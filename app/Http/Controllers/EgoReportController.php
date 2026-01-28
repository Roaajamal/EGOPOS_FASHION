<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Transaction;
use App\BusinessLocation;
use App\User;
use App\TaxRate;
use App\Contact;
use Yajra\DataTables\Facades\DataTables;

class EgoReportController extends Controller
{
    /**
     * عرض صفحة التقرير الرئيسية
     */
    public function dailySalesReport()
    {
        // صلاحية المستخدم
        if (!auth()->user()->can('sell_report.view')) {
            abort(403, 'عذراً، ليس لديك صلاحية عرض هذا التقرير');
        }

        $business_id = request()->session()->get('user.business_id');

        // الحصول على قائمة الفروع
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        // الحصول على قائمة المستخدمين للفلتر
        $users = User::where('business_id', $business_id)
            ->where('status', 'active')
            ->pluck('username', 'id');

        return view('report.daily_sales', compact('business_locations', 'users'));
    }

    /**
     * معالجة بيانات التقرير عبر Ajax
     */
    /**
     * معالجة بيانات التقرير عبر Ajax
     */
    /**
     * معالجة بيانات التقرير عبر Ajax
     */
    /**
     * معالجة بيانات التقرير عبر Ajax
     */
    public function getDailySalesData(Request $request)
    {
        if (!auth()->user()->can('sell_report.view')) {
            abort(403, 'عذراً، ليس لديك صلاحية عرض هذا التقرير');
        }

        $business_id = $request->session()->get('user.business_id');

        // الحصول على قيم المرشحات
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $user_id = $request->get('user_id');

        // **الاستعلام الرئيسي: الحصول على بيانات المبيعات والضرائب معاً**
        $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
            ->select(
                DB::raw('DATE(transactions.transaction_date) as sale_date'),
                DB::raw('COUNT(DISTINCT transactions.id) as invoice_count'),
                // المبالغ من الفاتورة مباشرة
                DB::raw('SUM(transactions.final_total) as total_sales'),
                DB::raw('SUM(transactions.total_before_tax) as total_before_tax'),
                DB::raw('SUM(transactions.tax_amount) as total_tax'),
                DB::raw('SUM(CASE WHEN transactions.payment_status = "paid" THEN transactions.final_total ELSE 0 END) as paid_amount'),
                DB::raw('SUM(CASE WHEN transactions.payment_status = "due" THEN transactions.final_total ELSE 0 END) as due_amount'),
                DB::raw('SUM(CASE WHEN transactions.payment_status = "partial" THEN transactions.final_total ELSE 0 END) as partial_amount'),
                'transactions.location_id',
                'bl.name as location_name'
            );

        // تطبيق المرشحات
        if (!empty($location_id) && $location_id != 'all') {
            $query->where('transactions.location_id', $location_id);
        }

        if (!empty($user_id) && $user_id != 'all') {
            $query->where('transactions.created_by', $user_id);
        }

        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween(DB::raw('DATE(transactions.transaction_date)'), [$start_date, $end_date]);
        } else {
            $query->whereDate('transactions.transaction_date', '>=', now()->subDays(30)->format('Y-m-d'));
        }

        $query->groupBy(DB::raw('DATE(transactions.transaction_date)'), 'transactions.location_id');

        // الحصول على بيانات المرتجعات
        $returns_query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell_return')
            ->where('transactions.status', 'final')
            ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
            ->select(
                DB::raw('DATE(transactions.transaction_date) as return_date'),
                DB::raw('SUM(transactions.final_total) as total_returns'),
                DB::raw('SUM(transactions.total_before_tax) as returns_before_tax'),
                DB::raw('SUM(transactions.tax_amount) as returns_tax'),
                'transactions.location_id',
                'bl.name as location_name'
            );

        if (!empty($location_id) && $location_id != 'all') {
            $returns_query->where('transactions.location_id', $location_id);
        }

        if (!empty($start_date) && !empty($end_date)) {
            $returns_query->whereBetween(DB::raw('DATE(transactions.transaction_date)'), [$start_date, $end_date]);
        } else {
            $returns_query->whereDate('transactions.transaction_date', '>=', now()->subDays(30)->format('Y-m-d'));
        }

