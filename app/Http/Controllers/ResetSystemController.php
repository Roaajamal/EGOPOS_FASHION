<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetSystemController extends Controller
{
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $business_locations = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->select('id', 'name as location_name')
            ->get();

        return view('reset_system.index', compact('business_locations'));
    }

  public function resetData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->input('location_id'); 

        if (empty($business_id)) return "خطأ في الجلسة";

        $target_locations = ($location_id == 'all') 
            ? DB::table('business_locations')->where('business_id', $business_id)->pluck('id')->toArray() 
            : [intval($location_id)];

        $sqlContent = "-- نسخة احتياطية شاملة لنظام POS \n";
        $sqlContent .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        DB::beginTransaction();
        try {
            Schema::disableForeignKeyConstraints();

            // أ- جلب معرفات المنتجات المتأثرة أولاً
            $loc_table = Schema::hasTable('product_locations') ? 'product_locations' : 'product_location';
            $product_ids = DB::table($loc_table)->whereIn('location_id', $target_locations)->pluck('product_id')->unique()->toArray();

            // 1. استخراج المنتجات (الأب) - يجب أن يكون أولاً في ملف SQL
            if (!empty($product_ids) && ($request->has('reset_products') || $request->has('reset_qty'))) {
                $this->addToBackup($sqlContent, 'products', DB::table('products')->whereIn('id', $product_ids)->get());
                $this->addToBackup($sqlContent, 'product_variations', DB::table('product_variations')->whereIn('product_id', $product_ids)->get());
                $this->addToBackup($sqlContent, 'variations', DB::table('variations')->whereIn('product_id', $product_ids)->get());
                $this->addToBackup($sqlContent, $loc_table, DB::table($loc_table)->whereIn('product_id', $product_ids)->get());
                $this->addToBackup($sqlContent, 'variation_location_details', DB::table('variation_location_details')->whereIn('location_id', $target_locations)->get());
            }

            // 2. استخراج المبيعات 
            if ($request->has('reset_sales')) {
                $trans_query = DB::table('transactions')->where('business_id', $business_id)->whereIn('location_id', $target_locations);
                $trans_rows = $trans_query->get();
                $trans_ids = $trans_rows->pluck('id')->toArray();
                
                
                if ($request->has('reset_sales')) {
                $scheme_ids = DB::table('business_locations')
                    ->whereIn('id', $target_locations)
                   ->pluck('invoice_scheme_id')
                   ->toArray();

               if (!empty($scheme_ids)) {
              DB::table('invoice_schemes')
              ->whereIn('id', $scheme_ids)
               ->update(['invoice_count' => 0]);
           }
             }
                
                if (!empty($trans_ids)) {
                    $this->addToBackup($sqlContent, 'transactions', $trans_rows);
                    $this->addToBackup($sqlContent, 'transaction_sell_lines', DB::table('transaction_sell_lines')->whereIn('transaction_id', $trans_ids)->get());
                }
            }

            // --- الآن البدء بعملية الحذف الفعلي من قاعدة البيانات ---
            
            if ($request->has('reset_sales') && !empty($trans_ids)) {
                DB::table('transaction_sell_lines')->whereIn('transaction_id', $trans_ids)->delete();
                DB::table('transaction_payments')->whereIn('transaction_id', $trans_ids)->delete();
                $trans_query->delete();
            }

            if ($request->has('reset_products') && !empty($product_ids)) {
                foreach ($product_ids as $p_id) {
                    // حذف فقط إذا لم يكن للمنتج فروع أخرى (منطق المنتج الوحيد)
                    $exists_elsewhere = DB::table($loc_table)->where('product_id', $p_id)->whereNotIn('location_id', $target_locations)->exists();
                    if (!$exists_elsewhere) {
                        DB::table('purchase_lines')->where('product_id', $p_id)->delete();
                        DB::table('product_variations')->where('product_id', $p_id)->delete();
                        DB::table('variations')->where('product_id', $p_id)->delete();
                        DB::table($loc_table)->where('product_id', $p_id)->delete();
                        DB::table('variation_location_details')->where('product_id', $p_id)->delete();
                        DB::table('products')->where('id', $p_id)->delete();
                    } else {
                        // إذا كان مشتركاً، احذف فقط ما يخص الفرع الحالي
                        DB::table($loc_table)->where('product_id', $p_id)->whereIn('location_id', $target_locations)->delete();
                        DB::table('variation_location_details')->where('product_id', $p_id)->whereIn('location_id', $target_locations)->delete();
                    }
                }
            } elseif ($request->has('reset_qty')) {
                DB::table('variation_location_details')->whereIn('location_id', $target_locations)->delete();
            }

            $sqlContent .= "\nSET FOREIGN_KEY_CHECKS = 1;";
            DB::commit();
            Schema::enableForeignKeyConstraints();

            return response()->streamDownload(function () use ($sqlContent) { echo $sqlContent; }, "final_reset_" . date('Y-m-d') . ".sql");

        } catch (\Exception $e) {
            DB::rollBack();
            return "خطأ: " . $e->getMessage();
        }
    }

    private function addToBackup(&$sqlContent, $tableName, $rows) {
        if ($rows->isEmpty()) return;
        $sqlContent .= "-- داتا جدول: $tableName \n";
        foreach ($rows as $row) {
            $array = (array)$row;
            $cols = implode("`, `", array_keys($array));
            $vals = implode(", ", array_map(function($v) {
                return (is_null($v) || $v === '') ? "NULL" : "'" . addslashes($v) . "'";
            }, array_values($array)));
            $sqlContent .= "REPLACE INTO `$tableName` (`$cols`) VALUES ($vals);\n";
        }
        $sqlContent .= "\n";
    }
}
