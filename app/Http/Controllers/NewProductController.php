<?php

namespace App\Http\Controllers;

use App\Brands;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\TaxRate;
use App\Unit;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\VariationTemplate; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewProductController extends Controller
{
    protected $productUtil;
    protected $moduleUtil;

    public function __construct(ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
    }

public function create()
{


    $business_id = request()->session()->get('user.business_id');
    $business = \App\Business::find($business_id);


    if (! auth()->user()->can('new_product.create')) {
            abort(403, 'Unauthorized action.');
        }

//Check if subscribed or not, then check for products quota
    if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (! $this->moduleUtil->isQuotaAvailable('products', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action([\App\Http\Controllers\ProductController::class, 'index']));
        }


    $custom_labels = !empty($business->custom_labels) ? $business->custom_labels : [];
    
    if (is_string($custom_labels)) {
        $custom_labels = json_decode($custom_labels, true);
    }

    $custom_settings = !empty($business->custom_product_settings) ? $business->custom_product_settings : [];
    $common_settings = !empty($business->common_settings) ? $business->common_settings : [];


    // ----------------------------
    $default_barcode_printer = 'egoprint';
    $categories = \App\Category::forDropdown($business_id, 'product');
    $brands = \App\Brands::forDropdown($business_id);
    $units = \App\Unit::forDropdown($business_id, true);
    
    $tax_dropdown = \App\TaxRate::forBusinessDropdown($business_id, true, true);
    $taxes = $tax_dropdown['tax_rates'];
    $tax_attributes = $tax_dropdown['attributes'];
    
    $business_locations = \App\BusinessLocation::forDropdown($business_id);
    $default_profit_percent = request()->session()->get('business.default_profit_percent');
    $colors = \App\VariationTemplate::where('business_id', $business_id)->pluck('name', 'name');
    
    $barcode_types = $this->productUtil->barcode_types();
    $barcode_default = $this->productUtil->barcode_default();
    $product_types = $this->product_types();
    $sub_categories = [];
    $duplicate_product = null;

    return view('product.new', compact(
        'categories', 'brands', 'units', 'taxes', 'tax_attributes', 
        'business_locations', 'default_profit_percent', 'sub_categories', 
        'duplicate_product', 'barcode_types', 'barcode_default', 'product_types',
        'colors', 'common_settings', 'custom_labels', 'custom_settings','default_barcode_printer'
    ));
}

public function store(Request $request)
{
    if (! auth()->user()->can('new_product.create')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $main_location_id = $request->input('main_location_id');

        
        $profit_percent = !empty($request->input('profit_percent')) ? $this->productUtil->num_uf($request->input('profit_percent')) : 0;

        $tax_id = $request->input('tax');
        $tax_rate = 0;
        if (!empty($tax_id)) {
            $tax_rate = \App\TaxRate::where('id', $tax_id)->value('amount') ?? 0;
        }

        // رفع الصورة مرة واحدة لاستخدامها في جميع الحالات
        $image_name = null;
        if ($request->hasFile('image')) {
            $image_name = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'), 'product');
        }
        
         if (empty($request->input('unit_id'))) {
    return response()->json(['success' => 0, 'msg' => "يرجى اختيار الوحدة أولاً"]);
     }

      // 2. التحقق من الـ sku
      if (!empty($request->input('sku'))) {
    $exists = \App\Product::where('business_id', $business_id)
                         ->where('sku', $request->input('sku'))
                         ->exists();
    if ($exists) {
        return response()->json(['success' => 0, 'msg' => "رقم SKU هذا مستخدم مسبقاً، يرجى اختيار رقم آخر"]);
    }
    }

        DB::beginTransaction();

        // تجهيز حقول النموذج
        $form_fields = [
            'name', 'brand_id', 'unit_id', 'category_id', 'sub_category_id', 'tax', 'type', 'barcode_type', 'sku', 
            'alert_quantity', 'tax_type', 'weight', 'product_description', 'expiry_period', 'expiry_period_type'
        ];
        for ($i = 1; $i <= 20; $i++) { $form_fields[] = 'product_custom_field' . $i; }
        
        $product_details = $request->only($form_fields);
        $product_details['business_id'] = $business_id;
        $product_details['created_by'] = $user_id;
        $product_details['enable_stock'] = 1;
        $product_details['image'] = $image_name;
        $product_details['not_for_selling'] = $request->has('not_for_selling') ? 1 : 0;
        
        if (empty($product_details['sku'])) { $product_details['sku'] = ' '; }
        if (!empty($product_details['alert_quantity'])) {
            $product_details['alert_quantity'] = $this->productUtil->num_uf($product_details['alert_quantity']);
        }

        // تحديد الفروع
        $location_ids = ($request->input('define_in_all_locations') == 1) 
            ? \App\BusinessLocation::where('business_id', $business_id)->pluck('id')->toArray() 
            : [$main_location_id];
        $location_ids = array_filter($location_ids);

        $created_products = [];

       // ---  مقاسات وألوان  ---
        if ($request->has('v_color') && count($request->input('v_color')) > 0) {
            $base_sku = !empty($request->input('sku')) ? trim($request->input('sku')) : '';

            foreach ($request->input('v_color') as $key => $color) {
                // نأخذ نسخة جديدة من التفاصيل لكل تباين
                $variant_data = $product_details;
                $variant_data['image'] = $image_name;
                $variant_data['product_custom_field1'] = $request->input('v_size')[$key]; 
                $variant_data['product_custom_field2'] = $color;
                $variant_data['type'] = 'single';

                // حساب الـ SKU الذكي لهذا التباين رقمياً
                if (!empty($base_sku)) {
                    if ($key == 0) {
                        $current_sku = $base_sku;
                    } else {
                        $current_sku = (string)((int)$base_sku + $key);
                    }

                    // فحص التكرار لضمان عدم وجود باركود مماثل
                    $sku_exists = \App\Product::where('business_id', $business_id)
                                              ->where('sku', $current_sku)
                                              ->exists();
                    while ($sku_exists) {
                        $current_sku = (string)((int)$current_sku + 1);
                        $sku_exists = \App\Product::where('business_id', $business_id)
                                                  ->where('sku', $current_sku)
                                                  ->exists();
                    }
                    // إجبار المصفوفة قبل الحفظ على الباركود الجديد
                    $variant_data['sku'] = $current_sku;
                } else {
                    $variant_data['sku'] = ' ';
                }
                
                // 1. إنشاء المنتج بالـ SKU المحسوب
                $product = \App\Product::create($variant_data);

                // إذا كان فارغاً يولد تلقائي (في حال لم يدخل المستخدم باركود يدوي)
                if (trim($product->sku) == '' || trim($product->sku) == ' ') {
                    $product->sku = $this->productUtil->generateProductSku($product->id);
                    $product->save();
                    $current_sku = $product->sku; // نحدث المتغير بالباركود التلقائي للمعاينة
                } else {
                    // إجبار الكائن في الذاكرة على الباركود اليدوي المتسلسل
                    $product->sku = $current_sku;
                    $product->save();
                }
                
                $product->product_locations()->sync($location_ids);

                // معالجة الأسعار ونسبة الربح
                $p_price_inc_tax = $this->productUtil->num_uf($request->input('v_purchase')[$key]);
                $s_price_inc_tax = $this->productUtil->num_uf($request->input('v_selling')[$key]);
                $p_price_exc_tax = ($tax_rate > 0) ? ($p_price_inc_tax / (1 + ($tax_rate / 100))) : $p_price_inc_tax;
                $s_price_exc_tax = ($tax_rate > 0) ? ($s_price_inc_tax / (1 + ($tax_rate / 100))) : $s_price_inc_tax;

                $current_profit = $profit_percent; 
                if ($current_profit == 0 && $p_price_exc_tax > 0) {
                    $current_profit = (($s_price_exc_tax - $p_price_exc_tax) / $p_price_exc_tax) * 100;
                }

                // 2. استدعاء دالة النظام (نمرر لها الـ current_sku المحسوب باليد إجبارياً)
                $this->productUtil->createSingleProductVariation(
                    $product->id, $current_sku, $p_price_exc_tax, $p_price_inc_tax, $current_profit, $s_price_exc_tax, $s_price_inc_tax
                );

                // المخزون الافتتاحي  
                $qty = $this->productUtil->num_uf($request->input('v_qty')[$key]);
                if ($qty > 0 && !empty($main_location_id)) {
                    $variation = \App\Variation::where('product_id', $product->id)->first();
                    $this->createOpeningStockTransaction($business_id, $main_location_id, $product, $variation, $qty, $p_price_exc_tax, $user_id);
                }

                // 🟢 3. تطهير قاعدة البيانات الفوري والصارم (الحل الذري)
                // نقوم بتحديث الجدولين مباشرة بعد استدعاء كافة دوال النظام لضمان عدم التفافها
                if (!empty($base_sku)) {
                    \DB::table('products')->where('id', $product->id)->update(['sku' => $current_sku]);
                    \DB::table('variations')->where('product_id', $product->id)->update(['sub_sku' => $current_sku]);
                }

                // 🟢 4. إجبار مصفوفة المعاينة (الـ الـ Live Row) على قراءة الباركود المحسوب يدوياً
                // النظام أحياناً كان يقرأ الباركود التلقائي القديم من كائن الـ $product المتواجد بالذاكرة
                $created_products[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $current_sku, // 🔴 هنا الإجبار المطلق للمعاينة
                    'variant_info' => $color . ' / ' . $request->input('v_size')[$key],
                    'purchase_price' => $p_price_inc_tax,
                    'selling_price' => $s_price_inc_tax,
                    'qty' => $qty,
                    'image_url' => $product->image_url,
                    'print_qty' => (int)$qty,
                    'barcode' => $current_sku, // 🔴 هنا الإجبار المطلق للطباعة والباركود
                    'custom_field1' => $request->input('v_size')[$key],
                    'custom_field2' => $color,
                    'custom_field3' => $request->input('product_custom_field3') ?? '',  // ✅ أضف هذا السطر
                     'product_custom_field3' => $request->input('product_custom_field3') ?? '',  // ✅ وأيضاً هذا
                ];
            }
        }
        // --- المنتج العادي ---
        else {
            $product = \App\Product::create($product_details);
            
            if (trim($product->sku) == '' || trim($product->sku) == ' ') {
                $product->sku = $this->productUtil->generateProductSku($product->id);
                $product->save();
            }
            
            $product->product_locations()->sync($location_ids);
            
            $p_price_inc_tax = $this->productUtil->num_uf($request->input('single_dpp_inc_tax'));
            $s_price_inc_tax = $this->productUtil->num_uf($request->input('single_dsp_inc_tax'));

            $p_price_exc_tax = ($tax_rate > 0) ? ($p_price_inc_tax / (1 + ($tax_rate / 100))) : $p_price_inc_tax;
            $s_price_exc_tax = ($tax_rate > 0) ? ($s_price_inc_tax / (1 + ($tax_rate / 100))) : $s_price_inc_tax;


            $this->productUtil->createSingleProductVariation(
                $product->id, $product->sku, $p_price_exc_tax, $p_price_inc_tax, $profit_percent, $s_price_exc_tax, $s_price_inc_tax
            );

            $qty = $this->productUtil->num_uf($request->input('opening_stock'));
            if ($qty > 0 && !empty($main_location_id)) {
                $variation = \App\Variation::where('product_id', $product->id)->first();
                $this->createOpeningStockTransaction($business_id, $main_location_id, $product, $variation, $qty, $p_price_exc_tax, $user_id);
            }

            $created_products[] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'variant_info' => '--',
                'purchase_price' => $p_price_inc_tax,
                'selling_price' => $s_price_inc_tax,
                'qty' => $qty,
                'image_url' => $product->image_url,
                'print_qty' => (int)$qty,
                'barcode' => $product->sku,
                'custom_field1' => $product->product_custom_field1 ?? '',
                'custom_field2' => $product->product_custom_field2 ?? '',
                'custom_field3' => (string) ($product->product_custom_field3 ?? ''),
            ];
        }

        DB::commit();

        return response()->json([
            'success' => 1, 
            'msg' => "تم الحفظ بنجاح",
            'products' => $created_products
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::emergency("Error: " . $e->getMessage());
        return response()->json(['success' => 0, 'msg' => "خطأ: " . $e->getMessage()]);
    }
}


private function createOpeningStockTransaction($business_id, $location_id, $product, $variation, $qty, $price, $user_id) {

  $transaction_date = request()->session()->get('financial_year.start');
        $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();
    
    $transaction = \App\Transaction::create([
        'business_id' => $business_id,
        'location_id' => $location_id,
        'type' => 'opening_stock',
        'opening_stock_product_id' => $product->id,
        'status' => 'received',
        'payment_status' => 'paid',
        'transaction_date' => $transaction_date,
        'created_by' => $user_id,
        'final_total' => $qty * $price,
        
    ]);

    \App\PurchaseLine::create([
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
        'variation_id' => $variation->id,
        'quantity' => $qty,
        'pp_without_tax' => $price,
        'purchase_price' => $price,
        'purchase_price_inc_tax' => $price,
    ]);

    $this->productUtil->updateProductQuantity($location_id, $product->id, $variation->id, $qty);
}
    private function product_types()
    {
        return [
            'single' => __('lang_v1.single'),
            'combo' => __('lang_v1.combo'),
        ];
    }

    public function checkProductName(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $name = $request->input('name');
        $exists = Product::where('business_id', $business_id)->where('name', $name)->exists();
        return response()->json(['exists' => $exists]);
    }


    public function destroy($id)
{
    if (!auth()->user()->can('product.delete')) {
        return response()->json(['success' => 0, 'msg' => 'غير مصرح لك بالقيام بهذه العملية']);
    }

    try {
        $business_id = request()->session()->get('user.business_id');
        
        
        $product = \App\Product::where('business_id', $business_id)
                                ->where('id', $id)
                                ->first();

        if (!$product) {
            return response()->json(['success' => 0, 'msg' => 'المنتج غير موجود']);
        }

        DB::beginTransaction();

        $variation_ids = \App\Variation::where('product_id', $id)->pluck('id')->toArray();
        
        \App\PurchaseLine::whereIn('variation_id', $variation_ids)
            ->whereHas('transaction', function($q) {
                $q->where('type', 'opening_stock');
            })->delete();

        // 2. حذف تفاصيل المنتج في الفروع (Location Details)
        \App\ProductVariation::where('product_id', $id)->delete();
        \App\Variation::where('product_id', $id)->delete();
        $product->product_locations()->detach();

        // 3. حذف ملف الصورة من السيرفر إذا وجد
        if (!empty($product->image)) {
            $image_path = public_path('uploads/' . config('constants.product_img_path') . '/' . $product->image);
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // 4. حذف المنتج نهائياً
        $product->delete();

        DB::commit();

        return response()->json(['success' => 1, 'msg' => 'تم حذف المنتج وكافة متعلقاته بنجاح']);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
        return response()->json(['success' => 0, 'msg' => 'خطأ أثناء الحذف: ' . $e->getMessage()]);
    }
}

public function quickUpdate(Request $request, $id)
{
    try {
        DB::beginTransaction();

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $product = \App\Product::where('business_id', $business_id)->findOrFail($id);

        // 1. تحديث بيانات المنتج الأساسية
        $product->update([
            'name'                   => $request->name,
            'sku'                    => $request->sku ?: $product->sku,
            'brand_id'               => $request->brand_id         ?: null,
            'unit_id'                => $request->unit_id          ?: null,
            'category_id'            => $request->category_id      ?: null,
            'sub_category_id'        => $request->sub_category_id  ?: null,
            'tax'                    => $request->tax               ?: null,
            'tax_type'               => $request->tax_type          ?? 'exclusive',
            'barcode_type'           => $request->barcode_type      ?: $product->barcode_type,
            'alert_quantity'         => $request->alert_quantity   ?? 0,
            'product_description'    => $request->product_description ?? '',
            'product_custom_field1'  => $request->product_custom_field1 ?? $product->product_custom_field1,
            'product_custom_field2'  => $request->product_custom_field2 ?? $product->product_custom_field2,
        ]);

        // 2. تحديث الصورة
        if ($request->hasFile('image')) {
            if (!empty($product->image)) {
                $old = public_path('uploads/' . config('constants.product_img_path') . '/' . $product->image);
                if (file_exists($old)) unlink($old);
            }
            $product->image = $this->productUtil->uploadFile(
                $request, 'image', config('constants.product_img_path'), 'product'
            );
            $product->save();
        }

        // 3. تحديث الأسعار وهامش الربح
        $variation = \App\Variation::where('product_id', $product->id)->first();
        if (!empty($variation)) {
            $p_price_inc_tax = $this->productUtil->num_uf($request->purchase_price); 
            $s_price_inc_tax = $this->productUtil->num_uf($request->selling_price);  

            $tax_rate = 0;
            if (!empty($product->tax)) {
                $tax_rate = \App\TaxRate::where('id', $product->tax)->value('amount') ?? 0;
            }

            $p_price_exc_tax = ($tax_rate > 0) ? ($p_price_inc_tax / (1 + ($tax_rate / 100))) : $p_price_inc_tax;
            $s_price_exc_tax = ($tax_rate > 0) ? ($s_price_inc_tax / (1 + ($tax_rate / 100))) : $s_price_inc_tax;

            
            $profit_percent = 0;
            if ($p_price_exc_tax > 0) {
                $profit_percent = (($s_price_exc_tax - $p_price_exc_tax) / $p_price_exc_tax) * 100;
            }

            // تحديث جدول الـ variations مع الهامش الجديد
            $variation->default_purchase_price = $p_price_exc_tax;
            $variation->dpp_inc_tax = $p_price_inc_tax;
            $variation->profit_percent = $profit_percent; 
            $variation->default_sell_price = $s_price_exc_tax;
            $variation->sell_price_inc_tax = $s_price_inc_tax;
            $variation->save();
        }

        // 4. تحديث الكمية ومخزون أول المدة
        if ($request->has('qty')) {
            $new_qty = $this->productUtil->num_uf($request->qty);
            $location_id = $request->location_id;

            $business = \App\Business::find($business_id);
            $financial_year_start = $business->fy_start_month; 
            $current_year = date('Y');
            $start_of_fy = \Carbon\Carbon::createFromDate($current_year, $financial_year_start, 1)->startOfDay()->toDateTimeString();

            \App\VariationLocationDetails::where('variation_id', $variation->id)->update(['qty_available' => 0]);

            $old_opening_stocks = \App\Transaction::where('type', 'opening_stock')
                ->where('business_id', $business_id)
                ->whereHas('purchase_lines', function($q) use ($variation) {
                    $q->where('variation_id', $variation->id);
                })->get();

            foreach($old_opening_stocks as $os) {
                \App\PurchaseLine::where('transaction_id', $os->id)->delete();
                $os->delete();
            }

            if ($new_qty > 0 && !empty($location_id)) {
                $transaction = $this->createOpeningStockTransaction(
                    $business_id, $location_id, $product, $variation, $new_qty, $variation->default_purchase_price, $user_id
                );
                if ($transaction) {
                    $transaction->transaction_date = $start_of_fy;
                    $transaction->save();
                    \App\AccountTransaction::where('transaction_id', $transaction->id)->update(['operation_date' => $start_of_fy]);
                }
            }
            $product->product_locations()->sync([$location_id]);
        }

        DB::commit();
        return response()->json(['success' => 1, 'msg' => 'تم التحديث بنجاح', 'image_url' => $product->image_url]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('quickUpdate Error | ID:' . $id . ' | ' . $e->getMessage());
        return response()->json(['success' => 0, 'msg' => 'خطأ: ' . $e->getMessage()]);
    }
}


public function getProductDetails($id)
{
    $business_id = request()->session()->get('user.business_id');

    $product = \App\Product::with([
        'variations',
        'variations.variation_location_details',
    ])->where('business_id', $business_id)
      ->find($id);

    if (!$product) {
        return response()->json(['success' => 0, 'msg' => 'المنتج غير موجود']);
    }

    $variation       = $product->variations->first();
    $location_detail = $variation
        ? $variation->variation_location_details->first()
        : null;

    // بناء variant_info من الحقلين 1 و 2
    $cf1 = trim($product->product_custom_field1 ?? '');
    $cf2 = trim($product->product_custom_field2 ?? '');
    $variant_info = collect([$cf1, $cf2])->filter()->implode(' / ');

    return response()->json([
        'success' => 1,
        'data'    => [
            'id'                  => $product->id,
            'name'                => $product->name,
            'sku'                 => $product->sku,
            'variant_info'        => $variant_info,
            'barcode_type'        => $product->barcode_type,
            'brand_id'            => $product->brand_id,
            'unit_id'             => $product->unit_id,
            'category_id'         => $product->category_id,
            'sub_category_id'     => $product->sub_category_id,
            'tax_id'              => $product->tax,
            'tax_type'            => $product->tax_type ?? 'exclusive',
            'alert_quantity'      => $product->alert_quantity ?? 0,
            'product_description' => $product->product_description ?? '',
            'purchase_price'      => $variation
                ? number_format((float) $variation->dpp_inc_tax, 2, '.', '')
                : '0.00',
            'selling_price'       => $variation
                ? number_format((float) $variation->sell_price_inc_tax, 2, '.', '')
                : '0.00',
            'qty'                 => $location_detail
                ? (float) $location_detail->qty_available
                : 0,
            'location_id'         => $location_detail
                ? $location_detail->location_id
                : null,
        ],
    ]);
}

public function getSubCategories($category_id)
{
    $business_id = request()->session()->get('user.business_id');
    
    $sub_categories = \App\Category::where('business_id', $business_id)
                        ->where('parent_id', $category_id)
                        ->select('id', 'name')
                        ->get();
    
    return response()->json($sub_categories);
}


public function cfSuggestions(Request $request)
{
    $business_id = $request->session()->get('user.business_id');
    $field       = $request->input('field'); // مثال: product_custom_field4
    $query       = $request->input('q', '');

    // تحقق أن الحقل ضمن النطاق المسموح (3-20)
    if (!preg_match('/^product_custom_field([3-9]|1[0-9]|20)$/', $field)) {
        return response()->json([]);
    }

    $suggestions = \App\Product::where('business_id', $business_id)
        ->whereNotNull($field)
        ->where($field, '!=', '')
        ->where($field, 'like', $query . '%')
        ->distinct()
        ->pluck($field)
        ->take(8);

    return response()->json($suggestions);
}
}