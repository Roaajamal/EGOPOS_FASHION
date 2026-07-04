@extends('layouts.app')
@section('title', __('lang_v1.preview_imported_sales'))

@section('content')

<style>
    .ego-imp-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:#fff;border:1px solid #eef0f4;border-radius:16px;padding:14px 18px;box-shadow:0 2px 14px rgba(15,23,42,.05);margin:4px 0 18px;}
    .ego-imp-head .ico{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0d9488,#0891b2);color:#fff;font-size:22px;box-shadow:0 8px 20px rgba(13,148,136,.30);}
    .ego-imp-head h2{margin:0;font-weight:800;color:#1e293b;font-size:20px;}
    .ego-imp-head p{margin:0;color:#94a3b8;font-size:13px;}
    .ego-imp-card{background:#fff;border:1px solid #eef0f4;border-radius:16px;box-shadow:0 2px 14px rgba(15,23,42,.05);margin-bottom:18px;}
    .ego-imp-card-head{padding:14px 18px;border-bottom:1px solid #f1f3f7;display:flex;align-items:center;gap:10px;}
    .ego-imp-card-head i{color:#0d9488;font-size:18px;}
    .ego-imp-card-head h4{margin:0;font-size:16px;font-weight:700;color:#334155;}
    .ego-imp-card-body{padding:18px;}
    .ego-imp-tip{background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;padding:10px 14px;color:#0f766e;font-size:13px;margin-bottom:14px;}
    .ego-imp-count{background:#eef2ff;color:#4f46e5;font-weight:800;border-radius:8px;padding:4px 10px;font-size:13px;}
    .ego-imp-scroll{max-height:460px;overflow:auto;border:1px solid #eef0f4;border-radius:12px;}
    table.ego-imp-tbl{width:100%;margin:0;border-collapse:separate;border-spacing:0;font-size:13px;}
    table.ego-imp-tbl th{background:#0f766e;color:#fff;font-weight:700;padding:9px 10px;white-space:nowrap;position:sticky;top:0;z-index:3;}
    table.ego-imp-tbl td{padding:8px 10px;border-bottom:1px solid #f1f5f9;white-space:nowrap;color:#334155;}
    table.ego-imp-tbl tr.ego-map-row td{background:#f8fafc;position:sticky;top:37px;z-index:2;border-bottom:2px solid #e2e8f0;padding:8px;}
    table.ego-imp-tbl tbody tr:nth-child(odd) td{background:#ffffff;}
    table.ego-imp-tbl tbody tr:nth-child(even) td{background:#f9fafb;}
    table.ego-imp-tbl td.ego-rownum{font-weight:700;color:#94a3b8;text-align:center;background:#f1f5f9 !important;position:sticky;right:0;z-index:1;}
    .ego-imp-submit{background:linear-gradient(135deg,#0d9488,#0891b2);border:none;color:#fff;font-weight:800;padding:12px 28px;border-radius:12px;box-shadow:0 8px 18px rgba(13,148,136,.30);}
    .ego-imp-submit:hover{transform:translateY(-1px);color:#fff;}
</style>

<section class="content-header">
    <div class="ego-imp-head">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="ico"><i class="fas fa-file-import"></i></div>
            <div>
                <h2>@lang('lang_v1.preview_imported_sales')</h2>
                <p>راجع البيانات واربط الأعمدة بالحقول قبل الاستيراد النهائي</p>
            </div>
        </div>
        <a href="{{ url('import-sales') }}" class="tw-dw-btn tw-dw-btn-default"><i class="fas fa-arrow-right"></i> رجوع</a>
    </div>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ImportSalesController::class, 'import']), 'method' => 'post', 'id' => 'import_sale_form']) !!}
    {!! Form::hidden('file_name', $file_name); !!}

    <div class="ego-imp-card">
        <div class="ego-imp-card-head"><i class="fas fa-sliders-h"></i><h4>إعدادات الاستيراد</h4></div>
        <div class="ego-imp-card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('group_by', __('lang_v1.group_sale_line_by') . ':*') !!} @show_tooltip(__('lang_v1.group_by_tooltip'))
                        {!! Form::select('group_by', $parsed_array[0], null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('location_id', __('business.business_location') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control', 'required', 'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ego-imp-card">
        <div class="ego-imp-card-head">
            <i class="fas fa-table"></i><h4>معاينة البيانات وربط الأعمدة</h4>
            <span class="ego-imp-count" style="margin-inline-start:auto;">{{ max(count($parsed_array) - 1, 0) }} صف</span>
        </div>
        <div class="ego-imp-card-body">
            <div class="ego-imp-tip">
                <i class="fas fa-lightbulb"></i>
                في الصف المظلَّل اختر لكل عمود الحقل المقابل له، أو اتركه <b>@lang('lang_v1.skip')</b> لتجاهله. المطلوب: المنتج/الـSKU، الكمية، والسعر. <b>الإيميل واسم العميل اختياريان</b> (يُسنَد للعميل الافتراضي إن تُركا).
            </div>
            <div class="ego-imp-scroll">
                <table class="ego-imp-tbl">
                    @foreach(array_slice($parsed_array, 0, 101) as $row)
                        <tr>
                            <td class="ego-rownum">@if($loop->index > 0){{$loop->index}}@else # @endif</td>
                            @foreach($row as $k => $v)
                                @if($loop->parent->index == 0)
                                    <th>{{$v}}</th>
                                @else
                                    <td>{{$v}}</td>
                                @endif
                            @endforeach
                        </tr>
                        @if($loop->index == 0)
                            <tr class="ego-map-row">
                                <td class="ego-rownum"><i class="fas fa-link"></i></td>
                                @foreach($row as $k => $v)
                                    <td>
                                        {!! Form::select('import_fields[' . $k . ']', $import_fields, $match_array[$k], ['class' => 'form-control import_fields select2', 'placeholder' => __('lang_v1.skip'), 'style' => 'width: 100%;']); !!}
                                    </td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12" style="margin-bottom:20px;">
            <button type="submit" class="ego-imp-submit pull-right"><i class="fas fa-check"></i> @lang('messages.submit')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@stop
@section('javascript')
<script type="text/javascript">
    $(document).on('submit', 'form#import_sale_form', function(){
        var import_fields = [];

        $('.import_fields').each( function() {
            if ($(this).val()) {
                import_fields.push($(this).val());
            }
        });

        // 🆕 أُلغي شرط الإيميل/اسم العميل — لم يعودا مطلوبين
        if (import_fields.indexOf('product') == -1 && import_fields.indexOf('sku') == -1) {
            alert("{{__('lang_v1.product_name_or_sku_is_required')}}");
            return false;
        }
        if (import_fields.indexOf('quantity') == -1) {
            alert("{{__('lang_v1.quantity_is_required')}}");
            return false;
        }
        if (import_fields.indexOf('unit_price') == -1) {
            alert("{{__('lang_v1.unit_price_is_required')}}");
            return false;
        }

        if(hasDuplicates(import_fields)) {
            alert("{{__('lang_v1.cannot_select_a_field_twice')}}");
            return false;
        }

    });

    function hasDuplicates(array) {
        return (new Set(array)).size !== array.length;
    }
</script>
@endsection