        $returns_query->groupBy(DB::raw('DATE(transactions.transaction_date)'), 'transactions.location_id');
        $returns_data = $returns_query->get()->keyBy(function ($item) {
            return $item->return_date . '_' . $item->location_id;
        });

        // **استعلام منفصل لتفاصيل الضرائب من سطور البيع**
        $tax_details_query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->leftJoin('tax_rates as tr', 'tsl.tax_id', '=', 'tr.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->select(
                DB::raw('DATE(t.transaction_date) as tax_date'),
                't.location_id',
                DB::raw('COALESCE(tr.id, 0) as tax_id'),
                DB::raw('COALESCE(tr.name, "بدون ضريبة") as tax_name'),
                DB::raw('COALESCE(tr.amount, 0) as tax_rate'),
                DB::raw('SUM(tsl.item_tax) as tax_amount')
            );

        if (!empty($location_id) && $location_id != 'all') {
            $tax_details_query->where('t.location_id', $location_id);
        }

        if (!empty($start_date) && !empty($end_date)) {
            $tax_details_query->whereBetween(DB::raw('DATE(t.transaction_date)'), [$start_date, $end_date]);
        } else {
            $tax_details_query->whereDate('t.transaction_date', '>=', now()->subDays(30)->format('Y-m-d'));
        }

        $tax_details_query->groupBy(
            DB::raw('DATE(t.transaction_date)'),
            't.location_id',
            'tr.id',
            'tr.name',
            'tr.amount'
        );

        $tax_details = $tax_details_query->get();

        // تنظيم بيانات الضرائب حسب التاريخ والفرع
        $taxes_by_date = [];
        foreach ($tax_details as $tax) {
            $key = $tax->tax_date . '_' . $tax->location_id;
            $tax_key = $tax->tax_name . '_' . $tax->tax_rate;

            if (!isset($taxes_by_date[$key])) {
                $taxes_by_date[$key] = [];
            }

            if (!isset($taxes_by_date[$key][$tax_key])) {
                $taxes_by_date[$key][$tax_key] = [
                    'name' => $tax->tax_name == 'بدون ضريبة' ? 'بدون ضريبة' : $tax->tax_name . ' (' . $tax->tax_rate . '%)',
                    'amount' => 0
                ];
            }
            $taxes_by_date[$key][$tax_key]['amount'] += $tax->tax_amount;
        }

        // الحصول على البيانات الرئيسية
        $sales_data = $query->get();

        // تجميع البيانات النهائية
        $formatted_data = [];
        foreach ($sales_data as $sale) {
            $key = $sale->sale_date . '_' . $sale->location_id;

            // بيانات المرتجعات لهذا التاريخ والفرع
            $return_data = $returns_data->get($key);
            $total_returns = $return_data ? $return_data->total_returns : 0;
            $returns_before_tax = $return_data ? $return_data->returns_before_tax : 0;
            $returns_tax_amount = $return_data ? $return_data->returns_tax : 0;

            // **الحسابات الصحيحة:**
            $net_sales = $sale->total_sales - $total_returns;
            $net_before_tax = $sale->total_before_tax - $returns_before_tax;
            $net_tax = $sale->total_tax - $returns_tax_amount;

            // التحقق من الحسابات
            $calculation_check = abs($net_sales - ($net_before_tax + $net_tax));

            // تجهيز بيانات الضرائب لهذا اليوم
            $day_tax_details = [];
            $total_day_tax = 0;

            if (isset($taxes_by_date[$key])) {
                foreach ($taxes_by_date[$key] as $tax) {
                    if ($tax['amount'] > 0) {
                        $day_tax_details[] = $tax;
                        $total_day_tax += $tax['amount'];
                    }
                }
            }

            // **تحضير HTML تفاصيل الضرائب**
            $tax_summary_html = '';
            if (!empty($day_tax_details)) {
                $tax_summary_html .= '<div class="tax-details">';
                foreach ($day_tax_details as $tax) {
                    $tax_summary_html .= '<div class="tax-item"><small>' . $tax['name'] . ': ' .
                        '<span class="text-primary">' . number_format($tax['amount'], 2) . '</span></small></div>';
                }
                $tax_summary_html .= '<div class="tax-total"><small><strong>إجمالي الضريبة: ' .
                    '<span class="text-success">' . number_format($total_day_tax, 2) . '</span></strong></small></div>';

                // إضافة ملاحظة إذا كانت الضرائب غير متطابقة
                if (abs($sale->total_tax - $total_day_tax) > 0.01) {
                    $tax_summary_html .= '<div class="text-warning"><small><i class="fa fa-exclamation-triangle"></i> ' .
                        'فرق: ' . number_format(abs($sale->total_tax - $total_day_tax), 2) . '</small></div>';
                }

                $tax_summary_html .= '</div>';
            } else {
                $tax_summary_html = '<span class="text-muted">لا توجد ضرائب</span>';
            }

            $formatted_data[] = [
                'date' => $sale->sale_date,
                'location' => $sale->location_name ?? 'غير معروف',
                'invoice_count' => $sale->invoice_count,
                'total_sales' => $sale->total_sales,
                'total_returns' => $total_returns,
                'net_sales' => $net_sales,
                'total_before_tax' => $sale->total_before_tax,
                'total_tax' => $sale->total_tax,
                'returns_before_tax' => $returns_before_tax,
                'returns_tax' => $returns_tax_amount,
                'net_before_tax' => $net_before_tax,
                'net_tax' => $net_tax,
                'paid_amount' => $sale->paid_amount,
                'due_amount' => $sale->due_amount,
                'partial_amount' => $sale->partial_amount,
                'tax_details' => $day_tax_details,
                'total_day_tax' => $total_day_tax,
                'tax_summary_html' => $tax_summary_html,
                'calculation_check' => $calculation_check
            ];
        }

