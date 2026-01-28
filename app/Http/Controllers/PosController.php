<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class PosController extends Controller
{
    /**
     * 🔍 جلب منتج حسب الباركود ضمن نفس البزنس
     */
    public function getProductByBarcode(Request $request)
    {
        $barcode = $request->barcode;

        if (empty($barcode)) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ الباركود فارغ'
            ]);
        }

        // ✅ جلب رقم البزنس الحالي من جلسة المستخدم
        $business_id = $request->session()->get('user.business_id');

        if (empty($business_id)) {
            return response()->json([
                'success' => false,
                'message' => '🚫 لم يتم تحديد رقم البزنس في الجلسة'
            ]);
        }

        // ✅ البحث عن المنتج داخل نفس البزنس فقط
        $product = DB::table('products')
            ->where('business_id', $business_id)
            ->where('sku', $barcode)
            ->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'product' => $product
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => '❌ لم يتم العثور على المنتج في هذا البزنس'
        ]);
    }
}
