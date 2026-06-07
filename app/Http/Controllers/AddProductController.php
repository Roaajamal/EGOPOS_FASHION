<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Brands;
use App\BusinessLocation;
use App\Category;
use App\Business;
use App\Product;
use App\TaxRate;
use App\Transaction;
use App\Unit;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Variation;
use App\VariationValueTemplate;
use DB;
use Excel;
use Datatables;

class AddProductController extends Controller
{
  
    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;

        //barcode types
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    /**
     * Display import product screen.
     *
     * @return \Illuminate\Http\Response
     */
public function index(Request $request)
{
    if (!auth()->user()->can('add_product.view')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = $request->session()->get('user.business_id');

     $custom_labels = json_decode(
        request()->session()->get('business.custom_labels'), true
        ); 
     $p_labels = $custom_labels['product'] ?? []; 

    if ($request->ajax()) {

        $query = \App\Models\ProductImport::where('business_id', $business_id)
    // ✅ أضف select بدون products_data و product_ids
    ->select([
        'id',
        'created_at',
        'locations',
        'product_count',
        'total_quantity',
        'created_by',
        'notes',
        'selected_location_id',
    ])
    ->with(['createdBy']);

        if ($request->filled('location_id')) {
            $query->whereJsonContains('locations', ['id' => (int)$request->input('location_id')]);
        }

        if (!empty($request->input('start_date')) && !empty($request->input('end_date'))) {
            $start = $request->input('start_date');
            $end   = $request->input('end_date');
            $query->whereBetween(DB::raw('DATE(created_at)'), [
                date('Y-m-d', strtotime($start)),
                date('Y-m-d', strtotime($end))
            ]);
        }

        $imports = $query->orderBy('created_at', 'desc')->get();

        return Datatables::of($imports)
            ->addColumn('date', function($row) {
                return \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i');
            })
            ->addColumn('locations_html', function($row) {
                $html = '';
                if (!empty($row->locations)) {
                    foreach ($row->locations as $loc) {
                        $html .= '<span class="label label-info">' . $loc['name'] . '</span> ';
                    }
                } else {
                    $html = '-';
                }
                return $html;
            })
            ->addColumn('created_by_name', function($row) {
                return $row->createdBy->first_name ?? '-';
            })
            ->addColumn('notes', function($row) {
                 return $row->notes ?? '-';
            })
            ->addColumn('action', function($row) {
                $print_url = action([\App\Http\Controllers\AddProductController::class, 'printImport'], [$row->id]);
                return '
              <button type="button" class="btn btn-xs btn-info view_import_details" data-id="' . $row->id . '">
                <i class="fa fa-eye"></i> فحص
              </button>
           
            <a href="' . route('add-products.show', [$row->id]) . '" target="_blank" class="btn btn-xs btn-default">
            <i class="fa fa-print"></i> طباعة 
        </a>';
            })
             //  <button type="button" class="btn btn-xs btn-default print_import_details" data-id="' . $row->id . '">
            //     <i class="fa fa-print"></i> طباعة
            //   </button>   ';
           ->addColumn('selected_location', function($row) {
    if ($row->selected_location_id) {
        $loc = BusinessLocation::find($row->selected_location_id);
        return $loc ? '<span class="label label-info">' . $loc->name . '</span>' : '-';
    }
    return '-';
})
            ->rawColumns(['locations_html', 'action', 'selected_location'])
            ->make(true);
    }

    // ── إذا مش AJAX رجّع الـ View ─────────────────────────
    $business_locations = BusinessLocation::where('business_id', $business_id)
        ->pluck('name', 'id');

    $zip_loaded = extension_loaded('zip') ? true : false;

    if ($zip_loaded === false) {
        return view('add_products.index')->with('notification', [
            'success' => 0,
            'msg'     => 'Please install/enable PHP Zip archive for import',
        ]);
    }

    return view('add_products.index', compact('business_locations','custom_labels', 'p_labels')); 
}


    public function create()
{
    if (!auth()->user()->can('add_product.create')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');

    // ✅ عرّف $custom_labels أولاً
    $custom_labels = json_decode(request()->session()->get('business.custom_labels'), true);
    $p_labels      = $custom_labels['product'] ?? [];

    $business = Business::find($business_id); // ✅ مطلوب للـ JavaScript

    $business_locations = BusinessLocation::forDropdown($business_id);

    $user = Transaction::with('createdBy')
        ->where('business_id', $business_id)
        ->latest()
        ->get();

    return view('add_products.create', compact(
        'business_locations',
        'user',
        'custom_labels',
        'p_labels',
        'business' // ✅ أضفها
    ));
}

    /**
     * Imports the uploaded file to database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
 public function store(Request $request)
{
    if (! auth()->user()->can('add_product.create')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $notAllowed = $this->productUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);

        if ($request->filled('rows_json')) {

            $rows_json = json_decode($request->input('rows_json'), true);

            $imported_data = array_map(function($item) {
               return $item['raw'];
               }, $rows_json);

            $business_id            = $request->session()->get('user.business_id');
            $user_id                = $request->session()->get('user.id');
            $default_profit_percent = $request->session()->get('business.default_profit_percent');

            $business = \App\Business::find($business_id);
            $custom_product_settings = $business->custom_product_settings ?? [];

            $formated_data = [];
            $is_valid      = true;
            $error_msg     = '';
            $total_rows    = count($imported_data);

            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            } elseif (! $this->moduleUtil->isQuotaAvailable('products', $business_id, $total_rows)) {
                return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action([\App\Http\Controllers\ImportProductsController::class, 'index']));
            }

            $business_locations   = BusinessLocation::where('business_id', $business_id)->get();
            $selected_location_id = $request->input('location_id');
            $selected_location    = BusinessLocation::find($selected_location_id);
            $excel_skus           = [];

            DB::beginTransaction();
            $new_product_ids = [];
            $mapping = !empty($rows_json[0]['mapping']) ? $rows_json[0]['mapping'] : [];
            $error_row_no = null; // ✅ هون بالزبط
 

            foreach ($imported_data as $key => $value) {
               

                $get = function($field) use ($value, $mapping) {
                if (isset($mapping[$field]) && is_int($mapping[$field]) && isset($value[$mapping[$field]])) {
                   return (string) trim($value[$mapping[$field]]);
                    }
                      return '';
                   };

                    $category     = null; // ✅ هون
                    $sub_category = null; // ✅ هون

 
                if (count($value) < 2) {
                    $is_valid  = false;
                    $error_msg = 'Some of the columns are missing. Please, use latest CSV file template.';
                    $error_row_no = $key + 1;
                    break;
                }

                $row_no = $rows_json[$key]['row_no'];
                $action = $rows_json[$key]['action'] ?? 'new';

                if ($action === 'ignore') {
                    continue;
                }

                // ── زيادة الكمية ──────────────────────────────────

                // ── تعريف منتج مكرر بفرع جديد ────────────────────────
if ($action === 'add_to_location') {
    $existing_id = $rows_json[$key]['existing_id'];
    $product     = Product::find($existing_id);

    if ($product) {
        $new_product_ids[] = $product->id;
        $qty      = $get('opening_stock');
        $location = $selected_location
            ?? BusinessLocation::where('business_id', $business_id)->first();

        if ($location) {
            $current_ids   = $product->product_locations()
                ->pluck('product_locations.location_id')->toArray();
            $current_ids[] = $location->id;
            $product->product_locations()->sync(array_unique($current_ids));

            if (!empty($qty)) {
                $this->addOpeningStock([
                    'quantity'    => $qty,
                    'location_id' => $location->id,
                    'exp_date'    => null,
                ], $product, $business_id);
            }
        }
    }
    continue;
}

// ── زيادة الكمية ──────────────────────────────────────
if ($action === 'add_qty') {
    $existing_id = $rows_json[$key]['existing_id'];
    $product     = Product::find($existing_id);

    if ($product) {
        $new_product_ids[] = $product->id;
        $qty       = $get('opening_stock');
        $variation = $product->variations()->first();

        $location = $selected_location
            ?? BusinessLocation::where('business_id', $business_id)->first();

        if ($location) {
            // ✅ تحقق من الفرع عند الحفظ
            $is_now_in_location = $product->product_locations()
                ->where('business_locations.id', $location->id)
                ->exists();

            if (!$is_now_in_location) {
                $is_valid  = false;
                $sku_display = $rows_json[$key]['display']['sku'] ?? "row $row_no";
                $error_msg = "المنتج ($sku_display) غير معرّف في الفرع المختار في الصف رقم $row_no. يرجى إعادة رفع الملف واختيار 'تعريف بالفرع'.";
                break;
            }

            if (!empty($qty)) {
                $data = [
                    'transaction_date' => now()->format('Y-m-d H:i:s'),
                    'ref_no'           => 'IMP-' . time()  ,
                    'location_id'      => $location->id,
                    'is_last_chunk'    => true,
                    'products'         => [
                        [
                            'product_id'     => $product->id,
                            'variation_id'   => $variation->id,
                            'quantity'       => $qty,
                            'purchase_price' => $variation->default_purchase_price ?? 0,
                        ]
                    ],
                ];
                $this->productUtil->createAddQuantityTransaction($business_id, $data);
            }
        }

        // ✅ تحقق من قرار التحديث
        $update_info = $rows_json[$key]['update_info'] ?? true;

        if ($update_info) {
            $update_data = [];

            if (!empty($get('name'))) {
                $update_data['name'] = $get('name');
            }

            if (!empty($get('unit'))) {
                $unit_name = $get('unit');
                $unit = Unit::where('business_id', $business_id)
                    ->where(function($q) use ($unit_name) {
                        $q->where('short_name', $unit_name)->orWhere('actual_name', $unit_name);
                    })->first();
                if ($unit) {
                    $update_data['unit_id'] = $unit->id;
                }
            }

            if (!empty($get('brand'))) {
                $brand = Brands::firstOrCreate(
                    ['business_id' => $business_id, 'name' => $get('brand')],
                    ['created_by'  => $user_id]
                );
                $update_data['brand_id'] = $brand->id;
            }

            if (!empty($get('category'))) {
                $category = Category::firstOrCreate(
                    ['business_id' => $business_id, 'name' => $get('category'), 'category_type' => 'product'],
                    ['created_by'  => $user_id, 'parent_id' => 0]
                );
                $update_data['category_id'] = $category->id;
            }

            if (!empty($get('sub_category'))) {
                $sub_category = Category::firstOrCreate(
                    ['business_id' => $business_id, 'name' => $get('sub_category'), 'category_type' => 'product'],
                    ['created_by'  => $user_id, 'parent_id' => $update_data['category_id'] ?? $product->category_id]
                );
                $update_data['sub_category_id'] = $sub_category->id;
            }

            
            if (!empty($get('tax'))) {
                $tax = TaxRate::where('business_id', $business_id)->where('name', $get('tax'))->first();
                if ($tax) $update_data['tax'] = $tax->id;
            }

            if (!empty($get('tax_type'))) {
                $update_data['tax_type'] = strtolower($get('tax_type'));
            }

            for ($i = 1; $i <= 20; $i++) {
                $val = $get('custom_field_' . $i);
                if ($val !== '') {
                    $update_data['product_custom_field' . $i] = $val;
                }
            }

            if (!empty($update_data)) {
                $product->update($update_data);
            }

            if ($variation && (!empty($get('dpp_inc_tax')) || !empty($get('dpp_exc_tax')))) {
                $variation_update = [];
                if (!empty($get('dpp_inc_tax'))) $variation_update['dpp_inc_tax']           = $get('dpp_inc_tax');
                if (!empty($get('dpp_exc_tax'))) $variation_update['default_purchase_price'] = $get('dpp_exc_tax');
                if (!empty($get('selling_price'))) $variation_update['default_sell_price']   = $get('selling_price');
                if (!empty($get('selling_price'))) $variation_update['sell_price_inc_tax']   = $get('selling_price');
                if (!empty($variation_update)) $variation->update($variation_update);
            }
        }
    }
    continue;
}
                // ── منتج جديد ─────────────────────────────────────
                $product_array                = [];
                $product_array['business_id'] = $business_id;
                $product_array['created_by']  = $user_id;

                // Name
                $product_name = $get('name');
                if (! empty($product_name)) {
                    $product_array['name'] = $product_name;
                } else {
                    $is_valid  = false;
                    $error_msg = "Product name is required in row no. $row_no";
                    $error_row_no = $row_no; // ✅
                    break;
                }

                // Image
                $image_name = $get('image');
                if (! empty($image_name)) {
                    if (filter_var($image_name, FILTER_VALIDATE_URL)) {
                        $source_image = file_get_contents($image_name);
                        $path         = parse_url($image_name, PHP_URL_PATH);
                        $new_name     = time() . '_' . basename($path);
                        $dest_img     = public_path() . '/uploads/' . config('constants.product_img_path') . '/' . $new_name;
                        file_put_contents($dest_img, $source_image);
                        $product_array['image'] = $new_name;
                    } else {
                        $product_array['image'] = $image_name;
                    }
                } else {
                    $product_array['image'] = '';
                }

                // Description
                $product_array['product_description'] = $get('description') ?: null;

                // Custom fields
                for ($i = 1; $i <= 20; $i++) {
                    $product_array['product_custom_field' . $i] = $get('custom_field_' . $i);
                }

                // Not for selling
                $product_array['not_for_selling'] = !empty($get('not_for_selling')) && $get('not_for_selling') == 1 ? 1 : 0;

                // Enable stock
                $enable_stock = $get('enable_stock') !== '' ? $get('enable_stock') : 1;
                if (in_array($enable_stock, [0, 1])) {
                    $product_array['enable_stock'] = $enable_stock;
                } else {
                    $is_valid  = false;
                    $error_msg = "Invalid value for MANAGE STOCK in row no. $row_no";
                    $error_row_no = $row_no; // ✅
                    break;
                }

                // Product type
                $default_product_type = $custom_product_settings['default_product_type'] ?? 'single'; 
                $product_type = strtolower($get('product_type'));
                  if (empty($product_type)) {
                 // ← الافتراضي من الإعدادات
                $product_type = $default_product_type;
                }
                if (in_array($product_type, ['single', 'variable'])) {
                $product_array['type'] = $product_type;
                } elseif ($product_type == 'combo') {
                continue;
               } else {
               $is_valid  = false;
               $error_msg = "Invalid value for PRODUCT TYPE in row no. $row_no";
               $error_row_no = $row_no; // ✅
               break;
               }

                // Unit
                $unit_name = $get('unit') ?: 'Pc(s)';
                if (! empty($unit_name)) {
                    $unit = Unit::where('business_id', $business_id)
                        ->where(function ($query) use ($unit_name) {
                            $query->where('short_name', $unit_name)->orWhere('actual_name', $unit_name);
                        })->first();
                    if (! empty($unit)) {
                        $product_array['unit_id'] = $unit->id;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Unit with name $unit_name not found in row no. $row_no. You can add unit from Products > Units";
                        $error_row_no = $row_no; // ✅
                        break;
                    }
                } else {
                    $is_valid  = false;
                    $error_msg = "UNIT is required in row no. $row_no";
                    $error_row_no = $row_no; // ✅
                    break;
                }

                // Barcode type
                $barcode_type = strtoupper($get('barcode_type'));
                if (empty($barcode_type)) {
                    $product_array['barcode_type'] = 'C128';
                } elseif (array_key_exists($barcode_type, $this->barcode_types)) {
                    $product_array['barcode_type'] = $barcode_type;
                } else {
                    $is_valid  = false;
                    $error_msg = "$barcode_type barcode type is not valid in row no. $row_no. Please, check for allowed barcode types in the instructions";
                    $error_row_no = $row_no; // ✅
                    break;
                }

                // Tax
                $default_tax_id      = $custom_product_settings['default_tax_id'] ?? null;
                $tax_name   = $get('tax');
                $tax_amount = 0;
                if (!empty($tax_name)) {
                $tax = TaxRate::where('business_id', $business_id)->where('name', $tax_name)->first();
                 if (!empty($tax)) {
                 $product_array['tax'] = $tax->id;
                 $tax_amount           = $tax->amount;
                 } else {
                  $is_valid  = false;
                  $error_msg = "Tax with name $tax_name in row no. $row_no not found.";
                  $error_row_no = $row_no; // ✅
                   break;
                }
                } elseif (!empty($default_tax_id)) {
                  // ← الافتراضي من الإعدادات
                 $default_tax = TaxRate::find($default_tax_id);
               if ($default_tax) {
                $product_array['tax'] = $default_tax->id;
                    $tax_amount           = $default_tax->amount;
                  }
                  }

                // Tax type
                $default_tax_type    = $custom_product_settings['default_tax_type'] ?? 'exclusive'; 
                $tax_type = strtolower($get('tax_type'));
if (empty($tax_type)) {
    // ← الافتراضي من الإعدادات
    $tax_type = $default_tax_type;
}
if (in_array($tax_type, ['inclusive', 'exclusive'])) {
    $product_array['tax_type'] = $tax_type;
} else {
    $is_valid  = false;
    $error_msg = "Invalid value for Selling Price Tax Type in row no. $row_no";
    $error_row_no = $row_no; // ✅
    break;
}

                // Alert quantity
                if ($product_array['enable_stock'] == 1) {
                    $product_array['alert_quantity'] = $get('alert_quantity');
                }

                // Brand
                $brand_name = $get('brand');
                if (! empty($brand_name)) {
                    $brand = Brands::firstOrCreate(
                        ['business_id' => $business_id, 'name' => $brand_name],
                        ['created_by'  => $user_id]
                    );
                    $product_array['brand_id'] = $brand->id;
                }

                // Category
                $category_name = $get('category');
                if (! empty($category_name)) {
                    $category = Category::firstOrCreate(
                        ['business_id' => $business_id, 
                         'name' => $category_name,
                         'category_type' => 'product',
                         'parent_id'     => 0, // ✅ أضفه هون
                         ],
                        ['created_by'  => $user_id, 'parent_id' => 0 ]
                    );
                    $product_array['category_id'] = $category->id;
                }

                // Sub-Category
                $sub_category_name = $get('sub_category');
                if (! empty($sub_category_name)) {
                    // ✅ تحقق إن $category معرّف
                     $parent_id = !empty($category) ? $category->id : 0;
                      \Log::info('Sub-Category Debug', [
                         'row_no'            => $row_no,
                         'sub_category_name' => $sub_category_name,
                        'category'          => !empty($category) ? $category->name : 'NULL',
                        'parent_id'         => $parent_id,
                          ]);

                    $sub_category = Category::firstOrCreate(
                        ['business_id' => $business_id, 'name' => $sub_category_name, 
                        'category_type' => 'product',
                         'parent_id'     => $parent_id,
                        ],
                        ['created_by'  => $user_id, 'parent_id' => $category->id]
                    );
                    $product_array['sub_category_id'] = $sub_category->id;
                }

                   // SKU
                    $sku = (string) $get('sku');
                if (!empty($sku)) {
                    if (in_array($sku, $excel_skus, true))  {
                    $is_valid  = false;
                     $error_msg = "Duplicate SKU '$sku' found in Excel file at row no. $row_no";
                      $error_row_no = $row_no; // ✅ 
                       break;
                     }
                  $excel_skus[] = $sku;
               $product_array['sku'] = $sku;

// تحقق من قاعدة البيانات
$is_exist = Product::where('business_id', $business_id)
    ->whereRaw('CAST(sku AS CHAR) = ?', [$sku]) // ← مقارنة كـ string
    ->exists();

if ($is_exist) {
    $is_valid  = false;
    $error_msg = "$sku SKU already exist in system. Row no. $row_no";
    $error_row_no = $row_no; // ✅
    break;
}
                } else {
                    $is_valid  = false;
                    $error_msg = "SKU is required in row no. $row_no";
                    $error_row_no = $row_no; // ✅ 
                    break;
                }

                // Expiry
                $expiry_period      = $get('expiry_period');
                $expiry_period_type = strtolower($get('expiry_period_type'));
                if (! empty($expiry_period) && in_array($expiry_period_type, ['months', 'days'])) {
                    $product_array['expiry_period']      = $expiry_period;
                    $product_array['expiry_period_type'] = $expiry_period_type;
                } else {
                    if (! empty($get('expiry_date'))) {
                        $product_array['expiry_period']      = 12;
                        $product_array['expiry_period_type'] = 'months';
                    }
                }

                // IMEI / Serial
                $enable_sr_no = $get('enable_sr_no');
                if (in_array($enable_sr_no, [0, 1])) {
                    $product_array['enable_sr_no'] = $enable_sr_no;
                } elseif (empty($enable_sr_no)) {
                    $product_array['enable_sr_no'] = 0;
                } else {
                    $is_valid  = false;
                    $error_msg = "Invalid value for ENABLE IMEI OR SERIAL NUMBER in row no. $row_no";
                    $error_row_no = $row_no; // ✅
                    break;
                }

                // Weight
                $product_array['weight'] = $get('weight') ?: '';

                // ── Single ────────────────────────────────────────
                if ($product_array['type'] == 'single') {
                    $profit_margin = $get('profit_margin') ?: $default_profit_percent;
                    $product_array['variation']['profit_percent'] = $profit_margin;

                    $dpp_inc_tax = $get('dpp_inc_tax') !== '' ? $get('dpp_inc_tax') : 0;
                    $dpp_exc_tax = $get('dpp_exc_tax');

                    if ($dpp_inc_tax == '' && $dpp_exc_tax == '') {
                        $is_valid  = false;
                        $error_msg = "PURCHASE PRICE is required in row no. $row_no";
                        break;
                    } else {
                        $dpp_inc_tax = ($dpp_inc_tax != '') ? $dpp_inc_tax : 0;
                        $dpp_exc_tax = ($dpp_exc_tax != '') ? $dpp_exc_tax : 0;
                    }

                    $selling_price  = $get('selling_price') !== '' ? $get('selling_price') : 0;
                    $product_prices = $this->calculateVariationPrices($dpp_exc_tax, $dpp_inc_tax, $selling_price, $tax_amount, $tax_type, $profit_margin);

                    $product_array['variation']['dpp_inc_tax'] = $product_prices['dpp_inc_tax'];
                    $product_array['variation']['dpp_exc_tax'] = $product_prices['dpp_exc_tax'];
                    $product_array['variation']['dsp_inc_tax'] = $product_prices['dsp_inc_tax'];
                    $product_array['variation']['dsp_exc_tax'] = $product_prices['dsp_exc_tax'];

                    // Opening stock
                    if (! empty($get('opening_stock')) && $enable_stock == 1) {
                        $product_array['opening_stock_details']['quantity'] = $get('opening_stock');

                        if (! empty($selected_location)) {
                            $location = $selected_location ?? BusinessLocation::where('business_id', $business_id)->first();
                            if (! empty($location)) {
                                $product_array['opening_stock_details']['location_id'] = $location->id;
                            } else {
                                $is_valid  = false;
                                $error_msg = "No location found in row no. $row_no";
                                break;
                            }
                        } else {
                            $location = BusinessLocation::where('business_id', $business_id)->first();
                            $product_array['opening_stock_details']['location_id'] = $location->id;
                        }

                        $product_array['opening_stock_details']['expiry_date'] = null;

                        if (! empty($get('expiry_date'))) {
                            $product_array['opening_stock_details']['exp_date'] = \Carbon::createFromFormat('m-d-Y', $get('expiry_date'))->format('Y-m-d');
                        } else {
                            $product_array['opening_stock_details']['exp_date'] = null;
                        }
                    }

                // ── Variable ──────────────────────────────────────
                } elseif ($product_array['type'] == 'variable') {
                    $variation_name = $get('variation_name');
                    if (empty($variation_name)) {
                        $is_valid  = false;
                        $error_msg = "VARIATION NAME is required in row no. $row_no";
                        break;
                    }

                    $variation_values_string = $get('variation_values');
                    if (empty($variation_values_string)) {
                        $is_valid  = false;
                        $error_msg = "VARIATION VALUES are required in row no. $row_no";
                        break;
                    }

                    $variation_sku_string  = $get('variation_skus');
                    $dpp_inc_tax_string    = $get('dpp_inc_tax') !== '' ? $get('dpp_inc_tax') : 0;
                    $dpp_exc_tax_string    = $get('dpp_exc_tax');
                    $selling_price_string  = $get('selling_price');
                    $profit_margin_string  = $get('profit_margin');

                    if (empty($dpp_inc_tax_string) && empty($dpp_exc_tax_string)) {
                        $is_valid  = false;
                        $error_msg = "PURCHASE PRICE is required in row no. $row_no";
                        $error_row_no = $row_no; // ✅
                        break;
                    }

                    $variation_values = array_map('trim', explode('|', $variation_values_string));
                    $variation_skus   = [];
                    if (! empty($variation_sku_string)) {
                        $variation_skus = array_map('trim', explode('|', $variation_sku_string));
                    }

                    $dpp_inc_tax = !empty($dpp_inc_tax_string)
                        ? array_map('trim', explode('|', $dpp_inc_tax_string))
                        : array_fill(0, count($variation_values), 0);

                    $dpp_exc_tax = !empty($dpp_exc_tax_string)
                        ? array_map('trim', explode('|', $dpp_exc_tax_string))
                        : array_fill(0, count($variation_values), 0);

                    $selling_price = !empty($selling_price_string)
                        ? array_map('trim', explode('|', $selling_price_string))
                        : array_fill(0, count($variation_values), 0);

                    $profit_margin = !empty($profit_margin_string)
                        ? array_map('trim', explode('|', $profit_margin_string))
                        : array_fill(0, count($variation_values), $default_profit_percent);

                    $array_lengths_count = [count($variation_values), count($dpp_inc_tax), count($dpp_exc_tax), count($selling_price), count($profit_margin)];
                    if (! empty($variation_skus)) $array_lengths_count[] = count($variation_skus);
                    $same = array_count_values($array_lengths_count);
                    if (count($same) != 1) {
                        $is_valid  = false;
                        $error_msg = "Prices mismatched with VARIATION VALUES in row no. $row_no";
                        $error_row_no = $row_no; // ✅
                        break;
                    }

                    $product_array['variation']['name'] = $variation_name;
                    $variation = $this->productUtil->createOrNewVariation($business_id, $variation_name);
                    $product_array['variation']['variation_template_id'] = $variation->id;

                    foreach ($variation_values as $k => $v) {
                        $variation_prices = $this->calculateVariationPrices($dpp_exc_tax[$k], $dpp_inc_tax[$k], $selling_price[$k], $tax_amount, $tax_type, $profit_margin[$k]);
                        $variation_value  = $variation->values->filter(function ($item) use ($v) {
                            return strtolower($item->name) == strtolower($v);
                        })->first();

                        if (empty($variation_value)) {
                            $variation_value = VariationValueTemplate::create([
                                'name'                 => $v,
                                'variation_template_id'=> $variation->id,
                            ]);
                        }

                        $product_array['variation']['variations'][] = [
                            'value'                  => $v,
                            'variation_value_id'     => $variation_value->id,
                            'default_purchase_price' => $variation_prices['dpp_exc_tax'],
                            'dpp_inc_tax'            => $variation_prices['dpp_inc_tax'],
                            'profit_percent'         => $this->productUtil->num_f($profit_margin[$k]),
                            'default_sell_price'     => $variation_prices['dsp_exc_tax'],
                            'sell_price_inc_tax'     => $variation_prices['dsp_inc_tax'],
                            'sub_sku'                => ! empty($variation_skus[$k]) ? $variation_skus[$k] : '',
                        ];
                    }

                    // Opening stock for variable
                    if (! empty($get('opening_stock')) && $enable_stock == 1) {
                        $variation_os = array_map('trim', explode('|', $get('opening_stock')));

                        if (count($product_array['variation']['variations']) != count($variation_os)) {
                            $is_valid  = false;
                            $error_msg = "Opening Stock mismatched with VARIATION VALUES in row no. $row_no";
                            $error_row_no = $row_no; // ✅
                            break;
                        }

                        if (! empty($selected_location)) {
                            $location = $selected_location ?? BusinessLocation::where('business_id', $business_id)->first();
                            if (empty($location)) {
                                $is_valid  = false;
                                $error_msg = "No location found in row no. $row_no";
                                break;
                            }
                        } else {
                            $location = BusinessLocation::where('business_id', $business_id)->first();
                        }
                        $product_array['variation']['opening_stock_location'] = $location->id;

                        foreach ($variation_os as $k => $v) {
                            $product_array['variation']['variations'][$k]['opening_stock']          = $v;
                            $product_array['variation']['variations'][$k]['opening_stock_exp_date'] = null;

                            if (! empty($get('expiry_date'))) {
                                $product_array['variation']['variations'][$k]['opening_stock_exp_date'] = \Carbon::createFromFormat('m-d-Y', $get('expiry_date'))->format('Y-m-d');
                            }
                        }
                    }
                }

                $formated_data[] = $product_array;
            }

            if (! $is_valid) {     /////////  edit for error message
                 throw new \Exception(json_encode([
                 'msg'    => $error_msg,
                 'row_no' => $error_row_no,
                 ]));
            }

            if (! empty($formated_data)) {
                foreach ($formated_data as $index => $product_data) {
                    $variation_data = $product_data['variation'];
                    unset($product_data['variation']);

                    $opening_stock = null;
                    if (! empty($product_data['opening_stock_details'])) {
                        $opening_stock = $product_data['opening_stock_details'];
                    }
                    if (isset($product_data['opening_stock_details'])) {
                        unset($product_data['opening_stock_details']);
                    }

                    $product = Product::create($product_data);
                    $new_product_ids[] = $product->id;

                    // Rack, Row & Position
                    $raw = $imported_data[$index];
                    $rack_field     = isset($mapping['rack'])     ? ($raw[$mapping['rack']]     ?? '') : '';
                    $row_field      = isset($mapping['row'])      ? ($raw[$mapping['row']]      ?? '') : '';
                    $position_field = isset($mapping['position']) ? ($raw[$mapping['position']] ?? '') : '';
                    $this->rackDetails($rack_field, $row_field, $position_field, $business_id, $product->id, $index + 1);

                    // Product locations
                    if ($request->input('select_all_location') == 1) {
                        $all_location_ids = $business_locations->pluck('id')->toArray();
                        $product->product_locations()->sync($all_location_ids);
                    } else {
                        if (!empty($selected_location)) {
                            $product->product_locations()->sync([$selected_location->id]);
                        } else {
                            $default_location = BusinessLocation::where('business_id', $business_id)->first();
                            if ($default_location) {
                                $product->product_locations()->sync([$default_location->id]);
                            }
                        }
                    }

                    if ($product->type == 'single') {
                        $this->productUtil->createSingleProductVariation(
                            $product,
                            $product->sku,
                            $variation_data['dpp_exc_tax'],
                            $variation_data['dpp_inc_tax'],
                            $variation_data['profit_percent'],
                            $variation_data['dsp_exc_tax'],
                            $variation_data['dsp_inc_tax']
                        );
                        if (! empty($opening_stock)) {
                            $this->addOpeningStock($opening_stock, $product, $business_id);
                        }
                    } elseif ($product->type == 'variable') {
                        $this->productUtil->createVariableProductVariations(
                            $product,
                            [$variation_data],
                            "with_out_variation",
                            $business_id
                        );
                        if (! empty($variation_data['opening_stock_location']) && $enable_stock == 1) {
                            $this->addOpeningStockForVariable($variation_data, $product, $business_id);
                        }
                    }
                }
            }

            // حساب الـ locations_data
            $locations_data = [];
             if ($request->input('select_all_location') == 1) {
        
               $locations_data[] = [
                   'id'   => 'all',
                   'name' => __('product.all_location'),
                                  ];
               } elseif (!empty($selected_location)) {
               $locations_data[] = [
                   'id'   => $selected_location->id,
                  'name' => $selected_location->name,
                ];
               } else {
                $default_location = BusinessLocation::where('business_id', $business_id)->first();
              if ($default_location) {
              $locations_data[] = [
              'id'   => $default_location->id,
              'name' => $default_location->name,
               ];
                  }
                }

            // حساب saved_rows و total_quantity
            $saved_rows = array_filter($rows_json, function($r) {
                return ($r['action'] ?? 'new') !== 'ignore';
            });

            $total_quantity = array_sum(array_map(function($r) use ($mapping) {
                $os_index = $mapping['opening_stock'] ?? null;
                return $os_index !== null ? floatval(trim($r['raw'][$os_index] ?? 0)) : 0;
            }, $saved_rows));

            // بناء products_display
            $products_display = [];
            foreach ($rows_json as $rowItem) {
                $action = $rowItem['action'] ?? 'new';
                if ($action === 'ignore') continue;

                $v   = $rowItem['raw'];
                $get_v = function($field) use ($v, $mapping) {
                    if (isset($mapping[$field]) && isset($v[$mapping[$field]])) {
                        return trim($v[$mapping[$field]]);
                    }
                    return '';
                };

                $row_display = [
                    'sku'            => $get_v('sku'),
                    'name'           => $get_v('name'),
                    'unit'           => $get_v('unit') ?: 'Pc(s)',
                    'brand'          => $get_v('brand'),
                    'category'       => $get_v('category'),
                    'tax'            => $get_v('tax'),
                    'tax_type'       => strtolower($get_v('tax_type')) ?: 'inclusive',
                    'purchase_price' => $get_v('dpp_inc_tax') !== '' ? $get_v('dpp_inc_tax') : ($get_v('dpp_inc_tax') !== '' ? $get_v('dpp_inc_tax') : 0 ),
                    'selling_price'  => $get_v('selling_price'),
                    'opening_stock'  => $get_v('opening_stock'),
                    'is_add_qty'     => $action === 'add_qty',
                ];

                for ($i = 1; $i <= 20; $i++) {
                    $row_display['custom_field_' . $i] = $get_v('custom_field_' . $i);
                }

                $products_display[] = $row_display;
            }

            \App\Models\ProductImport::create([
                'business_id'    => $business_id,
                'created_by'     => $user_id,
                'locations'      => $locations_data,
                'selected_location_id' => $selected_location->id ?? null,
                'product_ids'    => $new_product_ids,
                'products_data'  => $products_display,
                'product_count'  => count($saved_rows),
                'total_quantity' => $total_quantity,
                'notes'          => $request->input('notes'),
            ]);

        } // نهاية if ($request->filled('rows_json'))

        $output = ['success' => 1, 'msg' => __('product.file_imported_successfully')];

        DB::commit(); 
       // ✅ هون — داخل try، بعد commit
    if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
        return response()->json([
            'success'  => 1,
            'msg'      => $output['msg'],
            'redirect' => url('add-products'),
        ]);
    }

    return redirect('add-products')->with('status', $output);

} catch (\Exception $e) {
    DB::rollBack();
    \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

    $errData = json_decode($e->getMessage(), true);
    $errMsg  = is_array($errData) ? $errData['msg']            : $e->getMessage();
    $errRow  = is_array($errData) ? ($errData['row_no'] ?? null) : null;

    $output = ['success' => 0, 'msg' => $errMsg, 'error_row' => $errRow];

    // ✅ هون — داخل catch
    if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
        return response()->json($output);
    }

    return redirect('add-products')->with('notification', $output);
}
} 

public function getHeaders(Request $request)
{
    if (!$request->hasFile('products_csv')) {
        return response()->json(['success' => false, 'msg' => 'No file uploaded']);
    }

    $parsed_array = Excel::toArray([], $request->file('products_csv'));
    $headers = array_map('trim', $parsed_array[0][0] ?? []);

    return response()->json([
        'success' => true,
        'headers' => $headers,
    ]);
}
 
 
  public function preview(Request $request)
{
    if (!auth()->user()->can('product.create')) {
        abort(403, 'Unauthorized action.');
    }

    if (!$request->hasFile('products_csv')) {
        return response()->json(['success' => false, 'msg' => 'No file uploaded']);
    }

  $file = $request->file('products_csv');

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
$reader->setReadDataOnly(false);
$spreadsheet = $reader->load($file->getPathname());
$worksheet = $spreadsheet->getActiveSheet();

$all_rows = [];
foreach ($worksheet->getRowIterator() as $row) {   /////// edit for zero value
    $row_data = [];
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    
    foreach ($cellIterator as $cell) {
        $value = $cell->getValue();
        
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $row_data[] = $value->getPlainText();
        } elseif ($value === null) {
            $row_data[] = '';
        } else {
            $row_data[] = (string) $value;
        }
    }
    $all_rows[] = $row_data;
}

    $imported_data = array_slice($all_rows, 1);

    $business_id          = $request->session()->get('user.business_id');
    $selected_location_id = $request->input('location_id');
    $mapping              = json_decode($request->input('column_mapping'), true) ?? [];

    $get = function($value, $field) use ($mapping) {
        if (isset($mapping[$field]) && is_int($mapping[$field]) && isset($value[$mapping[$field]])) {
            return (string) trim($value[$mapping[$field]]);
        }
        return '';
    };

    $custom_labels_arr = json_decode(session('business.custom_labels'), true);
    $p_labels_arr      = $custom_labels_arr['product'] ?? [];

    $preview_rows = [];

    foreach ($imported_data as $key => $value) {
        $product_type = strtolower($get($value, 'product_type'));
        if ($product_type === 'combo') continue;

        $sku = (string) $get($value, 'sku');
        $dpp_inc        = $get($value, 'dpp_inc_tax');
        $dpp_exc        = $get($value, 'dpp_exc_tax');
        $purchase_price = $dpp_inc !== '' ? $dpp_inc : ($dpp_exc !== '' ? $dpp_exc : '0');

        $is_duplicate     = false;
        $existing_product = null;
        $is_in_location   = false;
        $differences      = [];

        if (!empty($sku)) {
            $existing_product = Product::where('business_id', $business_id)
                ->whereRaw('CAST(sku AS CHAR) = ?', [$sku])
                ->first();

            if ($existing_product) {
                $is_duplicate = true;
                $variation    = $existing_product->variations()->first();

                // تحقق من الفرع
                if (!empty($selected_location_id)) {
                    $is_in_location = $existing_product->product_locations()
                        ->where('business_locations.id', $selected_location_id)
                        ->exists();
                }

                // الاسم
                $new_name = $get($value, 'name');
                if (!empty($new_name) && $new_name !== $existing_product->name) {
                    $differences[] = [
                        'field' => 'الاسم',
                        'old'   => $existing_product->name,
                        'new'   => $new_name,
                    ];
                }

                // الوحدة
                $new_unit = $get($value, 'unit');
                if (!empty($new_unit)
                    && $new_unit !== optional($existing_product->unit)->short_name
                    && $new_unit !== optional($existing_product->unit)->actual_name) {
                    $differences[] = [
                        'field' => 'الوحدة',
                        'old'   => optional($existing_product->unit)->short_name ?? '-',
                        'new'   => $new_unit,
                    ];
                }

                // الماركة
                $new_brand = $get($value, 'brand');
                if (!empty($new_brand) && $new_brand !== optional($existing_product->brand)->name) {
                    $differences[] = [
                        'field' => 'الماركة',
                        'old'   => optional($existing_product->brand)->name ?? '-',
                        'new'   => $new_brand,
                    ];
                }

                // الفئة
                $new_category = $get($value, 'category');
                if (!empty($new_category) && $new_category !== optional($existing_product->category)->name) {
                    $differences[] = [
                        'field' => 'الفئة',
                        'old'   => optional($existing_product->category)->name ?? '-',
                        'new'   => $new_category,
                    ];
                }

                // سعر الشراء
                $new_price = $get($value, 'dpp_inc_tax') ?: $get($value, 'dpp_exc_tax');
                if (!empty($new_price) && $variation && $new_price != $variation->dpp_inc_tax) {
                    $differences[] = [
                        'field' => 'سعر الشراء',
                        'old'   => $variation->dpp_inc_tax,
                        'new'   => $new_price,
                    ];
                }

                // الضريبة
                $new_tax = $get($value, 'tax');
                if (!empty($new_tax)) {
                    $existing_tax = optional($existing_product->taxRate)->name ?? '-';
                    if ($new_tax !== $existing_tax) {
                        $differences[] = [
                            'field' => 'الضريبة',
                            'old'   => $existing_tax,
                            'new'   => $new_tax,
                        ];
                    }
                }

                // نوع الضريبة
                $new_tax_type = strtolower($get($value, 'tax_type'));
                if (!empty($new_tax_type) && $new_tax_type !== $existing_product->tax_type) {
                    $differences[] = [
                        'field' => 'نوع الضريبة',
                        'old'   => $existing_product->tax_type ?? '-',
                        'new'   => $new_tax_type,
                    ];
                }

                // سعر البيع
                $new_selling = $get($value, 'selling_price');
                if (!empty($new_selling) && $variation && $new_selling != $variation->default_sell_price) {
                    $differences[] = [
                        'field' => 'سعر البيع',
                        'old'   => $variation->sell_price_inc_tax,
                        'new'   => $new_selling,
                    ];
                }

                // الحقول المخصصة
                for ($i = 1; $i <= 20; $i++) {
                    $new_val     = $get($value, 'custom_field_' . $i);
                    $old_val     = $existing_product->{'product_custom_field' . $i} ?? '';
                    $field_label = $p_labels_arr['custom_field_' . $i] ?? '';

                    if (!empty($field_label) && !empty($new_val) && $new_val !== $old_val) {
                        $differences[] = [
                            'field' => $field_label,
                            'old'   => $old_val ?: '-',
                            'new'   => $new_val,
                        ];
                    }
                }
            }
        }

        // الحقول المخصصة للعرض
        $custom_fields = [];
        for ($i = 1; $i <= 20; $i++) {
            $custom_fields['custom_field_' . $i] = $get($value, 'custom_field_' . $i);
        }

        $preview_rows[] = [
            'row_no'         => $key + 1,
            'display'        => array_merge([
                'sku'            => $sku,
                'name'           => $get($value, 'name'),
                'unit'           => $get($value, 'unit') ?: 'Pc(s)',
                'category'       => $get($value, 'category') ?: '-',
                'tax'            => $get($value, 'tax') ?: '-',
                'tax_type'       => $get($value, 'tax_type') ?: 'inclusive',
                'purchase_price' => $purchase_price,
                'selling_price'  => $get($value, 'selling_price') ?: '-',
                'opening_stock'  => $get($value, 'opening_stock') ?: '-',
            ], $custom_fields),
            'raw'            => $value,
            'mapping'        => $mapping,
            'is_duplicate'   => $is_duplicate,
            'is_in_location' => $is_in_location,
            'existing_id'    => $is_duplicate ? $existing_product->id : null,
            'differences'    => $differences,
            'action'         => $is_duplicate ? 'ask' : 'new',
        ];
    }

    return response()->json([
        'success' => true,
        'data'    => $preview_rows,
    ]);
}

public function printImport(Request $request, $id)
{
    if (!auth()->user()->can('product.create')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = $request->session()->get('user.business_id');

    $import = \App\Models\ProductImport::where('business_id', $business_id)
        ->findOrFail($id);

    // تأكد من تحويل البيانات لمصفوفة إذا كانت مخزنة كـ JSON string
    $products = $import->products_data;
    if (is_string($products)) {
        $products = json_decode($products, true);
    }
     $locations = is_string($import->locations)
        ? json_decode($import->locations, true)
        : ($import->locations ?? []);

    $locationNames = collect($locations)->pluck('name')->join(', ');

    // نرسل البيانات لصفحة الـ Blade
    return view('add_products.show', compact('import', 'products', 'locationNames'));
}

public function details(Request $request, $id)
{
    if (!auth()->user()->can('product.create')) {
        abort(403, 'Unauthorized action.');
    }


    $business_id = $request->session()->get('user.business_id');

    $import = \App\Models\ProductImport::where('business_id', $business_id)
        ->findOrFail($id);
    
    return response()->json([
        'success'  => true,
        'products' => $import->products_data ?? [],
    ]);
    
}

    private function calculateVariationPrices($dpp_exc_tax, $dpp_inc_tax, $selling_price, $tax_amount, $tax_type, $margin)
    {

        //Calculate purchase prices
        if ($dpp_inc_tax == 0) {
            $dpp_inc_tax = $this->productUtil->calc_percentage(
                $dpp_exc_tax,
                $tax_amount,
                $dpp_exc_tax
            );
        }

        if ($dpp_exc_tax == 0) {
            $dpp_exc_tax = $this->productUtil->calc_percentage_base($dpp_inc_tax, $tax_amount);
        }

        if ($selling_price != 0) {
            if ($tax_type == 'inclusive') {
                $dsp_inc_tax = $selling_price;
                $dsp_exc_tax = $this->productUtil->calc_percentage_base(
                    $dsp_inc_tax,
                    $tax_amount
                );
            } elseif ($tax_type == 'exclusive') {
                $dsp_exc_tax = $selling_price;
                $dsp_inc_tax = $this->productUtil->calc_percentage(
                    $selling_price,
                    $tax_amount,
                    $selling_price
                );
            }
        } else {
            $dsp_exc_tax = $this->productUtil->calc_percentage(
                $dpp_exc_tax,
                $margin,
                $dpp_exc_tax
            );
            $dsp_inc_tax = $this->productUtil->calc_percentage(
                $dsp_exc_tax,
                $tax_amount,
                $dsp_exc_tax
            );
        }

        return [
            'dpp_exc_tax' => $this->productUtil->num_f($dpp_exc_tax),
            'dpp_inc_tax' => $this->productUtil->num_f($dpp_inc_tax),
            'dsp_exc_tax' => $this->productUtil->num_f($dsp_exc_tax),
            'dsp_inc_tax' => $this->productUtil->num_f($dsp_inc_tax),
        ];
    }

    /**
     * Adds opening stock of a single product
     *
     * @param  array  $opening_stock
     * @param  obj  $product
     * @param  int  $business_id
     * @return void
     */
    private function addOpeningStock($opening_stock, $product, $business_id)
    {
        $user_id = request()->session()->get('user.id');

        $variation = Variation::where('product_id', $product->id)
            ->first();

        $total_before_tax = $opening_stock['quantity'] * $variation->dpp_inc_tax;

        $transaction_date = request()->session()->get('financial_year.start');
        $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();
        //Add opening stock transaction
        $transaction = Transaction::create(
            [
                'type' => 'opening_stock',
                'opening_stock_product_id' => $product->id,
                'status' => 'received',
                'business_id' => $business_id,
                'transaction_date' => $transaction_date,
                'total_before_tax' => $total_before_tax,
                'location_id' => $opening_stock['location_id'],
                'final_total' => $total_before_tax,
                'payment_status' => 'paid',
                'created_by' => $user_id,
            ]
        );
        //Get product tax
        $tax_percent = ! empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
        $tax_id = ! empty($product->product_tax->id) ? $product->product_tax->id : null;

        $item_tax = $this->productUtil->calc_percentage($variation->default_purchase_price, $tax_percent);

        //Create purchase line
        $transaction->purchase_lines()->create([
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => $opening_stock['quantity'],
            'item_tax' => $item_tax,
            'tax_id' => $tax_id,
            'pp_without_discount' => $variation->default_purchase_price,
            'purchase_price' => $variation->default_purchase_price,
            'purchase_price_inc_tax' => $variation->dpp_inc_tax,
            'exp_date' => ! empty($opening_stock['exp_date']) ? $opening_stock['exp_date'] : null,
        ]);
        //Update variation location details
        $this->productUtil->updateProductQuantity($opening_stock['location_id'], $product->id, $variation->id, $opening_stock['quantity']);

        //Add product location
        $this->__addProductLocation($product, $opening_stock['location_id']);
    }

    private function __addProductLocation($product, $location_id)
    {
        $count = DB::table('product_locations')->where('product_id', $product->id)
                                            ->where('location_id', $location_id)
                                            ->count();
        if ($count == 0) {
            DB::table('product_locations')->insert(['product_id' => $product->id,
                'location_id' => $location_id, ]);
        }
    }

    private function addOpeningStockForVariable($variations, $product, $business_id)
    {
        $user_id = request()->session()->get('user.id');

        $transaction_date = request()->session()->get('financial_year.start');
        $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();

        $total_before_tax = 0;
        $location_id = $variations['opening_stock_location'];
        if (isset($variations['variations'][0]['opening_stock'])) {
            //Add opening stock transaction
            $transaction = Transaction::create(
                [
                    'type' => 'opening_stock',
                    'opening_stock_product_id' => $product->id,
                    'status' => 'received',
                    'business_id' => $business_id,
                    'transaction_date' => $transaction_date,
                    'total_before_tax' => $total_before_tax,
                    'location_id' => $location_id,
                    'final_total' => $total_before_tax,
                    'payment_status' => 'paid',
                    'created_by' => $user_id,
                ]
            );

            //Add product location
            $this->__addProductLocation($product, $location_id);

            foreach ($variations['variations'] as $variation_os) {
                if (! empty($variation_os['opening_stock'])) {
                    $variation = Variation::where('product_id', $product->id)
                                    ->where('name', $variation_os['value'])
                                    ->first();
                    if (! empty($variation)) {
                        $opening_stock = [
                            'quantity' => $variation_os['opening_stock'],
                            'exp_date' => $variation_os['opening_stock_exp_date'],
                        ];

                        $total_before_tax = $total_before_tax + ($variation_os['opening_stock'] * $variation->dpp_inc_tax);
                    }

                    //Get product tax
                    $tax_percent = ! empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
                    $tax_id = ! empty($product->product_tax->id) ? $product->product_tax->id : null;

                    $item_tax = $this->productUtil->calc_percentage($variation->default_purchase_price, $tax_percent);

                    //Create purchase line
                    $transaction->purchase_lines()->create([
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                        'quantity' => $opening_stock['quantity'],
                        'item_tax' => $item_tax,
                        'tax_id' => $tax_id,
                        'purchase_price' => $variation->default_purchase_price,
                        'purchase_price_inc_tax' => $variation->dpp_inc_tax,
                        'exp_date' => ! empty($opening_stock['exp_date']) ? $opening_stock['exp_date'] : null,
                    ]);
                    //Update variation location details
                    $this->productUtil->updateProductQuantity($location_id, $product->id, $variation->id, $opening_stock['quantity']);
                }
            }

            $transaction->total_before_tax = $total_before_tax;
            $transaction->final_total = $total_before_tax;
            $transaction->save();
        }
    }

    private function rackDetails($rack_value, $row_value, $position_value, $business_id, $product_id, $row_no)
    {
        if (! empty($rack_value) || ! empty($row_value) || ! empty($position_value)) {
            $locations = BusinessLocation::forDropdown($business_id);
            $loc_count = count($locations);

            $racks = explode('|', $rack_value);
            $rows = explode('|', $row_value);
            $position = explode('|', $position_value);

            if (count($racks) > $loc_count) {
                $error_msg = "Invalid value for RACK in row no. $row_no";
                throw new \Exception($error_msg);
            }

            if (count($rows) > $loc_count) {
                $error_msg = "Invalid value for ROW in row no. $row_no";
                throw new \Exception($error_msg);
            }

            if (count($position) > $loc_count) {
                $error_msg = "Invalid value for POSITION in row no. $row_no";
                throw new \Exception($error_msg);
            }

            $rack_details = [];
            $counter = 0;
            foreach ($locations as $key => $value) {
                $rack_details[$key]['rack'] = isset($racks[$counter]) ? $racks[$counter] : '';
                $rack_details[$key]['row'] = isset($rows[$counter]) ? $rows[$counter] : '';
                $rack_details[$key]['position'] = isset($position[$counter]) ? $position[$counter] : '';
                $counter += 1;
            }

            if (! empty($rack_details)) {
                $this->productUtil->addRackDetails($business_id, $product_id, $rack_details);
            }
        }
    }
}