        return DataTables::of($formatted_data)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '<button class="btn btn-xs btn-info view-details" 
                    data-date="' . $row['date'] . '"
                    data-location="' . htmlspecialchars($row['location']) . '">
                    <i class="fa fa-eye"></i> تفاصيل
                    </button>';
            })
            ->addColumn('tax_summary', function ($row) {
                return $row['tax_summary_html'];
            })
            ->editColumn('date', function ($row) {
                $date = date('Y-m-d', strtotime($row['date']));
                $months = [
                    'January' => 'يناير',
                    'February' => 'فبراير',
                    'March' => 'مارس',
                    'April' => 'أبريل',
                    'May' => 'مايو',
                    'June' => 'يونيو',
                    'July' => 'يوليو',
                    'August' => 'أغسطس',
                    'September' => 'سبتمبر',
                    'October' => 'أكتوبر',
                    'November' => 'نوفمبر',
                    'December' => 'ديسمبر'
                ];
                $english_date = date('d F Y', strtotime($date));
                $arabic_date = str_replace(
                    array_keys($months),
                    array_values($months),
                    $english_date
                );
                return $arabic_date;
            })
            ->editColumn('invoice_count', function ($row) {
                return '<span class="badge bg-info">' . $row['invoice_count'] . '</span>';
            })
            ->editColumn('total_sales', function ($row) {
                return '<span class="display_currency" data-orig-value="' . $row['total_sales'] . '">' .
                    number_format($row['total_sales'], 2) . '</span>';
            })
            ->editColumn('total_returns', function ($row) {
                $color = $row['total_returns'] > 0 ? 'text-danger' : 'text-muted';
                return '<span class="display_currency ' . $color . '" data-orig-value="' . $row['total_returns'] . '">' .
                    number_format($row['total_returns'], 2) . '</span>';
            })
            ->editColumn('net_sales', function ($row) {
                $color = $row['net_sales'] < 0 ? 'text-danger' : 'text-success';
                $weight = $row['net_sales'] >= 0 ? 'font-weight-bold' : '';
                return '<span class="display_currency ' . $color . ' ' . $weight . '" data-orig-value="' . $row['net_sales'] . '">' .
                    number_format($row['net_sales'], 2) . '</span>';
            })
            ->editColumn('total_before_tax', function ($row) {
                return '<span class="display_currency" data-orig-value="' . $row['total_before_tax'] . '">' .
                    number_format($row['total_before_tax'], 2) . '</span>';
            })
            ->editColumn('total_tax', function ($row) {
                $color = $row['total_tax'] > 0 ? 'text-info' : 'text-muted';
                return '<span class="display_currency ' . $color . '" data-orig-value="' . $row['total_tax'] . '">' .
                    number_format($row['total_tax'], 2) . '</span>';
            })
            ->editColumn('net_before_tax', function ($row) {
                $color = $row['net_before_tax'] < 0 ? 'text-danger' : 'text-primary';
                return '<span class="display_currency ' . $color . '" data-orig-value="' . $row['net_before_tax'] . '">' .
                    number_format($row['net_before_tax'], 2) . '</span>';
            })
            ->editColumn('net_tax', function ($row) {
                $color = $row['net_tax'] < 0 ? 'text-danger' : 'text-success';
                return '<span class="display_currency ' . $color . '" data-orig-value="' . $row['net_tax'] . '">' .
                    number_format($row['net_tax'], 2) . '</span>';
            })
            ->rawColumns([
                'action',
                'tax_summary',
                'date',
                'invoice_count',
                'total_sales',
                'total_returns',
                'net_sales',
                'total_before_tax',
                'total_tax',
                'net_before_tax',
                'net_tax'
            ])
            ->make(true);
    }

    public function getDailyDetails(Request $request)
    {
        if (!auth()->user()->can('sell_report.view')) {
            abort(403, 'عذراً، ليس لديك صلاحية عرض هذا التقرير');
        }

        $business_id = $request->session()->get('user.business_id');
        $date = $request->get('date');
        $location_name = $request->get('location');

        // البحث عن الـ location_id من الاسم
        $location = BusinessLocation::where('business_id', $business_id)
            ->where('name', $location_name)
            ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'html' => '<div class="alert alert-danger">لم يتم العثور على الفرع</div>'
            ]);
        }

        // الحصول على الفواتير لهذا اليوم والفرع
        $transactions = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', $date)
            ->where('location_id', $location->id)
            ->with(['contact', 'payment_lines', 'sell_lines' => function ($q) {
                $q->with(['product', 'tax']);
            }])
            ->orderBy('transaction_date', 'desc')
            ->get();

        // الحصول على المرتجعات
        $returns = Transaction::where('business_id', $business_id)
            ->where('type', 'sell_return')
            ->where('status', 'final')
            ->whereDate('transaction_date', $date)
            ->where('location_id', $location->id)
            ->with(['contact', 'sell_lines' => function ($q) {
                $q->with(['product', 'tax']);
            }])
            ->get();

        // إحصائيات سريعة
        $total_sales = $transactions->sum('final_total');
        $total_paid = $transactions->sum(function ($t) {
            return $t->payment_lines->sum('amount');
        });
        $total_returns = $returns->sum('final_total');
        $total_due = $total_sales - $total_paid;

        // تفاصيل الضرائب
        $tax_details = [];
        foreach ($transactions as $transaction) {
            foreach ($transaction->sell_lines as $line) {
                if ($line->tax) {
                    $tax_key = $line->tax->name . '_' . $line->tax->amount;
                    if (!isset($tax_details[$tax_key])) {
                        $tax_details[$tax_key] = [
                            'name' => $line->tax->name . ' (' . $line->tax->amount . '%)',
                            'amount' => 0
                        ];
                    }
                    $tax_details[$tax_key]['amount'] += $line->item_tax;
                }
            }
        }

        // طرح ضرائب المرتجعات
        foreach ($returns as $return) {
            foreach ($return->sell_lines as $line) {
                if ($line->tax) {
                    $tax_key = $line->tax->name . '_' . $line->tax->amount;
                    if (!isset($tax_details[$tax_key])) {
                        $tax_details[$tax_key] = [
                            'name' => $line->tax->name . ' (' . $line->tax->amount . '%)',
                            'amount' => 0
                        ];
                    }
                    $tax_details[$tax_key]['amount'] -= $line->item_tax;
                }
            }
        }

        // فلترة الضرائب الموجبة فقط
        $positive_taxes = array_filter($tax_details, function ($tax) {
            return $tax['amount'] > 0;
        });

        $html = view('report.partials.daily_details', compact(
            'transactions',
            'returns',
            'date',
            'location_name',
            'total_sales',
            'total_paid',
            'total_returns',
            'total_due',
            'positive_taxes'
        ))->render();

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }

    /**
     * تصدير التقرير إلى Excel
     */
    public function exportDailySalesReport(Request $request)
    {
        if (!auth()->user()->can('sell_report.view')) {
            abort(403, 'عذراً، ليس لديك صلاحية تصدير هذا التقرير');
        }

        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $user_id = $request->get('user_id');

        // استخدام نفس الاستعلام السابق
        $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
            ->select(
                DB::raw('DATE(transactions.transaction_date) as sale_date'),
                DB::raw('COUNT(DISTINCT transactions.id) as invoice_count'),
                DB::raw('SUM(transactions.final_total) as total_sales'),
                DB::raw('SUM(transactions.total_before_tax) as total_before_tax'),
                DB::raw('SUM(transactions.tax_amount) as total_tax'),
                DB::raw('SUM(CASE WHEN transactions.payment_status = "paid" THEN transactions.final_total ELSE 0 END) as paid_amount'),
                DB::raw('SUM(CASE WHEN transactions.payment_status = "due" THEN transactions.final_total ELSE 0 END) as due_amount'),
                DB::raw('SUM(CASE WHEN transactions.payment_status = "partial" THEN transactions.final_total ELSE 0 END) as partial_amount'),
                'transactions.location_id',
                'bl.name as location_name'
            );

        if (!empty($location_id) && $location_id != 'all') {
            $query->where('transactions.location_id', $location_id);
        }

        if (!empty($user_id) && $user_id != 'all') {
            $query->where('transactions.created_by', $user_id);
        }

        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween(DB::raw('DATE(transactions.transaction_date)'), [$start_date, $end_date]);
        } else {
            $query->whereDate('transactions.transaction_date', '>=', now()->subDays(30)->format('Y-m-d'));
        }

        $query->groupBy(DB::raw('DATE(transactions.transaction_date)'), 'transactions.location_id');
        $sales_data = $query->get();

        // الحصول على المرتجعات
        $returns_query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell_return')
            ->where('transactions.status', 'final')
            ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
            ->select(
                DB::raw('DATE(transactions.transaction_date) as return_date'),
                DB::raw('SUM(transactions.final_total) as total_returns'),
                DB::raw('SUM(transactions.total_before_tax) as returns_before_tax'),
                DB::raw('SUM(transactions.tax_amount) as returns_tax'),
                'transactions.location_id'
            );

        if (!empty($location_id) && $location_id != 'all') {
            $returns_query->where('transactions.location_id', $location_id);
        }

        if (!empty($start_date) && !empty($end_date)) {
            $returns_query->whereBetween(DB::raw('DATE(transactions.transaction_date)'), [$start_date, $end_date]);
        } else {
            $returns_query->whereDate('transactions.transaction_date', '>=', now()->subDays(30)->format('Y-m-d'));
        }

        $returns_query->groupBy(DB::raw('DATE(transactions.transaction_date)'), 'transactions.location_id');
        $returns_data = $returns_query->get()->keyBy(function ($item) {
            return $item->return_date . '_' . $item->location_id;
        });

        // تجميع البيانات للتصدير
        $export_data = [];
        foreach ($sales_data as $sale) {
            $key = $sale->sale_date . '_' . $sale->location_id;

            $return_data = $returns_data->get($key);
            $total_returns = $return_data ? $return_data->total_returns : 0;
            $returns_before_tax = $return_data ? $return_data->returns_before_tax : 0;
            $returns_tax = $return_data ? $return_data->returns_tax : 0;

            $net_sales = $sale->total_sales - $total_returns;
            $net_before_tax = $sale->total_before_tax - $returns_before_tax;
            $net_tax = $sale->total_tax - $returns_tax;

            // التأكد من أن الأرقام موجبة
            $net_sales = max(0, $net_sales);
            $net_before_tax = max(0, $net_before_tax);
            $net_tax = max(0, $net_tax);

            $export_data[] = [
                'التاريخ' => $sale->sale_date,
                'الفرع' => $sale->location_name ?? 'غير معروف',
                'عدد الفواتير' => $sale->invoice_count,
                'إجمالي المبيعات' => $sale->total_sales,
                'إجمالي المرتجعات' => $total_returns,
                'صافي المبيعات' => $net_sales,
                'قبل الضريبة (إجمالي)' => $sale->total_before_tax,
                'الضريبة (إجمالي)' => $sale->total_tax,
                'الصافي قبل الضريبة' => $net_before_tax,
                'الصافي بعد الضريبة' => $net_sales,
                'الضريبة الصافية' => $net_tax,
                'المدفوع' => $sale->paid_amount,
                'المستحق' => $sale->due_amount,
                'المدفوع جزئياً' => $sale->partial_amount
            ];
        }

        // يمكنك هنا استخدام Laravel Excel package للتصدير
        // أو إنشاء CSV يدوياً

        // مثال بسيط لتنزيل CSV
        $filename = "تقرير_المبيعات_اليومية_" . date('Y_m_d') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($export_data) {
            $file = fopen('php://output', 'w');

            // BOM للغة العربية
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // رأس الجدول
            fputcsv($file, array_keys($export_data[0] ?? []));

            // البيانات
            foreach ($export_data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * الحصول على إحصائيات التقرير
     */
    public function getReportSummary(Request $request)
    {
        if (!auth()->user()->can('sell_report.view')) {
            abort(403, 'عذراً، ليس لديك صلاحية عرض هذا التقرير');
        }

        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $user_id = $request->get('user_id');

        // استعلام الإجماليات
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final');

        $returns_query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell_return')
            ->where('status', 'final');

        if (!empty($location_id) && $location_id != 'all') {
            $query->where('location_id', $location_id);
            $returns_query->where('location_id', $location_id);
        }

        if (!empty($user_id) && $user_id != 'all') {
            $query->where('created_by', $user_id);
            $returns_query->where('created_by', $user_id);
        }

        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween(DB::raw('DATE(transaction_date)'), [$start_date, $end_date]);
            $returns_query->whereBetween(DB::raw('DATE(transaction_date)'), [$start_date, $end_date]);
        }

        $total_sales = $query->sum('final_total');
        $total_returns = $returns_query->sum('final_total');
        $total_before_tax = $query->sum('total_before_tax');
        $returns_before_tax = $returns_query->sum('total_before_tax');
        $total_tax = $query->sum('tax_amount');
        $returns_tax = $returns_query->sum('tax_amount');

        $net_sales = $total_sales - $total_returns;
        $net_before_tax = $total_before_tax - $returns_before_tax;
        $net_tax = $total_tax - $returns_tax;

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => $total_sales,
                'total_returns' => $total_returns,
                'net_sales' => $net_sales,
                'total_before_tax' => $total_before_tax,
                'net_before_tax' => $net_before_tax,
                'total_tax' => $total_tax,
                'net_tax' => $net_tax
            ]
        ]);
    }







    //////////////////////////////////009
    public function customSalesReport()
{
    $business_id = request()->session()->get('user.business_id');
    $business_locations = BusinessLocation::forDropdown($business_id);
    return view('report.custom_sales_report', compact('business_locations'));
}

