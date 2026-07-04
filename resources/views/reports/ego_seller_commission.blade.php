@extends('layouts.app')
@section('title', 'عمولات البائعين')

@section('content')
<style>
    /* 🆕 تنسيق أزرار التصدير (DataTables) — مُحدِّدات عامة لتُطبَّق أينما كانت الأزرار */
    .dt-buttons{display:inline-flex !important;gap:8px;flex-wrap:wrap;float:none !important;margin-bottom:12px}
    .dt-button, button.dt-button, .dt-buttons .btn, .buttons-csv, .buttons-excel, .buttons-print{
        border:1.5px solid #e2e8f0 !important;background:#fff !important;color:#334155 !important;
        border-radius:10px !important;padding:8px 16px !important;font-weight:700 !important;font-size:13px !important;
        box-shadow:0 1px 3px rgba(2,6,23,.06) !important;display:inline-flex !important;align-items:center;gap:6px;margin:0 !important;line-height:1.4 !important
    }
    .dt-button:hover, .dt-buttons .btn:hover{background:#f0fdfa !important;border-color:#0d9488 !important;color:#0d9488 !important}
    .dt-button i, .dt-buttons .btn i{color:#0d9488 !important}
    #ego_commission_buttons{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
</style>
<section class="content-header">
    <h1><i class="fas fa-user-tag" style="color:#0d9488"></i> تقرير عمولات البائعين (لكل منتج)</h1>
</section>

<section class="content">
    <div class="box box-solid">
        <div class="box-body">
            <form method="GET" action="{{ route('reports.ego_seller_commission') }}" class="form-inline" style="margin-bottom:18px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                <div class="form-group">
                    <label>من تاريخ</label>
                    <input type="date" name="start_date" value="{{ $start }}" class="form-control">
                </div>
                <div class="form-group">
                    <label>إلى تاريخ</label>
                    <input type="date" name="end_date" value="{{ $end }}" class="form-control">
                </div>
                <div class="form-group">
                    <label>البائع</label>
                    {!! Form::select('seller_id', $sellers, $seller_id, ['class' => 'form-control select2', 'placeholder' => 'كل البائعين', 'style' => 'min-width:180px']) !!}
                </div>
                <button type="submit" class="btn" style="background:#0d9488;color:#fff;font-weight:700"><i class="fas fa-search"></i> عرض</button>
            </form>

            <div id="ego_commission_buttons" style="margin-bottom:10px"></div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="ego_commission_table">
                    <thead>
                        <tr style="background:#0d9488;color:#fff">
                            <th>اسم البائع</th>
                            <th>اسم المنتج</th>
                            <th>باركود</th>
                            <th>رقم الفاتورة</th>
                            <th>التاريخ</th>
                            <th class="text-center">إجمالي السعر</th>
                            <th class="text-center">إجمالي الكمية</th>
                            <th class="text-center">نسبة العمولة</th>
                            <th class="text-center">قيمة العمولة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr>
                                <td style="font-weight:700">{{ $r->seller_name }}</td>
                                <td>{{ $r->product_name }}</td>
                                <td>{{ $r->barcode }}</td>
                                <td>{{ $r->invoice_no }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->transaction_date)->format('d-m-Y h:i A') }}</td>
                                <td class="text-center">@format_currency($r->line_total)</td>
                                <td class="text-center">{{ (float) $r->qty }}</td>
                                <td class="text-center">{{ (float) $r->cmmsn_percent }}%</td>
                                <td class="text-center" style="font-weight:800;color:#0d9488">@format_currency($r->commission)</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center" style="padding:24px;color:#94a3b8">لا توجد بيانات في هذه الفترة</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($rows))
                        <tfoot>
                            <tr style="font-weight:800;background:#f1f5f9">
                                <td colspan="5">الإجمالي</td>
                                <td class="text-center">@format_currency($grand_value)</td>
                                <td class="text-center">{{ (float) $grand_qty }}</td>
                                <td></td>
                                <td class="text-center" style="color:#0d9488">@format_currency($grand_commission)</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            <p class="help-block">العمولة لكل سطر = قيمة المنتج (شامل الضريبة) × نسبة عمولة بائعه — للفواتير المكتملة ضمن الفترة المحددة.</p>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function () {
        if ($.fn.DataTable) {
            var t = $('#ego_commission_table').DataTable({
                dom: 'Bfrtip',
                paging: false,
                info: false,
                order: [],
                buttons: [
                    { extend: 'csv',   text: '<i class="fa fa-file-csv"></i> تصدير إلى CSV',   className: 'btn btn-default btn-sm' },
                    { extend: 'excel', text: '<i class="fa fa-file-excel"></i> تصدير إلى Excel', className: 'btn btn-default btn-sm', title: 'عمولات البائعين' },
                    { extend: 'print', text: '<i class="fa fa-print"></i> طباعة / PDF',         className: 'btn btn-default btn-sm', title: 'عمولات البائعين' }
                ]
            });
            t.buttons().container().appendTo('#ego_commission_buttons');
        }
    });
</script>
@endsection
