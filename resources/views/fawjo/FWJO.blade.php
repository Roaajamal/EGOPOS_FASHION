
@extends('layouts.app')

@section('title', 'إعدادات نظام الفوترة الأردني')
<!--    after --> 
@section('content')
<section class="content-header">
    <h1>إعدادات نظام الفوترة الوطني الأردني (JoFotara)
        <small>ربط النظام مع بوابة الفوترة الإلكترونية</small>
    </h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">معلومات الاتصال بنظام الفوترة</h3>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible" style="margin: 15px;">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="icon fa fa-check"></i> {{ session('success') }}
            </div>
        @endif

        <div class="box box-primary">
    <div class="box-body">
        <div class="row">
           

           <select class="form-control select2" onchange="window.location.href='{{ route('fawjo.settings') }}?business_id={{ $business_id }}&location_id=' + this.value">
  @foreach($all_locations as $loc)
  <option value="{{ $loc->id }}" {{ $location_id == $loc->id ? 'selected' : '' }}>
    {{ $loc->name }}
    @if(!empty($loc->location_id))
      ({{ $loc->location_id }})
    @endif
  </option>
  @endforeach
</select>
        </div>
    </div>
</div>

        <form action="{{ route('fawjo.settings.store') }}" method="POST">
            @csrf
            <input type="hidden" name="business_id" value="{{ $business_id }}">
            <input type="hidden" name="location_id" value="{{ $location_id }}">

            <div class="box-body">
                <!-- معلومات الاتصال الأساسية -->
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="text-primary"><i class="fa fa-key"></i> معلومات الاتصال (إلزامية)</h4>
                        <hr>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="client_id">Client ID <span class="text-danger">*</span></label>
                            <input type="text" name="client_id" id="client_id" class="form-control" 
                                   value="{{ $settings->client_id ?? '' }}" required>
                            <small class="text-muted">معرف العميل من بوابة JoFotara</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supplier_income_source">تسلسل مصدر الدخل (Supplier Income Source) <span class="text-danger">*</span></label>
                            <input type="text" name="supplier_income_source" id="supplier_income_source" 
                                   class="form-control" value="{{ $settings->supplier_income_source ?? '' }}" required>
                            <small class="text-muted">رقم تسلسل مصدر الدخل من نظام JoFotara (إلزامي)</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="secret_key">Secret Key <span class="text-danger">*</span></label>
                            <textarea name="secret_key" id="secret_key" class="form-control" rows="3" required>{{ $settings->secret_key ?? '' }}</textarea>
                            <small class="text-muted">المفتاح السري من بوابة JoFotara</small>
                        </div>
                    </div>
                </div>

                <!-- معلومات الشركة -->
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="text-primary"><i class="fa fa-building"></i> معلومات الشركة</h4>
                        <hr>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tin">الرقم الضريبي (TIN) <span class="text-danger">*</span></label>
                            <input type="text" name="tin" id="tin" class="form-control" 
                                   value="{{ $settings->tin ?? '' }}" required>
                            <small class="text-muted">رقم التعريف الضريبي للشركة</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="registration_name">اسم الشركة المسجل <span class="text-danger">*</span></label>
                            <input type="text" name="registration_name" id="registration_name" 
                                   class="form-control" value="{{ $settings->registration_name ?? '' }}" required>
                            <small class="text-muted">الاسم الرسمي للشركة</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="crn">رقم السجل التجاري (CRN)</label>
                            <input type="text" name="crn" id="crn" class="form-control" 
                                   value="{{ $settings->crn ?? '' }}">
                        </div>
                    </div>
                    <!--       003 -->
                     
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="invoice_type">invoice_type</label>
                            <select name="invoice_type" id="invoice_type" class="form-control">
                                <option value="">اختر نوع الضريبة</option>
                                <option value="income" {{ ($settings->invoice_type ?? '') == 'income' ? 'selected' : '' }}>دخل </option>
                                <option value="general_sales" {{ ($settings->invoice_type ?? '') == 'general_sales' ? 'selected' : '' }}>مبيعات </option>
                                <option value="special_sales" {{ ($settings->invoice_type ?? '') == 'special_sales' ? 'selected' : '' }}>خاصة </option>
                            </select>
                            
                        </div>
                    </div>
                   <!--              003 -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vat">رقم ضريبة القيمة المضافة (VAT)</label>
                            <input type="text" name="vat" id="vat" class="form-control" 
                                   value="{{ $settings->vat ?? '' }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="postal_code">الرمز البريدي</label>
                            <input type="text" name="postal_code" id="postal_code" class="form-control" 
                                   value="{{ $settings->postal_code ?? '' }}">
                        </div>
                    </div>
                </div>

                <!-- العنوان -->
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="text-primary"><i class="fa fa-map-marker"></i> عنوان الشركة</h4>
                        <hr>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="street_name">اسم الشارع</label>
                            <input type="text" name="street_name" id="street_name" class="form-control" 
                                   value="{{ $settings->street_name ?? '' }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="building_number">رقم المبنى</label>
                            <input type="text" name="building_number" id="building_number" class="form-control" 
                                   value="{{ $settings->building_number ?? '' }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="plot_al_zone">رقم القطعة/المنطقة</label>
                            <input type="text" name="plot_al_zone" id="plot_al_zone" class="form-control" 
                                   value="{{ $settings->plot_al_zone ?? '' }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="city_name">المدينة</label>
                            <input type="text" name="city_name" id="city_name" class="form-control" 
                                   value="{{ $settings->city_name ?? '' }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="city_code">رمز المدينة</label>
                            <select name="city_code" id="city_code" class="form-control">
                                <option value="">اختر المدينة</option>
                                <option value="JO-AM" {{ ($settings->city_code ?? '') == 'JO-AM' ? 'selected' : '' }}>عمان (JO-AM)</option>
                                <option value="JO-AQ" {{ ($settings->city_code ?? '') == 'JO-AQ' ? 'selected' : '' }}>العقبة (JO-AQ)</option>
                                <option value="JO-AZ" {{ ($settings->city_code ?? '') == 'JO-AZ' ? 'selected' : '' }}>الزرقاء (JO-AZ)</option>
                                <option value="JO-IR" {{ ($settings->city_code ?? '') == 'JO-IR' ? 'selected' : '' }}>إربد (JO-IR)</option>
                                <option value="JO-JA" {{ ($settings->city_code ?? '') == 'JO-JA' ? 'selected' : '' }}>جرش (JO-JA)</option>
                                <option value="JO-KA" {{ ($settings->city_code ?? '') == 'JO-KA' ? 'selected' : '' }}>الكرك (JO-KA)</option>
                                <option value="JO-MA" {{ ($settings->city_code ?? '') == 'JO-MA' ? 'selected' : '' }}>المفرق (JO-MA)</option>
                            </select>
                            <small class="text-muted">رمز المدينة حسب المعيار الأردني</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="county">المحافظة</label>
                            <input type="text" name="county" id="county" class="form-control" 
                                   value="{{ $settings->county ?? '' }}">
                        </div>
                    </div>
                </div>

                <!-- معلومات إضافية -->
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="text-primary"><i class="fa fa-file-text"></i> معلومات إضافية</h4>
                        <hr>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="csr">Certificate Signing Request (CSR)</label>
                            <textarea name="csr" id="csr" class="form-control" rows="3">{{ $settings->csr ?? '' }}</textarea>
                            <small class="text-muted">شهادة طلب التوقيع (اختياري)</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="icon fa fa-info"></i>
                            <strong>ملاحظة مهمة:</strong> 
                            الحقول المميزة بـ <span class="text-danger">*</span> إلزامية لإتمام التكامل مع نظام الفوترة الأردني.
                            يمكنك الحصول على Client ID و Secret Key و Supplier Income Source من بوابة JoFotara الإلكترونية.
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa fa-save"></i> حفظ الإعدادات
                </button>
                <a href="{{ url('/home') }}" class="btn btn-default btn-lg">
                    <i class="fa fa-arrow-left"></i> رجوع
                </a>
            </div>
        </form>
    </div>
</section>
@endsection