public function getCustomSalesData(Request $request)
{
    $business_id = $request->session()->get('user.business_id');
    $report_type = $request->get('report_type', 'summary');

    if ($report_type == 'summary') {
        // --- منطق المجمل: تجميع حسب التاريخ ---
        $query = Transaction::where('business_id', $business_id)
            ->whereIn('type', ['sell', 'sell_return'])
            ->where('status', 'final')
            ->select([
                DB::raw("DATE_FORMAT(transaction_date, '%d-%m-%Y') as date"),
                DB::raw("SUM(IF(type='sell', final_total, 0)) as total_sales"),
                DB::raw("SUM(IF(type='sell_return', final_total, 0)) as total_returns"),
                DB::raw("SUM(IF(type='sell', final_total, -1 * final_total)) as net_sales"),
                DB::raw("COUNT(id) as total_invoices")
            ])
            ->groupBy('date');
    } else {
        // --- منطق التفصيلي: عرض كل فاتورة ---
        $query = Transaction::where('business_id', $business_id)
            ->whereIn('type', ['sell', 'sell_return'])
            ->where('status', 'final')
            ->with(['contact', 'location'])
            ->select('transactions.*');
    }

    // الفلترة حسب الموقع والتاريخ
    if (!empty($request->location_id)) {
        $query->where('location_id', $request->location_id);
    }
    if (!empty($request->start_date) && !empty($request->end_date)) {
        $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
    }

    return DataTables::of($query)
        ->editColumn('total_sales', fn($row) => number_format($row->total_sales ?? $row->final_total, 2))
        ->addColumn('type_label', function($row) {
            return $row->type == 'sell' ? '<span class="label bg-green">مبيعات</span>' : '<span class="label bg-red">مرتجع</span>';
        })
        ->rawColumns(['type_label'])
        ->make(true);
}
}
