<?php

namespace Modules\Accounting\Http\Controllers;

use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\AccountingAccTransMapping;
use Modules\Accounting\Utils\AccountingUtil;
use Yajra\DataTables\Facades\DataTables;
use App\BusinessLocation;

class JournalEntryController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $util;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(Util $util, ModuleUtil $moduleUtil, AccountingUtil $accountingUtil)
    {
        $this->util = $util;
        $this->moduleUtil = $moduleUtil;
        $this->accountingUtil = $accountingUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
public function index()
{
    $business_id = request()->session()->get('user.business_id');

    if (!(auth()->user()->can('superadmin') ||
        $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
        !(auth()->user()->can('accounting.view_journal'))) {
        abort(403, 'Unauthorized action.');
    }

    if (request()->ajax()) {
        $journal = AccountingAccTransMapping::where('accounting_acc_trans_mappings.business_id', $business_id)
            ->join('users as u', 'accounting_acc_trans_mappings.created_by', 'u.id')
            ->leftJoin('accounting_accounts_transactions as at', 'accounting_acc_trans_mappings.id', '=', 'at.acc_trans_mapping_id')
            ->leftJoin('business_locations as bl', 'at.location_id', '=', 'bl.id')
            ->where('accounting_acc_trans_mappings.type', 'journal_entry')
            ->select([
                'accounting_acc_trans_mappings.id', 
                'accounting_acc_trans_mappings.ref_no', 
                'accounting_acc_trans_mappings.operation_date', 
                'accounting_acc_trans_mappings.note',
                'bl.name as location_name',
                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
            ])
            ->groupBy('accounting_acc_trans_mappings.id');

        // فلتر التاريخ
        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $journal->whereDate('accounting_acc_trans_mappings.operation_date', '>=', request()->start_date)
                    ->whereDate('accounting_acc_trans_mappings.operation_date', '<=', request()->end_date);
        }

        // فلتر الفرع
        if (!empty(request()->location_id)) {
            $journal->where('at.location_id', request()->location_id);
        }

        // فلتر الرقم المرجعي
        if (!empty(request()->ref_no)) {
            $journal->where('accounting_acc_trans_mappings.ref_no', 'like', '%' . request()->ref_no . '%');
        }

        // فلتر البيان (الملاحظات)
        if (!empty(request()->note)) {
            $journal->where('accounting_acc_trans_mappings.note', 'like', '%' . request()->note . '%');
        }

        return Datatables::of($journal)
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                data-toggle="dropdown" aria-expanded="false">' .
                                __("messages.actions") .
                                '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                
                $html .= '<li><a href="' . action([\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'show'], [$row->id]) . '" class="btn-modal" data-container="#view_modal"><i class="fas fa-eye"></i> ' . __("messages.view") . '</a></li>';
                $html .= '<li><a href="' . action([\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'edit'], [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                $html .= '<li><a href="' . action([\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'destroy'], [$row->id]) . '" class="delete_journal_button"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</a></li>';

                $html .=  '</ul></div>';
                return $html;
            })
            ->removeColumn('id')
            ->editColumn('operation_date', function ($row) {
                return $this->util->format_date($row->operation_date, true);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    // جلب الفروع لعرضها في قائمة الفلتر المنسدلة
    $business_locations = \App\BusinessLocation::forDropdown($business_id);

    return view('accounting::journal_entry.index')->with(compact('business_locations'));
}

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
   public function create()
{
    $business_id = request()->session()->get('user.business_id');
    
    // جلب الفروع
    $business_locations = \App\BusinessLocation::forDropdown($business_id);
    
    $currencies = \App\Currency::select('id', DB::raw("CONCAT(currency, ' (', code, ')') as info"))->pluck('info', 'id');

    if (!(auth()->user()->can('superadmin') ||
        $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
        !(auth()->user()->can('accounting.add_journal'))) {
        abort(403, 'Unauthorized action.');
    }

    return view('accounting::journal_entry.create')->with(compact('currencies', 'business_locations'));
}

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') ||
            $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! (auth()->user()->can('accounting.add_journal'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
        DB::beginTransaction();
        $user_id = request()->session()->get('user.id');
        $account_ids = $request->get('account_id');
        
        // التعديل هنا: سحب الفرع كقيمة واحدة وليس مصفوفة
        $location_id = $request->get('location_id'); 
        
        $credits = $request->get('credit');
        $debits = $request->get('debit');
        $journal_date = $request->get('journal_date');
        $currency_id = $request->get('currency_id');
        $exchange_rate = $this->util->num_uf($request->get('exchange_rate', 1));

            $accounting_settings = $this->accountingUtil->getAccountingSettings($business_id);

            $ref_no = $request->get('ref_no');
            $ref_count = $this->util->setAndGetReferenceCount('journal_entry');
            if (empty($ref_no)) {
                $prefix = ! empty($accounting_settings['journal_entry_prefix']) ?
                $accounting_settings['journal_entry_prefix'] : '';

                $ref_no = $this->util->generateReferenceNumber('journal_entry', $ref_count, $business_id, $prefix);
            }

            $acc_trans_mapping = new AccountingAccTransMapping();
            $acc_trans_mapping->business_id = $business_id;
            $acc_trans_mapping->ref_no = $ref_no;
            $acc_trans_mapping->note = $request->get('note');
            $acc_trans_mapping->type = 'journal_entry';
            $acc_trans_mapping->created_by = $user_id;
            $acc_trans_mapping->operation_date = $this->util->uf_date($journal_date, true);
            $acc_trans_mapping->save();

            foreach ($account_ids as $index => $account_id) {
                if (! empty($account_id)) {
                    $transaction_row = [];
                    $transaction_row['accounting_account_id'] = $account_id;
                    $transaction_row['location_id'] = $location_id;

                    $amount_in_currency = 0;
                    $type = '';

                    if (! empty($credits[$index])) {
                        $amount_in_currency = $this->util->num_uf($credits[$index]);
                        $type = 'credit';
                    } elseif (! empty($debits[$index])) {
                        $amount_in_currency = $this->util->num_uf($debits[$index]);
                        $type = 'debit';
                    }

                    if ($amount_in_currency > 0) {
                        $transaction_row['type'] = $type;
                        // حفظ المبلغ الأصلي (بالدولار مثلاً)
                        $transaction_row['amount_in_currency'] = $amount_in_currency;
                        // حفظ المبلغ المحول (بالدينار) للاستخدام المحاسبي
                        $transaction_row['amount'] = $amount_in_currency * $exchange_rate;
                        
                        // حفظ بيانات العملة في كل سطر
                        $transaction_row['currency_id'] = $currency_id;
                        $transaction_row['exchange_rate'] = $exchange_rate;

                        $transaction_row['created_by'] = $user_id;
                        $transaction_row['operation_date'] = $this->util->uf_date($journal_date, true);
                        $transaction_row['sub_type'] = 'journal_entry';
                        $transaction_row['acc_trans_mapping_id'] = $acc_trans_mapping->id;

                        $accounts_transactions = new AccountingAccountsTransaction();
                        $accounts_transactions->fill($transaction_row);
                        $accounts_transactions->save();
                    }
                }
            }

            DB::commit();

            $output = ['success' => 1,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->route('journal-entry.index')->with('status', $output);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') ||
            $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! (auth()->user()->can('accounting.view_journal'))) {
            abort(403, 'Unauthorized action.');
        }

        return view('accounting::journal_entry.show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
public function edit($id)
{
    $business_id = request()->session()->get('user.business_id');

    if (!(auth()->user()->can('superadmin') ||
        $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
        !(auth()->user()->can('accounting.edit_journal'))) {
        abort(403, 'Unauthorized action.');
    }

    $journal = AccountingAccTransMapping::where('business_id', $business_id)
                ->where('type', 'journal_entry')
                ->where('id', $id)
                ->firstOrFail();

    // جلب أول حركة لمعرفة الفرع المرتبط بالقيد
    $first_transaction = AccountingAccountsTransaction::where('acc_trans_mapping_id', $id)->first();
    $journal->location_id = $first_transaction ? $first_transaction->location_id : null;

    // جلب جميع الحركات المرتبطة بالقيد مع بيانات الحسابات
    $accounts_transactions = AccountingAccountsTransaction::with('account')
                                ->where('acc_trans_mapping_id', $id)
                                ->get()->toArray();

    // --- السطر الناقص المهم جداً ---
    // جلب قائمة الفروع الخاصة بالبزنس لعرضها في الـ Select
    $business_locations = \App\BusinessLocation::forDropdown($business_id);

    return view('accounting::journal_entry.edit')
        ->with(compact('journal', 'accounts_transactions', 'business_locations'));
}

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
public function update(Request $request, $id)
{
    $business_id = request()->session()->get('user.business_id');

    if (!(auth()->user()->can('superadmin') ||
        $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
        !(auth()->user()->can('accounting.edit_journal'))) {
        abort(403, 'Unauthorized action.');
    }

    try {
        DB::beginTransaction();

        $user_id = request()->session()->get('user.id');
        $account_ids = $request->get('account_id');
        $accounts_transactions_id = $request->get('accounts_transactions_id');
        $credits = $request->get('credit');
        $debits = $request->get('debit');
        $journal_date = $request->get('journal_date');
        $location_id = $request->get('location_id'); // استقبال الفرع

        // حقول العملة
        $currency_id = $request->get('currency_id');
        $exchange_rate = $this->util->num_uf($request->get('exchange_rate', 1));

        $acc_trans_mapping = AccountingAccTransMapping::where('business_id', $business_id)
            ->where('type', 'journal_entry')
            ->where('id', $id)
            ->firstOrFail();
            
        $acc_trans_mapping->note = $request->get('note');
        $acc_trans_mapping->operation_date = $this->util->uf_date($journal_date, true);
        $acc_trans_mapping->update();

        // مصفوفة للاحتفاظ بمعرفات الترانزكشن التي تم تحديثها لمنع حذفها
        $updated_transaction_ids = [];

        foreach ($account_ids as $index => $account_id) {
            if (!empty($account_id)) {
                $amount_in_currency = 0;
                $type = '';

                if (!empty($credits[$index])) {
                    $amount_in_currency = $this->util->num_uf($credits[$index]);
                    $type = 'credit';
                } elseif (!empty($debits[$index])) {
                    $amount_in_currency = $this->util->num_uf($debits[$index]);
                    $type = 'debit';
                }

                if ($amount_in_currency > 0) {
                    $transaction_data = [
                        'accounting_account_id' => $account_id,
                        'location_id' => $location_id, // تحديث الفرع لكل سطر
                        'amount_in_currency' => $amount_in_currency,
                        'amount' => $amount_in_currency * $exchange_rate,
                        'type' => $type,
                        'currency_id' => $currency_id,
                        'exchange_rate' => $exchange_rate,
                        'operation_date' => $this->util->uf_date($journal_date, true),
                        'created_by' => $user_id,
                        'sub_type' => 'journal_entry',
                        'acc_trans_mapping_id' => $acc_trans_mapping->id,
                    ];

                    if (!empty($accounts_transactions_id[$index])) {
                        // تحديث السطر الموجود
                        $transaction = AccountingAccountsTransaction::where('id', $accounts_transactions_id[$index])
                            ->where('acc_trans_mapping_id', $acc_trans_mapping->id)
                            ->first();
                        if ($transaction) {
                            $transaction->update($transaction_data);
                            $updated_transaction_ids[] = $transaction->id;
                        }
                    } else {
                        // إضافة سطر جديد (في حال ضغط المستخدم على Add More Row في التعديل)
                        $new_transaction = AccountingAccountsTransaction::create($transaction_data);
                        $updated_transaction_ids[] = $new_transaction->id;
                    }
                }
            }
        }

        // حذف أي أسطر قديمة كانت موجودة في القيد ولم تعد موجودة في الطلب الحالي (مثلاً لو حذف المستخدم سطر)
        AccountingAccountsTransaction::where('acc_trans_mapping_id', $acc_trans_mapping->id)
            ->whereNotIn('id', $updated_transaction_ids)
            ->delete();

        DB::commit();
        $output = ['success' => 1, 'msg' => __('lang_v1.updated_success')];
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
        $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
    }

    return redirect()->action([\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'index'])->with('status', $output);
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') ||
            $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! (auth()->user()->can('accounting.delete_journal'))) {
            abort(403, 'Unauthorized action.');
        }

        $user_id = request()->session()->get('user.id');

        $acc_trans_mapping = AccountingAccTransMapping::where('id', $id)
                        ->where('business_id', $business_id)->firstOrFail();

        if (! empty($acc_trans_mapping)) {
            $acc_trans_mapping->delete();
            AccountingAccountsTransaction::where('acc_trans_mapping_id', $id)->delete();
        }

        return ['success' => 1,
            'msg' => __('lang_v1.deleted_success'),
        ];
    }
}
