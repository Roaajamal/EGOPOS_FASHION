<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    /**
     * تحديد إذا كان المستخدم مخول لاستخدام هذا الـ Request
     */
    public function authorize()
    {
        return auth()->user()->can('stock_adjustment.create');
    }

    /**
     * قواعد التحقق من صحة البيانات
     */
    public function rules()
    {
        return [
            'location_id' => 'required|exists:business_locations,id',
            'transaction_date' => 'required|date',
            'adjustment_type' => 'required|in:normal,abnormal,inventory',
            'products' => 'required|array|min:1',
            'products.*.variation_id' => 'required|exists:variations,id',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'nullable|numeric|min:0',
            'final_total' => 'required|numeric|min:0',
            'total_amount_recovered' => 'nullable|numeric|min:0',
            'additional_notes' => 'nullable|string|max:1000',
            'ref_no' => 'nullable|string|max:50',
            'is_last_chunk' => 'boolean'
        ];
    }

    /**
     * رسائل الخطأ المخصصة
     */
    public function messages()
    {
        return [
            'location_id.required' => 'حقل الموقع مطلوب',
            'location_id.exists' => 'الموقع المحدد غير موجود',
            'transaction_date.required' => 'حقل التاريخ مطلوب',
            'transaction_date.date' => 'صيغة التاريخ غير صحيحة',
            'adjustment_type.required' => 'نوع التسوية مطلوب',
            'adjustment_type.in' => 'نوع التسوية يجب أن يكون normal أو abnormal',
            'products.required' => 'يجب إضافة منتج واحد على الأقل',
            'products.array' => 'صيغة المنتجات غير صحيحة',
            'products.min' => 'يجب إضافة منتج واحد على الأقل',
            'products.*.variation_id.required' => 'معرف الـ variation مطلوب',
            'products.*.variation_id.exists' => 'الـ variation غير موجود',
            'products.*.quantity.required' => 'الكمية مطلوبة',
            'products.*.quantity.numeric' => 'الكمية يجب أن تكون رقماً',
            'products.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر',
            'products.*.unit_price.numeric' => 'السعر يجب أن يكون رقماً',
            'final_total.required' => 'الإجمالي النهائي مطلوب',
            'final_total.numeric' => 'الإجمالي النهائي يجب أن يكون رقماً',
            'final_total.min' => 'الإجمالي النهائي يجب أن يكون أكبر من صفر'
        ];
    }

    /**
     * تجهيز البيانات للتحقق (اختياري)
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_last_chunk' => filter_var($this->is_last_chunk, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}