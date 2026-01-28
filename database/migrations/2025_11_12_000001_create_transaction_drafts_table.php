<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionDraftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_drafts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            
            $table->integer('location_id')->unsigned();
            $table->foreign('location_id')->references('id')->on('business_locations');
            
            $table->enum('type', ['sell', 'sell_transfer'])->default('sell');
            $table->enum('status', ['draft', 'quotation', 'proforma'])->default('draft');
            $table->enum('sub_status', ['quotation', 'proforma'])->nullable();
            
            $table->enum('sub_type', ['repair'])->nullable();
            
            $table->integer('contact_id')->unsigned();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            
            $table->integer('customer_group_id')->unsigned()->nullable();
            
            $table->string('invoice_no')->nullable();
            $table->string('ref_no')->nullable();
            $table->string('source')->nullable();
            
            $table->integer('invoice_scheme_id')->unsigned()->nullable();
            $table->foreign('invoice_scheme_id')->references('id')->on('invoice_schemes')->onDelete('cascade');
            
            $table->datetime('transaction_date');
            
            $table->decimal('total_before_tax', 22, 4)->default(0)->comment('Total before tax');
            $table->string('tax_id')->nullable();
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->enum('discount_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('discount_amount', 22, 4)->default(0);
            
            $table->decimal('shipping_details')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('shipping_status')->nullable();
            $table->string('delivered_to')->nullable();
            $table->string('shipping_charges', 22, 4)->default(0);
            
           
            $table->text('staff_note')->nullable();
            
            $table->decimal('final_total', 22, 4)->default(0);
            
            $table->decimal('expense_category_id')->unsigned()->nullable();
            $table->decimal('expense_for')->unsigned()->nullable();
            
            $table->integer('commission_agent')->nullable();
            $table->string('document')->nullable();
            
            $table->boolean('is_direct_sale')->default(0);
            $table->boolean('is_quotation')->default(0);
            $table->boolean('is_suspend')->default(0);
            
            $table->decimal('exchange_rate', 20, 3)->default(1.000);
            
            $table->integer('selling_price_group_id')->unsigned()->nullable();
            
            $table->integer('created_by')->unsigned();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->integer('types_of_service_id')->unsigned()->nullable();
            
            $table->string('packing_charge')->nullable()->comment('Packing charge if applicable');
            $table->enum('packing_charge_type', ['fixed', 'percent'])->nullable();
            
            $table->integer('service_custom_field_1')->nullable();
            $table->text('service_custom_field_2')->nullable();
            $table->text('service_custom_field_3')->nullable();
            $table->text('service_custom_field_4')->nullable();
            
            $table->boolean('is_created_from_api')->default(0);
            
            $table->integer('res_table_id')->unsigned()->nullable()->comment('Restaurant: Table ID');
            $table->integer('res_waiter_id')->unsigned()->nullable()->comment('Restaurant: Waiter ID');
            
            $table->text('additional_notes')->nullable();
            
            $table->boolean('is_export')->default(0);
            $table->boolean('is_recurring')->default(0)->comment('Whether it is a recurring invoice');
            $table->integer('recur_parent_id')->nullable()->comment('Parent invoice ID for recurring invoices');
            
            // Tracking conversion to final invoice
            $table->integer('converted_to_transaction_id')->unsigned()->nullable()->comment('ID of the final transaction after conversion');
            $table->boolean('is_converted')->default(0)->comment('Whether this draft has been converted to final invoice');
            $table->datetime('converted_at')->nullable()->comment('When it was converted to final invoice');
            $table->integer('converted_by')->unsigned()->nullable()->comment('User who converted it');
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('business_id');
            $table->index('location_id');
            $table->index('contact_id');
            $table->index('status');
            $table->index('sub_status');
            $table->index('is_converted');
            $table->index('transaction_date');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_drafts');
    }
}

