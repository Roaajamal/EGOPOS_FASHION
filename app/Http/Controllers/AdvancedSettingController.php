<?php

namespace App\Http\Controllers;
use App\Business;

use Illuminate\Http\Request;

class AdvancedSettingController extends Controller
{public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();
        
        // جلب الإعدادات الحالية
        $pos_settings = json_decode($business->pos_settings, true);

        return view('advanced_settings.index', compact('pos_settings'));
    }

    /**
     * حفظ الإعدادات
     */
    public function update(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $business = Business::findOrFail($business_id);
            
            $pos_settings = json_decode($business->pos_settings, true);
            
            // تحديث خيار حذف المسودات
            $pos_settings['delete_draft_on_close'] = $request->has('delete_draft_on_close') ? 1 : 0;
            
            $business->pos_settings = json_encode($pos_settings);
            $business->save();

            $output = ['success' => 1, 'msg' => 'تم تحديث الإعدادات بنجاح'];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => 'حدث خطأ ما'];
        }

        return redirect()->back()->with('status', $output);
    }
}
