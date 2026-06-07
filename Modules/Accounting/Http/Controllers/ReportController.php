<?php

namespace Modules\Accounting\Http\Controllers;

use App\BusinessLocation;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use DB;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Utils\AccountingUtil;
use Illuminate\Http\Request;
use App\Business;
use Modules\Accounting\Entities\AccountingAccountTransaction;
use App\Transaction;
use App\TransactionSellLine;

class ReportController extends Controller
{
    protected $accountingUtil;
    protected $businessUtil;
    protected $moduleUtil;

    /**
     * Constructor
     */
    public function __construct(AccountingUtil $accountingUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->accountingUtil = $accountingUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') ||
            $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! (auth()->user()->can('accounting.view_reports'))) {
            abort(403, 'Unauthorized action.');
        }

        $first_account = AccountingAccount::where('business_id', $business_id)
                            ->where('status', 'active')
                            ->first();
        $ledger_url = null;
        if (! empty($first_account)) {
            $ledger_url = route('accounting.ledger', $first_account);
        }

        return view('accounting::report.index')
            ->with(compact('ledger_url'));
    }

    /**
     * Trial Balance - ميزان المراجعة 
     */
public function trialBalance()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || 
            $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) || 
            !(auth()->user()->can('accounting.view_reports'))) {
            abort(403, 'Unauthorized action.');
        }

        // تحديد التواريخ
        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start_date = request()->start_date;
            $end_date = request()->end_date;
        } else {
            $fy = $this->businessUtil->getCurrentFinancialYear($business_id);
            $start_date = $fy['start'];
            $end_date = $fy['end'];
        }

        // --- إضافة منطق الفرع الجديد ---
        $location_id = request()->input('location_id');
        $business_locations = \App\BusinessLocation::forDropdown($business_id);

        $exchange_rate = request()->input('exchange_rate', 1);
        if($exchange_rate <= 0) { $exchange_rate = 1; }

        $currency_id = request()->input('currency_id');
        if (!empty($currency_id)) {
            $currency = \App\Currency::find($currency_id);
            $currency_code = $currency ? $currency->code : '';
        } else {
            $business = \App\Business::find($business_id);
            $currency_code = $business->currency->code ?? '';
        }

        $query = AccountingAccount::leftJoin('accounting_accounts_transactions as AAT',
                'AAT.accounting_account_id', '=', 'accounting_accounts.id')
            ->where('accounting_accounts.business_id', $business_id)
            ->whereDate('AAT.operation_date', '>=', $start_date)
            ->whereDate('AAT.operation_date', '<=', $end_date);

        // فلترة حسب الفرع إذا تم اختياره
        if (!empty($location_id)) {
            $query->where('AAT.location_id', $location_id);
        }

        $accounts = $query->select(
                DB::raw("SUM(IF(AAT.type = 'credit', AAT.amount / $exchange_rate, 0)) as credit_balance"),
                DB::raw("SUM(IF(AAT.type = 'debit', AAT.amount / $exchange_rate, 0)) as debit_balance"),
                'accounting_accounts.name'
            )
            ->groupBy('accounting_accounts.id', 'accounting_accounts.name')
            ->get();

        $currencies = \App\Currency::select('id', DB::raw("CONCAT(currency, ' (', code, ')') as info"))->pluck('info', 'id');

        return view('accounting::report.trial_balance')
            ->with(compact('accounts', 'start_date', 'end_date', 'currencies', 'exchange_rate', 'currency_code', 'business_locations'));
    }



    /**
     * Balance Sheet - الميزانية العمومية
     */
  public function balanceSheet()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || 
            $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) || 
            !(auth()->user()->can('accounting.view_reports'))) {
            abort(403, 'Unauthorized action.');
        }

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start_date = request()->start_date;
            $end_date = request()->end_date;
        } else {
            $fy = $this->businessUtil->getCurrentFinancialYear($business_id);
            $start_date = $fy['start'];
            $end_date = $fy['end'];
        }

        // --- إضافة منطق الفرع الجديد ---
        $location_id = request()->input('location_id');
        $business_locations = \App\BusinessLocation::forDropdown($business_id);

        $exchange_rate = request()->input('exchange_rate', 1);
        if ($exchange_rate <= 0) { $exchange_rate = 1; }

        $currencies = \App\Currency::select('id', DB::raw("CONCAT(currency, ' (', code, ')') as info"))->pluck('info', 'id');

        $currency_id = request()->input('currency_id');
        if (!empty($currency_id)) {
            $currency = \App\Currency::find($currency_id);
            $currency_code = $currency ? $currency->code : 'JOD';
        } else {
            $business = \App\Business::find($business_id);
            $currency_code = $business->currency->code ?? 'JOD';
        }

        $original_formula = $this->accountingUtil->balanceFormula();
        $clean_formula = str_ireplace(' as balance', '', $original_formula);
        $converted_formula = "(" . $clean_formula . ") / " . $exchange_rate . " as balance";

        // دالة مساعدة لبناء الاستعلام لتقليل التكرار
        $buildQuery = function($types) use ($business_id, $start_date, $end_date, $location_id, $converted_formula) {
            $q = AccountingAccount::leftJoin('accounting_accounts_transactions as AAT', 'AAT.accounting_account_id', '=', 'accounting_accounts.id')
                ->leftJoin('accounting_account_types as AATP', 'AATP.id', '=', 'accounting_accounts.account_sub_type_id')
                ->whereDate('AAT.operation_date', '>=', $start_date)
                ->whereDate('AAT.operation_date', '<=', $end_date)
                ->where('accounting_accounts.business_id', $business_id)
                ->whereIn('accounting_accounts.account_primary_type', (array)$types);

            if (!empty($location_id)) {
                $q->where('AAT.location_id', $location_id);
            }

            return $q->select(DB::raw($converted_formula), 'accounting_accounts.name', 'AATP.name as sub_type')
                     ->groupBy('accounting_accounts.id', 'accounting_accounts.name', 'AATP.name')->get();
        };

        $assets = $buildQuery(['asset']);
        $liabilities = $buildQuery(['liability']);
        $equities = $buildQuery(['equity']);

        return view('accounting::report.balance_sheet')
            ->with(compact('assets', 'liabilities', 'equities', 'start_date', 'end_date', 'currencies', 'exchange_rate', 'currency_code', 'business_locations'));
    }

    /**
     * Income Sheet - قائمة الدخل
     */
