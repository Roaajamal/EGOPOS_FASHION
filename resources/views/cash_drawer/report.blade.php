{{-- resources/views/cash_drawer/report.blade.php --}}
@extends('layouts.app')
@section('title', __('lang_v1.cash_drawer_report'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.cash_drawer_report')
        <small></small>
    </h1>
</section>

<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, [
                            'class'       => 'form-control select2',
                            'style'       => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
    <div class="form-group">
        {!! Form::label('user_id', __('lang_v1.added_by') . ':') !!}
        {!! Form::select('user_id', $users, null, [
            'class'       => 'form-control select2',
            'style'       => 'width:100%',
            'id'          => 'user_id', 
            'placeholder' => __('lang_v1.all'),
        ]) !!}
    </div>
</div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('drawer_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, [
                            'placeholder' => __('lang_v1.select_a_date_range'),
                            'class'       => 'form-control',
                            'id'          => 'drawer_date_filter',
                            'readonly'
                        ]) !!}
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.cash_drawer_report')])

        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="cash_drawer_table">
                <thead>
    <tr>
        
        <th>@lang('messages.date')</th>
        <th>@lang('lang_v1.added_by')</th>
        <th>@lang('business.location')</th>
        <th>@lang('lang_v1.reason')</th>    
    </tr>
</thead>
            </table>
        </div>

    @endcomponent

</section>

@endsection

@section('javascript')
<script>
$(document).ready(function () {

  var start = moment().startOf('day');
    var end = moment().endOf('day');

    // 2. تعريف الـ daterangepicker لمرة واحدة فقط وبإعدادات كاملة
    if ($('#drawer_date_filter').length == 1) {
        $('#drawer_date_filter').daterangepicker(
            _.extend({}, dateRangeSettings, {
                timePicker: true,
                timePicker24Hour: true,
                startDate: start,
                endDate: end,
                locale: { format: moment_date_format + ' HH:mm' }
            }),
            function(start, end) {
                // ✅ تعديل: ضبط الوقت المختار ليكون اليوم كاملاً من بدايته لنهايته
                var report_start = start.startOf('day');
                var report_end = end.endOf('day');

                // تحديث النص الظاهر في الحقل ليراه المستخدم بصيغة واضحة
                $('#drawer_date_filter').val(
                    report_start.format(moment_date_format + ' HH:mm') + ' ~ ' + 
                    report_end.format(moment_date_format + ' HH:mm')
                );
                
                // ✅ تعديل جوهري: تحديث قيم الـ picker الداخلية لضمان إرسالها لـ Ajax بالوقت الجديد
                var picker = $('#drawer_date_filter').data('daterangepicker');
                picker.startDate = report_start;
                picker.endDate = report_end;

                if (typeof cash_drawer_table !== 'undefined') {
                    cash_drawer_table.ajax.reload();
                }
            }
        );

        // ضبط القيمة الظاهرة في الحقل النصي عند تحميل الصفحة لأول مرة (تاريخ اليوم كاملاً)
        $('#drawer_date_filter').val(
            start.format(moment_date_format + ' HH:mm') + ' ~ ' +
            end.format(moment_date_format + ' HH:mm')
        );
    }

    // DataTable
    var cash_drawer_table = $('#cash_drawer_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("cash-drawer.report") }}',
            data: function(d) {
                d.location_id = $('#location_id').val();
                d.user_id     = $('#user_id').val();

            // ✅ فلتر التاريخ
           var picker = $('#drawer_date_filter').data('daterangepicker');
                // هنا نضمن إرسال التاريخ للسيرفر حتى لو لم يقم المستخدم بتغييره
                if (picker && $('#drawer_date_filter').val() !== '') {
                    d.start_date = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
                    d.end_date   = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
                }
        }
    },
        columns: [
  
    { data: 'created_at', name: 'created_at' }, 
    { data: 'user_name', name: 'u.first_name' }, 
    { data: 'location_name', name: 'bl.name' },
    { data: 'reason', name: 'reason' }, 
],
        order: [[1, 'desc']],
    });
    // ── فلتر الفرع ────────────────────────────────────────
    $(document).on('change', '#location_id, #user_id', function() {
    cash_drawer_table.ajax.reload();
});

    

});
</script>
@endsection