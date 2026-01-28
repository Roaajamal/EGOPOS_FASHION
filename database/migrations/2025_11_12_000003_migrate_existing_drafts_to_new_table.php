<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrateExistingDraftsToNewTable extends Migration
{
    /**
     * Run the migrations.
     * Migrates existing draft transactions from 'transactions' table to 'transaction_drafts'
     *
     * @return void
     */
    public function up()
    {
        // Get all draft transactions
        $drafts = DB::table('transactions')
            ->where('status', 'draft')
            ->whereIn('type', ['sell', 'sell_transfer'])
            ->get();

        if ($drafts->isEmpty()) {
            echo "No drafts found to migrate.\n";
            return;
        }

        echo "Found " . $drafts->count() . " drafts to migrate...\n";

        $migrated = 0;
        $failed = 0;

        foreach ($drafts as $draft) {
            try {
                DB::beginTransaction();

                // Insert into transaction_drafts
                $draftId = DB::table('transaction_drafts')->insertGetId([
                    'business_id' => $draft->business_id,
                    'location_id' => $draft->location_id,
                    'type' => $draft->type,
                    'status' => $draft->status,
                    'sub_status' => $draft->sub_status,
                    'sub_type' => $draft->sub_type,
                    'contact_id' => $draft->contact_id,
                    'customer_group_id' => $draft->customer_group_id,
                    'invoice_no' => $draft->invoice_no,
                    'ref_no' => $draft->ref_no,
                    'source' => $draft->source,
                    'invoice_scheme_id' => $draft->invoice_scheme_id,
                    'transaction_date' => $draft->transaction_date,
                    'total_before_tax' => $draft->total_before_tax ?? 0,
                    'tax_id' => $draft->tax_id,
                    'tax_amount' => $draft->tax_amount ?? 0,
                    'discount_type' => $draft->discount_type ?? 'percentage',
                    'discount_amount' => $draft->discount_amount ?? 0,
                    'shipping_details' => $draft->shipping_details,
                    'shipping_address' => $draft->shipping_address,
                    'shipping_status' => $draft->shipping_status,
                    'delivered_to' => $draft->delivered_to,
                    'shipping_charges' => $draft->shipping_charges ?? 0,
                    'additional_notes' => $draft->additional_notes,
                    'staff_note' => $draft->staff_note,
                    'final_total' => $draft->final_total ?? 0,
                    'expense_category_id' => $draft->expense_category_id,
                    'expense_for' => $draft->expense_for,
                    'commission_agent' => $draft->commission_agent,
                    'document' => $draft->document,
                    'is_direct_sale' => $draft->is_direct_sale ?? 0,
                    'is_quotation' => $draft->is_quotation ?? 0,
                    'is_suspend' => $draft->is_suspend ?? 0,
                    'exchange_rate' => $draft->exchange_rate ?? 1.000,
                    'selling_price_group_id' => $draft->selling_price_group_id,
                    'created_by' => $draft->created_by,
                    'types_of_service_id' => $draft->types_of_service_id,
                    'packing_charge' => $draft->packing_charge,
                    'packing_charge_type' => $draft->packing_charge_type,
                    'service_custom_field_1' => $draft->service_custom_field_1,
                    'service_custom_field_2' => $draft->service_custom_field_2,
                    'service_custom_field_3' => $draft->service_custom_field_3,
                    'service_custom_field_4' => $draft->service_custom_field_4,
                    'is_created_from_api' => $draft->is_created_from_api ?? 0,
                    'res_table_id' => $draft->res_table_id,
                    'res_waiter_id' => $draft->res_waiter_id,
                    'is_export' => $draft->is_export ?? 0,
                    'is_recurring' => $draft->is_recurring ?? 0,
                    'recur_parent_id' => $draft->recur_parent_id,
                    'is_converted' => 0,
                    'converted_to_transaction_id' => null,
                    'converted_at' => null,
                    'converted_by' => null,
                    'created_at' => $draft->created_at,
                    'updated_at' => $draft->updated_at,
                ]);

                // Get sell lines for this draft
                $sellLines = DB::table('transaction_sell_lines')
                    ->where('transaction_id', $draft->id)
                    ->get();

                // Insert sell lines into draft sell lines table
                foreach ($sellLines as $line) {
                    DB::table('transaction_sell_lines_drafts')->insert([
                        'transaction_draft_id' => $draftId,
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'quantity' => $line->quantity,
                        'quantity_returned' => $line->quantity_returned ?? 0,
                        'unit_id' => $line->unit_id,
                        'unit_price_before_discount' => $line->unit_price_before_discount ?? 0,
                        'unit_price' => $line->unit_price,
                        'line_discount_type' => $line->line_discount_type,
                        'line_discount_amount' => $line->line_discount_amount ?? 0,
                        'unit_price_inc_tax' => $line->unit_price_inc_tax,
                        'item_tax' => $line->item_tax,
                        'tax_id' => $line->tax_id,
                        'discount_id' => $line->discount_id,
                        'lot_no_line_id' => $line->lot_no_line_id,
                        'sell_line_note' => $line->sell_line_note,
                        'sub_unit_id' => $line->sub_unit_id,
                        'discount_amount' => $line->discount_amount ?? 0,
                        'res_service_staff_id' => $line->res_service_staff_id,
                        'parent_sell_line_id' => $line->parent_sell_line_id,
                        'children_type' => $line->children_type,
                        'so_line_id' => $line->so_line_id,
                        'so_quantity_invoiced' => $line->so_quantity_invoiced ?? 0,
                        'secondary_unit_quantity' => $line->secondary_unit_quantity ?? 0,
                        'created_at' => $line->created_at,
                        'updated_at' => $line->updated_at,
                    ]);
                }

                // Create mapping record to track old ID -> new ID
                DB::table('draft_migration_mapping')->insert([
                    'old_transaction_id' => $draft->id,
                    'new_draft_id' => $draftId,
                    'migrated_at' => now(),
                ]);

                DB::commit();
                $migrated++;

                echo "Migrated draft ID {$draft->id} → {$draftId}\n";

            } catch (\Exception $e) {
                DB::rollBack();
                $failed++;
                echo "Failed to migrate draft ID {$draft->id}: " . $e->getMessage() . "\n";
                \Log::error("Draft migration failed for ID {$draft->id}: " . $e->getMessage());
            }
        }

        echo "\nMigration completed!\n";
        echo "✅ Migrated: {$migrated}\n";
        echo "❌ Failed: {$failed}\n";
    }

    /**
     * Reverse the migrations.
     * WARNING: This will DELETE all drafts from the new table!
     *
     * @return void
     */
    public function down()
    {
        // Optionally restore drafts back to transactions table
        // For safety, we won't delete anything, just log a warning
        
        echo "WARNING: down() migration not implemented to prevent data loss.\n";
        echo "If you need to restore, do it manually using the draft_migration_mapping table.\n";
        
        \Log::warning('Attempted to rollback draft migration - not implemented');
    }
}

