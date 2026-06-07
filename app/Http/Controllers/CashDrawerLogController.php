<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// تعديل: حذفنا Models لأن نظامك لا يدعمها
use App\CashDrawerLog; 
use App\User;
use App\BusinessLocation;
use Yajra\DataTables\Facades\DataTables;
use DB;

class CashDrawerLogController extends Controller
{
    // صفحة التقرير
    public function index(Request $request)
    {
         if (!auth()->user()->can('cash_drawer.view') ) {
        abort(403, 'Unauthorized action.');
        }
   
        $business_id = auth()->user()->business_id;

        if ($request->ajax()) {
            // ملاحظة: تأكد أنك أنشأت موديل اسمه App\CashDrawerLog أو استخدم DB::table
            $query = DB::table('cash_drawer_logs as cdl')
                ->join('users as u', 'cdl.user_id', '=', 'u.id')
                ->join('business_locations as bl', 'cdl.location_id', '=', 'bl.id')
                ->where('cdl.business_id', $business_id)
                ->select([
                    'cdl.id',
                    'cdl.created_at',
                    'cdl.open_type',
                    'cdl.reason',
                    DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user_name"),
                    'bl.name as location_name'
                ]);

            if ($request->filled('user_id')) {
    // نستخدم cdl.user_id لضمان عدم الاختلاط مع أي جداول أخرى
    $query->where('cdl.user_id', $request->user_id);
}

            // ✅ فلتر الفرع
   // ✅ تصحيح فلتر الفرع
if ($request->filled('location_id')) {
    // استخدم cdl.location_id بدلاً من transactions
    $query->where('cdl.location_id', $request->input('location_id'));
}

// ✅ تصحيح فلتر التاريخ
if ($request->filled('start_date') && $request->filled('end_date')) {
    // استخدم cdl.created_at بدلاً من transactions.transaction_date
    $query->whereBetween('cdl.created_at', [
        $request->input('start_date'),
        $request->input('end_date'),
    ]);
}
            return DataTables::of($query)->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
       $users = User::where('business_id', $business_id)
    ->select('id', DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"))
    ->pluck('full_name', 'id');

        return view('cash_drawer.report', compact('business_locations', 'users'));
    }

    // وظيفة تسجيل فتح الدرج
    public function storeDrawerLog(Request $request) 
    {
        if (!auth()->user()->can('cash_drawer.create') ) {
        abort(403, 'Unauthorized action.');
        }
         
        try {
            $request->validate([
                'location_id' => 'required',
                'reason'      => 'required|string|max:255',
            ]);

            $business_id = $request->session()->get('user.business_id');
            $user_id     = auth()->user()->id;

            DB::table('cash_drawer_logs')->insert([
                'business_id'    => $business_id,
                'location_id'    => $request->location_id,
                'user_id'        => $user_id,
                'transaction_id' => $request->transaction_id ?? null,
                'open_type'      => $request->open_type ?? 'manual',
                'reason'         => $request->reason,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return response()->json([
                'success' => true, 
                'message' => __('cash_drawer.log_success')
            ]);

        } catch (\Exception $e) {
            \Log::error("Cash Drawer Log Error: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }
}