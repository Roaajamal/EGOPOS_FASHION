<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BusinessLocation;
use App\Transaction;
use App\TransactionSellLine;
use App\Variation;
use App\Product;
use App\Contact;
use App\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EgoInvoiceController extends Controller
{
    public function index(Request $request)
    {
        // التحقق من الصلاحية إذا كان middleware يعمل
        // إذا كان لديك package spatie/laravel-permission يمكنك استخدام:
        // if (!auth()->user()->can('sales_report.view')) {
        //     abort(403, 'عذراً، ليس لديك صلاحية عرض هذا التقرير');
        // }
        
        // جلب business_id من المستخدم الحالي
        $business_id = auth()->user()->business_id;
        
        // جلب فروع هذا business فقط
        $branches = BusinessLocation::where('business_id', $business_id)->get();
        
        // استعلام البيانات بناءً على الفلاتر
        $reportData = [];
        $summaryData = [];
        $detailedData = [];
        
        if ($request->has('start_date') && $request->has('end_date')) {
            if ($request->report_type == 'summary') {
                $reportData = $this->getSummaryData($request);
                $summaryData = $reportData;
            } else {
                $reportData = $this->getDetailedData($request);
                $detailedData = $reportData;
            }
        }
        
        return view('report.invoice_statement', compact('branches', 'summaryData', 'detailedData'));
    }
    
    public function getSummaryData(Request $request)
    {
        $business_id = auth()->user()->business_id;
        
        // استعلام المبيعات
        $salesQuery = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('users', 'transactions.created_by', '=', 'users.id')
            ->select(
                'transactions.id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.final_total',
                'transactions.total_before_tax',
                'transactions.tax_amount',
                'transactions.discount_amount',
                'transactions.payment_status',
                'bl.name as branch_name',
                'contacts.name as customer_name',
                'contacts.mobile as customer_mobile',
                DB::raw("CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as created_by_name")
            );
        
        // فلترة حسب التاريخ
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $salesQuery->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
        }
        
        // فلترة حسب الفرع
        if ($request->filled('branch_id')) {
            $salesQuery->where('transactions.location_id', $request->branch_id);
        }
        
        // فلترة حسب نوع العملية
        if ($request->filled('transaction_type')) {
            if ($request->transaction_type == 'sales') {
                $transactions = $salesQuery->get();
                $returns = collect([]);
            } elseif ($request->transaction_type == 'returns') {
                // استعلام المرتجعات
                $returnsQuery = Transaction::where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell_return')
                    ->where('transactions.status', 'final')
                    ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
                    ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                    ->leftJoin('transactions as parent', 'transactions.return_parent_id', '=', 'parent.id')
                    ->select(
                        'transactions.id',
                        'transactions.invoice_no',
                        'transactions.transaction_date',
                        'transactions.final_total',
                        'transactions.total_before_tax',
                        'transactions.tax_amount',
                        'transactions.discount_amount',
                        'transactions.payment_status',
                        'bl.name as branch_name',
                        'contacts.name as customer_name',
                        'contacts.mobile as customer_mobile',
                        'parent.invoice_no as parent_invoice'
                    );
                
                if ($request->has('start_date') && $request->has('end_date')) {
                    $returnsQuery->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
                }
                
                if ($request->filled('branch_id')) {
                    $returnsQuery->where('transactions.location_id', $request->branch_id);
                }
                
                $transactions = collect([]);
                $returns = $returnsQuery->get();
            } else {
                // كل العمليات
                $transactions = $salesQuery->get();
                
                $returnsQuery = Transaction::where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell_return')
                    ->where('transactions.status', 'final')
                    ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
                    ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                    ->leftJoin('transactions as parent', 'transactions.return_parent_id', '=', 'parent.id')
                    ->select(
                        'transactions.id',
                        'transactions.invoice_no',
                        'transactions.transaction_date',
                        'transactions.final_total',
                        'transactions.total_before_tax',
                        'transactions.tax_amount',
                        'transactions.discount_amount',
                        'transactions.payment_status',
                        'bl.name as branch_name',
                        'contacts.name as customer_name',
                        'contacts.mobile as customer_mobile',
                        'parent.invoice_no as parent_invoice'
                    );
                
                if ($request->has('start_date') && $request->has('end_date')) {
                    $returnsQuery->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
                }
                
                if ($request->filled('branch_id')) {
                    $returnsQuery->where('transactions.location_id', $request->branch_id);
                }
                
                $returns = $returnsQuery->get();
            }
        } else {
            // الافتراضي كل العمليات
            $transactions = $salesQuery->get();
            
            $returnsQuery = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell_return')
                ->where('transactions.status', 'final')
                ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
                ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('transactions as parent', 'transactions.return_parent_id', '=', 'parent.id')
                ->select(
                    'transactions.id',
                    'transactions.invoice_no',
                    'transactions.transaction_date',
                    'transactions.final_total',
                    'transactions.total_before_tax',
                    'transactions.tax_amount',
                    'transactions.discount_amount',
                    'transactions.payment_status',
                    'bl.name as branch_name',
                    'contacts.name as customer_name',
                    'contacts.mobile as customer_mobile',
                    'parent.invoice_no as parent_invoice'
                );
            
            if ($request->has('start_date') && $request->has('end_date')) {
                $returnsQuery->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
            }
            
            if ($request->filled('branch_id')) {
                $returnsQuery->where('transactions.location_id', $request->branch_id);
            }
            
            $returns = $returnsQuery->get();
        }
        
        // حساب الإجماليات
        $totals = [
            'total_sales' => 0,
            'total_returns' => 0,
            'net_total' => 0,
            'total_tax' => 0,
            'total_discount' => 0,
            'paid_amount' => 0,
            'due_amount' => 0,
            'partial_amount' => 0
        ];
        
        // معالجة المبيعات
        foreach ($transactions as $transaction) {
            $totals['total_sales'] += $transaction->final_total;
            $totals['total_tax'] += $transaction->tax_amount;
            $totals['total_discount'] += $transaction->discount_amount;
            $totals['net_total'] += $transaction->final_total;
            
            if ($transaction->payment_status == 'paid') {
                $totals['paid_amount'] += $transaction->final_total;
            } elseif ($transaction->payment_status == 'due') {
                $totals['due_amount'] += $transaction->final_total;
            } elseif ($transaction->payment_status == 'partial') {
                $totals['partial_amount'] += $transaction->final_total;
            }
        }
        
        // معالجة المرتجعات (قيم سالبة)
        foreach ($returns as $return) {
            $totals['total_returns'] += $return->final_total;
            $totals['total_tax'] -= $return->tax_amount;
            $totals['total_discount'] -= $return->discount_amount;
            $totals['net_total'] -= $return->final_total;
        }
        
        return [
            'sales' => $transactions,
            'returns' => $returns,
            'totals' => $totals
        ];
    }
    
    public function getDetailedData(Request $request)
    {
        $business_id = auth()->user()->business_id;
        
        // استعلام سطور المبيعات
        $salesLinesQuery = TransactionSellLine::where('transactions.business_id', $business_id)
            ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->leftJoin('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->leftJoin('products', 'variations.product_id', '=', 'products.id')
            ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
            ->select(
                'transactions.id as transaction_id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.type',
                'transactions.payment_status',
                'products.name as product_name',
                'variations.name as variation_name',
                'variations.sub_sku as sku',
                'transaction_sell_lines.quantity',
                'transaction_sell_lines.unit_price',
                'transaction_sell_lines.unit_price_inc_tax',
                'transaction_sell_lines.item_tax',
                'transaction_sell_lines.line_discount_amount',
                'tax_rates.name as tax_name',
                'tax_rates.amount as tax_rate',
                'bl.name as branch_name',
                'contacts.name as customer_name',
                'contacts.mobile as customer_mobile',
                DB::raw('(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as line_total')
            )
            ->where('transactions.status', 'final')
            ->where('transactions.type', 'sell');
        
        // فلترة حسب التاريخ
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            
            $salesLinesQuery->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
        }
        
        // فلترة حسب الفرع
        if ($request->filled('branch_id')) {
            $salesLinesQuery->where('transactions.location_id', $request->branch_id);
        }
        
        $salesData = $salesLinesQuery->get();
        
        // استعلام سطور المرتجعات
        $returnsLinesQuery = TransactionSellLine::where('transactions.business_id', $business_id)
            ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->leftJoin('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->leftJoin('products', 'variations.product_id', '=', 'products.id')
            ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
            ->leftJoin('transactions as parent', 'transactions.return_parent_id', '=', 'parent.id')
            ->select(
                'transactions.id as transaction_id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.type',
                'transactions.payment_status',
                'products.name as product_name',
                'variations.name as variation_name',
                'variations.sub_sku as sku',
                'transaction_sell_lines.quantity',
                'transaction_sell_lines.unit_price',
                'transaction_sell_lines.unit_price_inc_tax',
                'transaction_sell_lines.item_tax',
                'transaction_sell_lines.line_discount_amount',
                'tax_rates.name as tax_name',
                'tax_rates.amount as tax_rate',
                'bl.name as branch_name',
                'contacts.name as customer_name',
                'contacts.mobile as customer_mobile',
                'parent.invoice_no as parent_invoice',
                DB::raw('(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) * -1 as line_total')
            )
            ->where('transactions.status', 'final')
            ->where('transactions.type', 'sell_return');
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $returnsLinesQuery->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
        }
        
        if ($request->filled('branch_id')) {
            $returnsLinesQuery->where('transactions.location_id', $request->branch_id);
        }
        
        $returnsData = $returnsLinesQuery->get();
        
        // دمج البيانات حسب نوع التقرير
        if ($request->filled('transaction_type')) {
            if ($request->transaction_type == 'sales') {
                $data = $salesData;
            } elseif ($request->transaction_type == 'returns') {
                $data = $returnsData;
            } else {
                $data = $salesData->merge($returnsData);
            }
        } else {
            $data = $salesData->merge($returnsData);
        }
        
        // حساب الإجماليات
        $totals = [
            'total_quantity' => 0,
            'total_amount' => 0,
            'total_tax' => 0,
            'total_discount' => 0,
            'total_sales_amount' => 0,
            'total_returns_amount' => 0
        ];
        
        foreach ($data as $item) {
            $totals['total_quantity'] += $item->type == 'sell' ? $item->quantity : -$item->quantity;
            $totals['total_amount'] += $item->line_total;
            $totals['total_tax'] += $item->item_tax;
            $totals['total_discount'] += $item->line_discount_amount;
            
            if ($item->type == 'sell') {
                $totals['total_sales_amount'] += $item->line_total;
            } else {
                $totals['total_returns_amount'] += abs($item->line_total);
            }
        }
        
        return [
            'detailed_items' => $data,
            'totals' => $totals
        ];
    }
    
    public function getInvoiceDetails($id)
    {
        $business_id = auth()->user()->business_id;
        
        $transaction = Transaction::where('business_id', $business_id)
            ->with([
                'contact',
                'business_location',
                'sell_lines' => function($q) {
                    $q->with(['product', 'variation', 'tax']);
                },
                'payment_lines' => function($q) {
                    $q->with(['payment_account']);
                }
            ])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }
}