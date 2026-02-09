<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Business;
use App\BusinessLocation;

class FAWJOFatoraController extends Controller
{
    public function index(Request $request)
    {
        // الحصول على المستخدم الحالي
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول أولاً.');
        }

        // جلب الشركة الحالية للمستخدم
        $current_business_id = $user->business_id;
        
        if (!$current_business_id) {
            return redirect()->back()->with('error', 'لا توجد شركة مرتبطة بالمستخدم.');
        }

        // جلب تفاصيل الشركة الحالية فقط
        $current_business = Business::where('id', $current_business_id)
            ->where('is_active', 1)
            ->first(['id', 'name']);

        if (!$current_business) {
            return redirect()->back()->with('error', 'الشركة غير موجودة أو غير نشطة.');
        }

        // جلب فروع الشركة الحالية فقط
        $all_locations = BusinessLocation::where('business_id', $current_business_id)
            ->where('is_active', 1)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'location_id']);

        // تحديد الفرع الحالي
        $location_id = $request->get('location_id');
        
        if (!$location_id && $all_locations->isNotEmpty()) {
            $location_id = $all_locations->first()->id;
        }

        // البحث في إعدادات الفواتير للشركة الحالية
        $settings = null;
        if ($current_business_id) {
            $query = DB::table('settings_fatora')->where('business_id', $current_business_id);
            
            if ($location_id) {
                $query->where('location_id', $location_id);
            } else {
                $query->whereNull('location_id');
            }
            
            $settings = $query->first();
        }

        // إذا لم توجد إعدادات، نقوم بإنشاء سجل جديد
        if (!$settings && $current_business_id) {
            // تحقق أولاً إذا كان هناك سجل موجود
            $existing_settings = DB::table('settings_fatora')
                ->where('business_id', $current_business_id)
                ->when($location_id, function($query) use ($location_id) {
                    return $query->where('location_id', $location_id);
                }, function($query) {
                    return $query->whereNull('location_id');
                })
                ->exists();

          
            
            // إعادة جلب السجل
            $query = DB::table('settings_fatora')->where('business_id', $current_business_id);
            
            if ($location_id) {
                $query->where('location_id', $location_id);
            } else {
                $query->whereNull('location_id');
            }
            
            $settings = $query->first();
        }

        return view('fawjo.FWJO', [
            'settings'       => $settings,
            'business_id'    => $current_business_id,
            'business_name'  => $current_business->name ?? '',
            'location_id'    => $location_id,
            'all_locations'  => $all_locations
        ]);
    }

    public function store(Request $request)
    {
        // الحصول على المستخدم الحالي
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول أولاً.');
        }

        $current_business_id = $user->business_id;
        $location_id = $request->input('location_id');

        if (!$current_business_id) {
            return redirect()->back()->with('error', 'لا توجد شركة مرتبطة بالمستخدم.');
        }

        $data = $request->only([
            'client_id', 'secret_key', 'supplier_income_source',
            'tin', 'registration_name', 'crn', 'invoice_type',
            'street_name', 'building_number', 'city_name', 'city_code',
            'county', 'postal_code', 'plot_al_zone', 'vat', 'csr'
        ]);

        $data['updated_at'] = now();

        // البحث عن السجل الموجود
        $query = DB::table('settings_fatora')->where('business_id', $current_business_id);
        
        if ($location_id) {
            $query->where('location_id', $location_id);
        } else {
            $query->whereNull('location_id');
        }
        
        $existingRecord = $query->first();

        if ($existingRecord) {
            // تحديث
            DB::table('settings_fatora')
                ->where('id', $existingRecord->id)
                ->update($data);
        } else {
            // إدراج جديد
            $data['business_id'] = $current_business_id;
            $data['location_id'] = $location_id;
            $data['is_active'] = true;
            $data['created_at'] = now();
            
            DB::table('settings_fatora')->insert($data);
        }

        return redirect()->back()->with('success', 'تم حفظ الإعدادات بنجاح!');
    }
}