<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Entities\AccountingVoucher;
use Modules\Accounting\Entities\AccountingAccount;
use App\Contact;
use App\Utils\ModuleUtil;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil) {
        $this->moduleUtil = $moduleUtil;
    }


    public function create() {
    $business_id = request()->session()->get('user.business_id');

    // جلب الفروع
    $business_locations = \App\BusinessLocation::forDropdown($business_id);

    // توليد رقم السند القادم (تلقائي للعرض فقط)
    $last_voucher = AccountingVoucher::where('business_id', $business_id)->latest()->first();
    $next_id = $last_voucher ? $last_voucher->id + 1 : 1;
    $next_voucher_no = 'VCH-' . date('Y') . '-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

    // جلب الأشخاص (عملاء وموردين وموظفين)
    $contacts = \App\Contact::contactDropdown($business_id, false, false);

    // جلب كافة الحسابات النشطة
    $accounts = \Modules\Accounting\Entities\AccountingAccount::where('business_id', $business_id)
                ->where('status', 'active')
                ->orderBy('name', 'asc')
                ->get()
                ->pluck('name', 'id');

    return view('accounting::vouchers.create')
            ->with(compact('contacts', 'accounts', 'business_locations', 'next_voucher_no'));
}

public function index()
{
    $business_id = request()->session()->get('user.business_id');

    if (request()->ajax()) {
        $vouchers = AccountingVoucher::where('accounting_vouchers.business_id', $business_id)
            ->leftJoin('contacts', 'accounting_vouchers.contact_id', '=', 'contacts.id')
            ->leftJoin('accounting_accounts', 'accounting_vouchers.account_id', '=', 'accounting_accounts.id')
            ->leftJoin('business_locations as bl', 'accounting_vouchers.location_id', '=', 'bl.id') // ربط الفروع
            ->select([
                'accounting_vouchers.id',
                'accounting_vouchers.voucher_no',
                'accounting_vouchers.type',
                'accounting_vouchers.operation_date',
                'accounting_vouchers.amount',
                'bl.name as location_name', // جلب اسم الفرع
                DB::raw("COALESCE(contacts.name, accounting_vouchers.received_from) as contact_display_name"),
                'accounting_accounts.name as account_name',
                'accounting_vouchers.note'
            ]);

        // فلتر الفرع الجديد
        if (!empty(request()->input('location_id'))) {
            $vouchers->where('accounting_vouchers.location_id', request()->input('location_id'));
        }

        // ... بقية الفلاتر (النوع، التاريخ، إلخ) تبقى كما هي ...
        if (!empty(request()->input('type'))) {
            $vouchers->where('accounting_vouchers.type', request()->input('type'));
        }

        return DataTables::of($vouchers)
            ->editColumn('type', function ($row) {
                $types = ['receipt' => 'سند قبض', 'payment' => 'سند صرف', 'journal' => 'سند قيد'];
                return $types[$row->type] ?? $row->type;
            })
            ->editColumn('amount', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">' . $row->amount . '</span>';
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . action([VoucherController::class, 'print'], [$row->id]) . '" class="btn btn-xs btn-primary" target="_blank"><i class="fas fa-print"></i> طباعة</a>';
            })
            ->rawColumns(['amount', 'action'])
            ->make(true);
    }

    $business_locations = \App\BusinessLocation::forDropdown($business_id); // جلب الفروع للفلتر
    $accounts = \Modules\Accounting\Entities\AccountingAccount::where('business_id', $business_id)->where('status', 'active')->pluck('name', 'id');
    $contacts = Contact::contactDropdown($business_id, false, false);

    return view('accounting::vouchers.index')->with(compact('accounts', 'contacts', 'business_locations'));
}



