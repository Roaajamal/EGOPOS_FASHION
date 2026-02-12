<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportSettingsController extends Controller
{
   public function saveColumnSettings(Request $request) {
    $settings = $request->input('settings'); // مصفوفة تحتوي على [report_key][column_key] => [role_ids]

    foreach ($settings as $report_key => $columns) {
        foreach ($columns as $column_key => $role_ids) {
            \DB::table('report_column_settings')->updateOrInsert(
                ['report_key' => $report_key, 'column_key' => $column_key],
                [
                    'role_ids' => json_encode($role_ids),
                    'updated_at' => now()
                ]
            );
        }
    }
    return redirect()->back()->with('status', ['success' => true, 'msg' => 'تم تحديث الصلاحيات بنجاح']);
}
}
