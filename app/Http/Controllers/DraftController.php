<?php

namespace App\Http\Controllers;

use App\Models\DraftTransaction;
use App\Models\DraftTransactionSellLine;
use App\Transaction;
use App\TransactionSellLine;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    protected $transactionUtil;
    protected $businessUtil;
    protected $productUtil;

    public function __construct(TransactionUtil $transactionUtil, BusinessUtil $businessUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Store a draft transaction (from POS or direct sale)
     * This is called when saving with status='draft'
     */
    public function storeDraft(Request $request)
    {
        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');
            
            $input = $request->except('_token');
            
            // Prepare draft data
            $draftData = [
                'business_id' => $business_id,
                'location_id' => $input['location_id'],
                'type' => 'sell',
                'status' => $input['status'] ?? 'draft',
                'sub_status' => $input['sub_status'] ?? null,
                'contact_id' => $input['contact_id'],
                'transaction_date' => $this->transactionUtil->uf_date($input['transaction_date'], true),
                'invoice_no' => $input['invoice_no'] ?? null,
                'ref_no' => $input['ref_no'] ?? null,
                'source' => $input['source'] ?? null,
                'invoice_scheme_id' => $input['invoice_scheme_id'] ?? null,
                'total_before_tax' => $this->transactionUtil->num_uf($input['total_before_tax'] ?? 0),
                'tax_id' => $input['tax_rate_id'] ?? null,
                'tax_amount' => $this->transactionUtil->num_uf($input['tax_amount'] ?? 0),
                'discount_type' => $input['discount_type'] ?? 'percentage',
                'discount_amount' => $this->transactionUtil->num_uf($input['discount_amount'] ?? 0),
                'shipping_details' => $input['shipping_details'] ?? null,
                'shipping_address' => $input['shipping_address'] ?? null,
                'shipping_charges' => $this->transactionUtil->num_uf($input['shipping_charges'] ?? 0),
                'additional_notes' => $input['sale_note'] ?? null,
                'staff_note' => $input['staff_note'] ?? null,
                'final_total' => $this->transactionUtil->num_uf($input['final_total']),
                'commission_agent' => $input['commission_agent'] ?? null,
                'is_direct_sale' => isset($input['is_direct_sale']) ? 1 : 0,
                'is_quotation' => isset($input['is_quotation']) ? $input['is_quotation'] : 0,
                'is_suspend' => isset($input['is_suspend']) ? 1 : 0,
                'exchange_rate' => $input['exchange_rate'] ?? 1,
                'selling_price_group_id' => $input['selling_price_group_id'] ?? null,
                'created_by' => $user_id,
                'types_of_service_id' => $input['types_of_service_id'] ?? null,
                'packing_charge' => $input['packing_charge'] ?? null,
                'res_table_id' => $input['res_table_id'] ?? null,
                'res_waiter_id' => $input['res_waiter_id'] ?? null,
                'is_export' => isset($input['is_export']) ? 1 : 0,
                'is_converted' => 0,
            ];
            
            // Create draft transaction
            $draft = DraftTransaction::create($draftData);
            
            // Create sell lines for draft
            if (!empty($input['products'])) {
                foreach ($input['products'] as $product) {
                    $sellLineData = [
                        'transaction_draft_id' => $draft->id,
                        'product_id' => $product['product_id'],
                        'variation_id' => $product['variation_id'],
                        'quantity' => $this->transactionUtil->num_uf($product['quantity']),
                        'unit_id' => $product['product_unit_id'] ?? null,
                        'unit_price_before_discount' => $this->transactionUtil->num_uf($product['unit_price_before_discount'] ?? 0),
                        'unit_price' => $this->transactionUtil->num_uf($product['unit_price']),
                        'line_discount_type' => $product['line_discount_type'] ?? null,
                        'line_discount_amount' => $this->transactionUtil->num_uf($product['line_discount_amount'] ?? 0),
                        'unit_price_inc_tax' => $this->transactionUtil->num_uf($product['unit_price_inc_tax'] ?? 0),
                        'item_tax' => $this->transactionUtil->num_uf($product['item_tax']),
                        'tax_id' => $product['tax_id'] ?? null,
                        'sell_line_note' => $product['sell_line_note'] ?? null,
                        'sub_unit_id' => $product['sub_unit_id'] ?? null,
                    ];
                    
                    DraftTransactionSellLine::create($sellLineData);
                }
            }
            
            DB::commit();

            $output = [
                'success' => 1,
                'msg' => trans('sale.draft_added'),
                'draft_id' => $draft->id,
            ];

            return response()->json($output);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return response()->json([
                'success' => 0,
                'msg' => trans('messages.something_went_wrong') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert draft to final invoice
     * Copies data from transaction_drafts to transactions table
     */
    public function convertToInvoice($draft_id)
    {
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            // Get the draft
            $draft = DraftTransaction::where('business_id', $business_id)
                ->where('id', $draft_id)
                ->with('sell_lines')
                ->firstOrFail();

            // Check if already converted
            if ($draft->is_converted) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => 'هذه المسودة تم تحويلها مسبقاً إلى فاتورة نهائية'
                ]);
            }

            // Prepare transaction data
            $transactionData = [
                'business_id' => $draft->business_id,
                'location_id' => $draft->location_id,
                'type' => $draft->type,
                'status' => 'final', // Changed from draft to final
                'sub_status' => null, // Clear sub_status
                'sub_type' => $draft->sub_type,
                'contact_id' => $draft->contact_id,
                'customer_group_id' => $draft->customer_group_id,
                'ref_no' => $draft->ref_no,
                'source' => $draft->source,
                'invoice_scheme_id' => $draft->invoice_scheme_id,
                'transaction_date' => $draft->transaction_date,
                'total_before_tax' => $draft->total_before_tax,
                'tax_id' => $draft->tax_id,
                'tax_amount' => $draft->tax_amount,
                'discount_type' => $draft->discount_type,
                'discount_amount' => $draft->discount_amount,
                'shipping_details' => $draft->shipping_details,
                'shipping_address' => $draft->shipping_address,
                'shipping_charges' => $draft->shipping_charges,
                'additional_notes' => $draft->additional_notes,
                'staff_note' => $draft->staff_note,
                'final_total' => $draft->final_total,
                'commission_agent' => $draft->commission_agent,
                'is_direct_sale' => $draft->is_direct_sale,
                'is_suspend' => 0, // Clear suspend when converting
                'exchange_rate' => $draft->exchange_rate,
                'selling_price_group_id' => $draft->selling_price_group_id,
                'created_by' => $draft->created_by,
                'types_of_service_id' => $draft->types_of_service_id,
                'packing_charge' => $draft->packing_charge,
                'res_table_id' => $draft->res_table_id,
                'res_waiter_id' => $draft->res_waiter_id,
                'is_export' => $draft->is_export,
                'payment_status' => 'due', // Set initial payment status
            ];

            // Generate new invoice number for final invoice
            $transactionData['invoice_no'] = $this->transactionUtil->getInvoiceNumber(
                $business_id,
                'final',
                $draft->location_id
            );

            // Create final transaction
            $transaction = Transaction::create($transactionData);

            // Copy sell lines
            foreach ($draft->sell_lines as $draftLine) {
                $sellLineData = [
                    'transaction_id' => $transaction->id,
                    'product_id' => $draftLine->product_id,
                    'variation_id' => $draftLine->variation_id,
                    'quantity' => $draftLine->quantity,
                    'unit_id' => $draftLine->unit_id,
                    'unit_price_before_discount' => $draftLine->unit_price_before_discount,
                    'unit_price' => $draftLine->unit_price,
                    'line_discount_type' => $draftLine->line_discount_type,
                    'line_discount_amount' => $draftLine->line_discount_amount,
                    'unit_price_inc_tax' => $draftLine->unit_price_inc_tax,
                    'item_tax' => $draftLine->item_tax,
                    'tax_id' => $draftLine->tax_id,
                    'sell_line_note' => $draftLine->sell_line_note,
                    'sub_unit_id' => $draftLine->sub_unit_id,
                    'res_service_staff_id' => $draftLine->res_service_staff_id,
                ];

                TransactionSellLine::create($sellLineData);
            }

            // Update stock - decrease quantity
            foreach ($draft->sell_lines as $draftLine) {
                if ($draftLine->product->enable_stock) {
                    $this->productUtil->decreaseProductQuantity(
                        $draftLine->product_id,
                        $draftLine->variation_id,
                        $draft->location_id,
                        $draftLine->quantity
                    );
                }
            }

            // Mark draft as converted
            $draft->update([
                'is_converted' => 1,
                'converted_to_transaction_id' => $transaction->id,
                'converted_at' => now(),
                'converted_by' => $user_id,
            ]);

            // Activity log
            $this->transactionUtil->activityLog($transaction, 'added', null, ['note' => 'Converted from draft #' . $draft->id]);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => 'تم تحويل المسودة إلى فاتورة نهائية بنجاح',
                'transaction_id' => $transaction->id,
            ];

            return redirect()
                ->action([\App\Http\Controllers\SellController::class, 'show'], [$transaction->id])
                ->with('status', $output);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => trans('messages.something_went_wrong') . ': ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete a draft
     */
    public function destroy($draft_id)
    {
        if (!auth()->user()->can('draft.delete') && !auth()->user()->can('quotation.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $draft = DraftTransaction::where('business_id', $business_id)
                ->where('id', $draft_id)
                ->firstOrFail();

            // Check if already converted
            if ($draft->is_converted) {
                return response()->json([
                    'success' => 0,
                    'msg' => 'لا يمكن حذف هذه المسودة لأنها تم تحويلها لفاتورة نهائية'
                ]);
            }

            // Delete sell lines first
            DraftTransactionSellLine::where('transaction_draft_id', $draft_id)->delete();

            // Delete draft
            $draft->delete();

            $output = [
                'success' => 1,
                'msg' => trans('lang_v1.draft_deleted_successfully'),
            ];

        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return response()->json($output);
    }

    /**
     * Show draft details in modal
     */
    public function show($draft_id)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('view_own_sell_only')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $draft = DraftTransaction::where('business_id', $business_id)
            ->where('id', $draft_id)
            ->with([
                'contact',
                'sell_lines',
                'sell_lines.product',
                'sell_lines.product.unit',
                'sell_lines.variations',
                'sell_lines.variations.product_variation',
                'location',
                'created_by_user',
                'tax'
            ])
            ->firstOrFail();

        // If converted, get the final transaction
        $final_transaction = null;
        if ($draft->is_converted && $draft->converted_to_transaction_id) {
            $final_transaction = \App\Transaction::find($draft->converted_to_transaction_id);
        }

        // Return HTML only for AJAX modal - NO JavaScript!
        return view('sale_pos.partials.draft_show_modal')
            ->with(compact('draft', 'final_transaction'))
            ->render();
    }
}
