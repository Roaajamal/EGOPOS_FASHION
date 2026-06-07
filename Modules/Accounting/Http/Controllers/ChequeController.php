<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\TransactionPayment;
use Yajra\DataTables\Facades\DataTables;
use DB;
use Carbon\Carbon;

class ChequeController extends Controller
{
public function index()
{
    if (!auth()->user()->can('accounting.view_reports')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = request()->session()->get('user.business_id');

    if (request()->ajax()) {
        $cheques = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('transaction_payments.method', 'cheque')
            ->select([
                'transaction_payments.id',
                'transaction_payments.cheque_number',
                'transaction_payments.bank_name',
                'transaction_payments.cheque_return_date',
                'transaction_payments.amount',
                'transaction_payments.cheque_status',
                'c.name as contact_name',
                't.ref_no as invoice_no',
                't.type as transaction_type'
            ]);

        // 1. فلترة نوع الشيك (لي / علي)
        if (!empty(request()->get('cheque_type'))) {
            if (request()->get('cheque_type') == 'mine') {
                $cheques->whereIn('t.type', ['sell', 'sell_return']);
            } elseif (request()->get('cheque_type') == 'on_me') {
                $cheques->whereIn('t.type', ['purchase', 'expense', 'purchase_return']);
            }
        }

        // 2. فلترة الحالة (قيد الانتظار، محصل، مرتجع)
        if (!empty(request()->get('status'))) {
            $cheques->where('transaction_payments.cheque_status', request()->get('status'));
        }

        // 3. فلترة البنك (بحث نصي)
        if (!empty(request()->get('bank_name'))) {
            $cheques->where('transaction_payments.bank_name', 'like', '%' . request()->get('bank_name') . '%');
        }

        // 4. فلترة العميل أو المورد
        if (!empty(request()->get('contact_id'))) {
            $cheques->where('t.contact_id', request()->get('contact_id'));
        }

        // 5. فلترة التاريخ (الخيارات السريعة + التاريخ المحدد)
        $today = Carbon::today();
        if (!empty(request()->get('due_date_filter'))) {
            $filter = request()->get('due_date_filter');
            if ($filter == 'today') {
                $cheques->whereDate('transaction_payments.cheque_return_date', $today);
            } elseif ($filter == 'yesterday') {
                $cheques->whereDate('transaction_payments.cheque_return_date', Carbon::yesterday());
            } elseif ($filter == 'overdue') {
                $cheques->whereDate('transaction_payments.cheque_return_date', '<', $today)
                        ->where('transaction_payments.cheque_status', 'pending');
            } elseif ($filter == 'upcoming') {
                $cheques->whereBetween('transaction_payments.cheque_return_date', [$today, $today->copy()->addDays(3)]);
            }
        }

        // 6. فلترة تاريخ استحقاق محدد (Range)
        if (!empty(request()->get('start_date')) && !empty(request()->get('end_date'))) {
            $cheques->whereBetween('transaction_payments.cheque_return_date', [request()->get('start_date'), request()->get('end_date')]);
        }

        // حساب الإحصائيات (تتأثر بالفلترة الحالية)
        $counts = [
            'overdue' => (clone $cheques)->whereDate('transaction_payments.cheque_return_date', '<', $today)->where('transaction_payments.cheque_status', 'pending')->count(),
            'today' => (clone $cheques)->whereDate('transaction_payments.cheque_return_date', $today)->count(),
            'upcoming' => (clone $cheques)->whereBetween('transaction_payments.cheque_return_date', [$today->copy()->addDay(), $today->copy()->addDays(3)])->count(),
        ];

        return Datatables::of($cheques)
            ->editColumn('amount', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">' . $row->amount . '</span>';
            })
            ->editColumn('cheque_status', function ($row) {
                $status = $row->cheque_status ?? 'pending';
                $labels = [
                    'pending' => ['class' => 'label-warning', 'label' => 'قيد الانتظار'],
                    'cleared' => ['class' => 'label-success', 'label' => 'تم التحصيل'],
                    'returned' => ['class' => 'label-danger', 'label' => 'مرتجع'],
                ];
                return '<span class="label ' . $labels[$status]['class'] . '">' . $labels[$status]['label'] . '</span>';
            })
            ->addColumn('action', function ($row) {
                return '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">الإجراءات <span class="caret"></span></button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="#" class="print-cheque" data-id="' . $row->id . '"><i class="fa fa-print"></i> طباعة</a></li>
                                <li class="divider"></li>
                                <li><a href="#" class="change-status" data-id="' . $row->id . '" data-status="cleared"><i class="fa fa-check-circle text-success"></i> تم التحصيل</a></li>
                                <li><a href="#" class="change-status" data-id="' . $row->id . '" data-status="returned"><i class="fa fa-times-circle text-danger"></i> مرتجع</a></li>
                            </ul>
                        </div>';
            })
            ->with('counts', $counts)
            ->rawColumns(['amount', 'cheque_status', 'action'])
            ->make(true);
    }

    // نحتاج لجلب قائمة الموردين والعملاء للفلاتر
    $business_id = request()->session()->get('user.business_id');
    $contacts = \App\Contact::where('business_id', $business_id)
                ->pluck('name', 'id');

    return view('accounting::cheques.index')->with(compact('contacts'));
}

    public function updateStatus(Request $request)
    {
        if (!auth()->user()->can('accounting.view_reports')) {
            return ['success' => false, 'msg' => 'Unauthorized'];
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $payment_id = $request->input('id');
            $status = $request->input('status');

            $payment = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
                ->where('t.business_id', $business_id)
                ->where('transaction_payments.id', $payment_id)
                ->select('transaction_payments.*')
                ->firstOrFail();

            $payment->cheque_status = $status;
            $payment->save();

            return [
                'success' => true,
                'msg' => "تم تحديث حالة الشيك بنجاح"
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return [
                'success' => false,
                'msg' => "حدث خطأ ما!"
            ];
        }
    }



    public function print($id)
{
    $business_id = request()->session()->get('user.business_id');
    $cheque = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
        ->join('contacts as c', 't.contact_id', '=', 'c.id')
        ->where('t.business_id', $business_id)
        ->where('transaction_payments.id', $id)
        ->select([
            'transaction_payments.*',
            'c.name as contact_name'
        ])->firstOrFail();

    // سنرسل البيانات لملف Blade مخصص للطباعة
    return view('accounting::cheques.print', compact('cheque'));
}
}