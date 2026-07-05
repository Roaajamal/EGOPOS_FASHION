@extends('layouts.app')
@section('title', __('lang_v1.import_sales'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.import_sales')</h1>
</section>

<!-- Main content -->
<section class="content">
    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    @if(!empty($notification['msg']))
                        {{$notification['msg']}}
                    @elseif(session('notification.msg'))
                        {{ session('notification.msg') }}
                    @endif
                </div>
            </div>  
        </div>     
    @endif
    <div class="row">
        <div class="col-md-12">
            @component('components.widget')
                {!! Form::open(['url' => action([\App\Http\Controllers\ImportSalesController::class, 'preview']), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                    <div class="row">
                        <div class="col-sm-6">
                        <div class="col-sm-8">
                            <div class="form-group">
                                {!! Form::label('name', __( 'product.file_to_import' ) . ':') !!}
                                {!! Form::file('sales', ['required' => 'required']); !!}
                              </div>
                        </div>
                        <div class="col-sm-4">
                        <br>
                            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('lang_v1.upload_and_review')</button>
                        </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <br>
                            <a href="{{ url('import-sales/template') }}" class="tw-dw-btn tw-dw-btn-success tw-text-white"><i class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
                        </div>
                    </div>

                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['title' => __('lang_v1.instructions')])
            <table class="table table-condensed">
                <tr>
                    <td>1.</td>
                    <td>@lang('lang_v1.upload_data_in_excel_format')</td>
                </tr>
                <tr>
                    <td>2.</td>
                    <td>@lang('lang_v1.choose_location_and_group_by')</td>
                </tr>
                <tr>
                    <td>3.</td>
                    <td>@lang('lang_v1.map_columns_with_respective_sales_fields')</td>
                </tr>
                <tr>
                    <td>4.</td>
                    <td>
                        <table class="table table-striped table-slim">
                            <tr>
                                <th>@lang('lang_v1.importable_fields')</th>
                                <th>@lang('lang_v1.instructions')</th>
                            </tr>
                            @foreach($import_fields as $key => $value)
                                <tr>
                                    <td>
                                        {{$value['label']}}
                                    </td>
                                    <td>
                                        <small>{{$value['instruction'] ?? ''}}</small>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>
            @endcomponent
        </div>
    </div>
    {{-- 🆕 تبويب: المبيعات المستوردة (كل عملية استيراد بصف واحد: الوقت + عدد الفواتير + الإجمالي + فحص) --}}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['title' => 'المبيعات المستوردة'])
            <div style="background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;padding:10px 14px;color:#0f766e;font-size:13px;margin-bottom:12px;">
                <i class="fas fa-lightbulb"></i> كل عملية استيراد تظهر بصفٍّ واحد: وقتها وعدد فواتيرها وإجمالي مبالغها — اضغط <b>فحص</b> لعرض أرقام فواتيرها.
            </div>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><i class="far fa-clock"></i> وقت الاستيراد</th>
                        <th>@lang('business.created_by')</th>
                        <th>عدد الفواتير</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($imported_sales_array as $key => $value)
                        <tr>
                            <td>{{$key}}</td>
                            <td>{{@format_datetime($value['import_time'])}}</td>
                            <td>{{$value['created_by']}}</td>
                            <td><span class="label label-info" style="font-size:13px;">{{ $value['count'] ?? count($value['invoices']) }} فاتورة</span></td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm ego-inspect-import"
                                    data-batch="{{ $key }}"
                                    data-time="{{ @format_datetime($value['import_time']) }}"
                                    data-count="{{ $value['count'] ?? count($value['invoices']) }}"><i class="fa fa-search"></i> فحص</button>
                                @can('sell.delete')
                                    <a href="{{action([\App\Http\Controllers\ImportSalesController::class, 'revertSaleImport'], $key)}}" class="btn btn-danger btn-sm revert_import"><i class="fas fa-undo"></i> @lang('lang_v1.revert_import')</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">لا توجد مبيعات مستوردة بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
            @endcomponent
        </div>
    </div>
</section>

{{-- 🆕 نافذة فحص فواتير عملية الاستيراد --}}
<div class="modal fade" id="ego_import_inspect_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-search"></i> فواتير عملية الاستيراد <span id="ego_imp_batch"></span></h4>
            </div>
            <div class="modal-body">
                <p style="color:#64748b"><i class="far fa-clock"></i> وقت الاستيراد: <b id="ego_imp_time"></b> — عدد الفواتير: <b id="ego_imp_count"></b></p>
                <div id="ego_imp_body">
                    <div class="text-center text-muted" style="padding:20px"><i class="fas fa-spinner fa-spin"></i> جارٍ التحميل…</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
@stop
@section('javascript')
<script type="text/javascript">
    // 🆕 فحص عملية استيراد: جدول لكل فاتورة بأصنافها وإجماليها وطريقة دفعها
    function egoEsc(s){ return $('<div>').text(s == null ? '' : s).html(); }
    $(document).on('click', '.ego-inspect-import', function(){
        var batch = $(this).data('batch');
        $('#ego_imp_batch').text('#' + batch);
        $('#ego_imp_time').text($(this).data('time'));
        $('#ego_imp_count').text($(this).data('count'));
        $('#ego_imp_body').html('<div class="text-center text-muted" style="padding:20px"><i class="fas fa-spinner fa-spin"></i> جارٍ التحميل…</div>');
        $('#ego_import_inspect_modal').modal('show');
        $.get("{{ url('import-sales/batch') }}/" + batch + "/items", function(r){
            if (!r || !r.success) { $('#ego_imp_body').html('<div class="text-danger text-center">تعذّر جلب البيانات</div>'); return; }
            var html = '';
            (r.invoices || []).forEach(function(inv){
                html += '<div style="border:1px solid #e2e8f0;border-radius:10px;margin-bottom:14px;overflow:hidden;">';
                html += '<div style="background:#f0fdfa;padding:8px 12px;font-weight:700;color:#0f766e;display:flex;flex-wrap:wrap;gap:14px;">'
                    + '<span><i class="fas fa-file-invoice"></i> فاتورة: ' + egoEsc(inv.invoice_no) + '</span>'
                    + '<span>الإجمالي: <b>' + egoEsc(inv.total) + '</b></span>'
                    + '<span>طريقة الدفع: <b>' + egoEsc(inv.payment) + '</b></span>'
                    + '</div>';
                html += '<table class="table table-bordered table-striped" style="margin:0;font-size:13px;">'
                    + '<thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>المجموع</th></tr></thead><tbody>';
                if ((inv.items || []).length === 0) {
                    html += '<tr><td colspan="4" class="text-center text-muted">لا أصناف</td></tr>';
                } else {
                    inv.items.forEach(function(it){
                        html += '<tr><td>' + egoEsc(it.product) + '</td><td>' + egoEsc(it.qty) + '</td><td>' + egoEsc(it.price) + '</td><td>' + egoEsc(it.subtotal) + '</td></tr>';
                    });
                }
                html += '</tbody></table></div>';
            });
            $('#ego_imp_body').html(html || '<div class="text-muted text-center">لا توجد فواتير</div>');
        }).fail(function(){ $('#ego_imp_body').html('<div class="text-danger text-center">تعذّر جلب البيانات</div>'); });
    });

    $(document).on('click', 'a.revert_import', function(e){
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                window.location = $(this).attr('href');
            } else {
                return false;
            }
        });
    });
</script>
@endsection