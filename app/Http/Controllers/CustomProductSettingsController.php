<?php

namespace App\Http\Controllers;

use App\Business;
use Illuminate\Http\Request;
use App\Utils\BusinessUtil;
use App\TaxRate;
use App\Unit;
use App\BusinessLocation;
use DB;

class CustomProductSettingsController extends Controller
{
    protected $businessUtil;

    public function __construct(BusinessUtil $businessUtil)
    {
        $this->businessUtil = $businessUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();

        $tax_details = TaxRate::forBusinessDropdown($business_id);
        $taxes = $tax_details['tax_rates']; 

        $barcode_types = $this->businessUtil->barcode_types();
        $barcode_default = $this->businessUtil->barcode_default();

        $units_dropdown = Unit::forDropdown($business_id, true);
        $business_locations = BusinessLocation::forDropdown($business_id);

        $custom_settings = !empty($business->custom_product_settings) ? $business->custom_product_settings : [];

        return view('custom_settings.index', compact(
            'business', 
            'custom_settings', 
            'taxes', 
            'units_dropdown', 
            'business_locations', 
            'barcode_types',
            'barcode_default'
        ));
    }

    public function update(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $business = Business::findOrFail($business_id);

            // جلب الإعدادات الحالية
            $current_settings = is_string($business->custom_product_settings) 
                ? json_decode($business->custom_product_settings, true) 
                : ($business->custom_product_settings ?? []);

            // جلب المدخلات الجديدة
            $new_settings = $request->input('custom_product_settings', []);

            // دمج الإعدادات للحفاظ على الحقول التي لم تظهر في الفورم
            $updated_settings = array_merge($current_settings, $new_settings);

            $business->custom_product_settings = $updated_settings;
            $business->save();

            $request->session()->put('business', $business);

            $output = ['success' => 1, 'msg' => 'تم تحديث الإعدادات بنجاح'];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => 'حدث خطأ ما!'];
        }

        return redirect()->back()->with('status', $output);
    }
}