public function store(Request $request) {
    try {
        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $location_id = $request->input('location_id'); // استقبال معرف الفرع

        DB::beginTransaction();

        $input = $request->only(['type', 'voucher_no', 'contact_id', 'debit_account', 'credit_account', 'amount', 'operation_date', 'note', 'payee_name']);

        // معالجة الاسم كما فعلنا سابقاً
        $final_name = $input['payee_name'];
        if (empty($final_name) && !empty($input['contact_id'])) {
            $contact = Contact::where('business_id', $business_id)->find($input['contact_id']);
            if($contact) { $final_name = $contact->name; }
        }

        $voucher = AccountingVoucher::create([
            'business_id' => $business_id,
            'location_id' => $location_id, // حفظ الفرع في جدول السندات
            'voucher_no' => $input['voucher_no'] ?? $this->generateVoucherNo($input['type']),
            'type' => $input['type'],
            'contact_id' => !empty($input['contact_id']) ? $input['contact_id'] : null,
            'account_id' => $input['debit_account'], 
            'amount' => $input['amount'],
            'operation_date' => $input['operation_date'],
            'received_from' => $final_name,
            'note' => $input['note'],
            'created_by' => $user_id
        ]);

        // إرسال الفرع لدالة القيد المزدوج
        $this->saveDoubleEntry($voucher, $input['debit_account'], $input['credit_account'], $location_id);
        
        DB::commit();
        return redirect()->action([\Modules\Accounting\Http\Controllers\VoucherController::class, 'index'])->with('status', ['success' => true, 'msg' => 'تم حفظ السند بنجاح']);
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('status', ['success' => false, 'msg' => $e->getMessage()]);
    }
}



private function saveEntry($voucher, $debit_id, $credit_id, $business_id, $user_id) {
    // الطرف المدين (Debit)
    if ($debit_id) {
        \Modules\Accounting\Entities\AccountingAccountsTransaction::create([
            'accounting_account_id' => $debit_id,
            'transaction_id' => null, // لأنه سند يدوي وليس فاتورة بيع
            'accounting_voucher_id' => $voucher->id,
            'type' => 'debit',
            'amount' => $voucher->amount,
            'operation_date' => $voucher->operation_date,
            'created_by' => $user_id,
            'business_id' => $business_id,
            'note' => $voucher->note
        ]);
    }

    // الطرف الدائن (Credit)
    if ($credit_id) {
        \Modules\Accounting\Entities\AccountingAccountsTransaction::create([
            'accounting_account_id' => $credit_id,
            'transaction_id' => null,
            'accounting_voucher_id' => $voucher->id,
            'type' => 'credit',
            'amount' => $voucher->amount,
            'operation_date' => $voucher->operation_date,
            'created_by' => $user_id,
            'business_id' => $business_id,
            'note' => $voucher->note
        ]);
    }
}
private function saveDoubleEntry($voucher, $debit_account_id, $credit_account_id, $location_id) {
    $user_id = request()->session()->get('user.id');

    $common_data = [
        'accounting_voucher_id' => $voucher->id,
        'amount' => $voucher->amount,
        'operation_date' => $voucher->operation_date,
        'created_by' => $user_id,
        'location_id' => $location_id, // ربط الحركة بالفرع
        'note' => $voucher->note
    ];

    // الطرف المدين
    \Modules\Accounting\Entities\AccountingAccountsTransaction::create(array_merge($common_data, [
        'accounting_account_id' => $debit_account_id,
        'type' => 'debit'
    ]));

    // الطرف الدائن
    \Modules\Accounting\Entities\AccountingAccountsTransaction::create(array_merge($common_data, [
        'accounting_account_id' => $credit_account_id,
        'type' => 'credit'
    ]));
}

public function print($id) {
    $business_id = request()->session()->get('user.business_id');
    $voucher = AccountingVoucher::where('business_id', $business_id)
                ->with(['contact', 'account'])
                ->findOrFail($id);

    // الحسابات المتأثرة بالقيد (لإظهارها في الجدول)
    $transactions = \Modules\Accounting\Entities\AccountingAccountsTransaction::where('accounting_voucher_id', $voucher->id)
                    ->with('account')
                    ->get();

    return view('accounting::vouchers.print')->with(compact('voucher', 'transactions'));
}


}