public function getIncomeStatement(Request $request)
{
    $business_id = request()->session()->get('user.business_id');
    $location_id = request()->input('location_id');

    // 1. تحديد النطاق الزمني
    $start_date    = !empty(request()->start_date) ? request()->start_date : date('Y-01-01');
    $end_date      = !empty(request()->end_date)   ? request()->end_date   : date('Y-m-t');
    $exchange_rate = (float)request()->input('exchange_rate', 1) ?: 1;

    // متطلبات العرض (Dropdowns)
    $business_locations = \App\BusinessLocation::forDropdown($business_id);
    $currencies = \App\Currency::select('id', DB::raw("CONCAT(currency, ' (', code, ')') as info"))->pluck('info', 'id');
    $currency_id   = request()->input('currency_id');
    $currency_code = !empty($currency_id) ? (\App\Currency::find($currency_id)->code ?? 'JOD') : 'JOD';

    // 2. جلب الإعدادات (Mapping)
    $business = \App\Business::find($business_id);
    $settings  = is_array($business->accounting_settings) ? $business->accounting_settings : json_decode($business->accounting_settings, true);
    $default_map   = $settings['accounting_default_map'] ?? [];

    $revenue_ids   = [];
    $purchase_ids  = [];
    $inventory_ids = [];
    $expense_ids   = [];

    foreach ($default_map as $loc => $map) {
        if (!empty($location_id) && $loc != $location_id) continue;

        if (!empty($map['sale']['payment_account']))      $revenue_ids[]   = $map['sale']['payment_account'];
        if (!empty($map['purchases']['payment_account'])) $purchase_ids[]  = $map['purchases']['payment_account'];
        if (!empty($map['purchases']['deposit_to']))      $inventory_ids[] = $map['purchases']['deposit_to'];
        if (!empty($map['add_quantity']['deposit_to']))   $inventory_ids[] = $map['add_quantity']['deposit_to'];
        if (!empty($map['opening_stock']['deposit_to']))  $inventory_ids[] = $map['opening_stock']['deposit_to'];
        if (!empty($map['expense']['deposit_to']))        $expense_ids[]   = $map['expense']['deposit_to'];
    }

    // 3. جلب كافة الحسابات حسب النوع (لضمان شمول القيود اليدوية)
    $all_income_ids = DB::table('accounting_accounts')->where('business_id', $business_id)
        ->whereIn('account_primary_type', ['income', 'revenue'])->pluck('id')->toArray();

    $all_expense_ids = DB::table('accounting_accounts')->where('business_id', $business_id)
        ->whereIn('account_primary_type', ['expenses', 'expense'])->pluck('id')->toArray();

    $final_revenue_ids = array_unique(array_merge($revenue_ids, $all_income_ids));
    $final_expense_ids = array_unique(array_merge($expense_ids, $all_expense_ids));
    $inventory_ids     = array_unique(array_filter($inventory_ids));
    $purchase_ids      = array_unique(array_filter($purchase_ids));

    // 4. الدالة المساعدة لجلب الأرصدة
    $get_balance = function($account_ids, $primary_types, $date_type = 'current', $debit_positive = true) 
    use ($business_id, $start_date, $end_date, $location_id) {
        
        $query = DB::table('accounting_accounts_transactions as AAT')
            ->join('accounting_accounts as AA', 'AAT.accounting_account_id', '=', 'AA.id')
            ->where('AA.business_id', $business_id);

        if (!empty($account_ids)) {
            $query->whereIn('AA.id', $account_ids);
        } else {
            $query->whereIn('AA.account_primary_type', (array)$primary_types);
        }

        if (!empty($location_id)) {
            $query->where(function($q) use ($location_id) {
                $q->where('AAT.location_id', $location_id)->orWhereNull('AAT.location_id');
            });
        }
if ($date_type === 'opening') {
            // التعديل الجذري: جلب الرصيد الافتتاحي (Opening Stock) مهما كان تاريخه خلال السنة المحددة
            // أو أي رصيد ناتج عن حركات قبل تاريخ بداية التقرير
            $query->where(function($q) use ($start_date, $end_date) {
                $q->where('AAT.sub_type', 'opening_stock')
                  ->whereDate('AAT.operation_date', '<=', $end_date) // أي رصيد افتتاحي مسجل حتى نهاية التقرير
                  ->orWhere(function($sub) use ($start_date) {
                      $sub->whereDate('AAT.operation_date', '<', $start_date)
                          ->where('AAT.sub_type', '!=', 'opening_stock');
                  });
            });
        } elseif ($date_type === 'current') {
            $query->whereDate('AAT.operation_date', '>=', $start_date)
                  ->whereDate('AAT.operation_date', '<=', $end_date);
        } else { 
            $query->whereDate('AAT.operation_date', '<=', $end_date);
        }

        $formula = $debit_positive 
            ? "SUM(IF(AAT.type = 'debit', AAT.amount, -AAT.amount))" 
            : "SUM(IF(AAT.type = 'credit', AAT.amount, -AAT.amount))";

        return $query->select(DB::raw("$formula as balance"))->first()->balance ?? 0;
    };

    // 5. الحسابات النهائية
    
    // الإيرادات
    $total_revenue = $get_balance($final_revenue_ids, ['income', 'revenue'], 'current', false) / $exchange_rate;

    // المشتريات (نعتمد على IDs المشتريات المربوطة)
    $total_purchase = abs($get_balance($purchase_ids, ['expense', 'asset'], 'current', true)) / $exchange_rate;

    // المخزون (أول وآخر المدة)
    $opening_inventory = $get_balance($inventory_ids, ['asset'], 'opening', true) / $exchange_rate;
    $closing_inventory = $get_balance($inventory_ids, ['asset'], 'closing', true) / $exchange_rate;

    // المصاريف الإدارية (إجمالي المصاريف مطروحاً منه المشتريات لتجنب التكرار)
    $total_expenses_raw = $get_balance($final_expense_ids, ['expenses', 'expense'], 'current', true) / $exchange_rate;
    $total_admin_expenses = max(0, $total_expenses_raw - $total_purchase);

    // المعادلات
    $total_cogs   = ($opening_inventory + $total_purchase) - $closing_inventory;
    $gross_profit = $total_revenue - $total_cogs;
    $net_profit   = $gross_profit - $total_admin_expenses;

    return view('accounting::report.income_statement')
        ->with(compact('total_revenue', 'opening_inventory', 'total_purchase', 'closing_inventory', 
                       'total_cogs', 'gross_profit', 'total_admin_expenses', 'net_profit', 
                       'business_locations', 'currency_code', 'currencies', 'exchange_rate', 
                       'start_date', 'end_date', 'location_id'));
}
}