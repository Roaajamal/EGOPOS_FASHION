@extends('layouts.app')
@section('title', 'إعدادات إضافية')

@section('content')
<section class="content-header">
    <h1>إعدادات نقاط البيع الإضافية</h1>
</section>

<section class="content">
    <div class="box box-solid">
        {!! Form::open(['url' => action([\App\Http\Controllers\AdvancedSettingController::class, 'update']), 'method' => 'post']) !!}
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('delete_draft_on_close', 1, !empty($pos_settings['delete_draft_on_close']), ['class' => 'input-icheck']); !!} 
                                <strong>تفعيل حذف المسودات تلقائياً عند إغلاق الكاش</strong>
                            </label>
                        </div>
                        <p class="help-block">عند تفعيل هذا الخيار، سيقوم النظام تلقائياً بمسح جميع الفواتير المحفوظة كـ (مسودة) للفرع الحالي بمجرد إغلاق الصندوق.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary pull-right">حفظ التغييرات</button>
        </div>
        {!! Form::close() !!}
    </div>
</section>
@endsection