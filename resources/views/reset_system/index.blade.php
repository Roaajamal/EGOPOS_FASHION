@extends('layouts.app')
@section('title', 'تصفير بيانات النظام')

@section('content')
<section class="content">
    <div class="box box-primary">
        <div class="box-header">
            <h3 class="box-title">تصفير بيانات الفرع والشركة</h3>
        </div>
        <div class="box-body">
            <form action="{{ action([\App\Http\Controllers\ResetSystemController::class, 'resetData']) }}" method="POST" id="reset_form">
                @csrf

                <div class="form-group">
                    <label for="location_id">اختر الفرع:</label>
                    <select name="location_id" id="location_id" class="form-control" required>
                        <option value="all">-- جميع الفروع --</option>
                        @foreach($business_locations as $location)
                            <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>خيارات التصفير:</label><br>
                    
                    <label>
                        <input type="checkbox" name="reset_sales" id="reset_sales" value="1"> 
                        تصفير المبيعات والمشتريات
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="reset_qty" id="reset_qty" value="1"> 
                        تصفير كميات المخزون
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="reset_products" id="reset_products" value="1"> 
                        <b>حذف المنتجات (تصفير كامل )</b>
                    </label>
                </div>

                <button type="submit" class="btn btn-danger" onclick="return confirm('تنبيه: سيتم حذف البيانات المحددة نهائياً وتحميل نسخة احتياطية. هل أنت متأكد؟')">
                    تنفيذ عملية التصفير
                </button>
            </form>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkProducts = document.getElementById('reset_products');
        const checkSales = document.getElementById('reset_sales');
        const checkQty = document.getElementById('reset_qty');
        const form = document.getElementById('reset_form');

        // عند تغيير حالة "تصفير المنتجات"
        checkProducts.addEventListener('change', function() {
            if(this.checked) {
                checkSales.checked = true;
                checkQty.checked = true;
                
                // نستخدم التظليل البصري بدلاً من الـ disabled لضمان إرسال القيمة
                checkSales.parentElement.style.opacity = "0.5";
                checkQty.parentElement.style.opacity = "0.5";
                
                // منع المستخدم من إلغاء تحديدهم يدوياً طالما المنتجات مختارة
                checkSales.onclick = function() { return false; };
                checkQty.onclick = function() { return false; };
            } else {
                checkSales.parentElement.style.opacity = "1";
                checkQty.parentElement.style.opacity = "1";
                checkSales.onclick = null;
                checkQty.onclick = null;
            }
        });
    });
</script>
@endsection