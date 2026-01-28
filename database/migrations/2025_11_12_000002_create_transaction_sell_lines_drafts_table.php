<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionSellLinesDraftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_sell_lines_drafts', function (Blueprint $table) {
            $table->increments('id');
            
            $table->integer('transaction_draft_id')->unsigned();
            $table->foreign('transaction_draft_id')->references('id')->on('transaction_drafts')->onDelete('cascade');
            
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            $table->integer('variation_id')->unsigned();
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
            
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('quantity_returned', 22, 4)->default(0)->comment('Quanity returned from sell');
            
            $table->integer('unit_id')->unsigned()->nullable()->comment('Unit id for the product');
            $table->decimal('unit_price_before_discount', 22, 4)->default(0)->comment('Unit price before inline discounts');
            $table->decimal('unit_price', 22, 4)->nullable()->comment('Sell price excluding tax');
            $table->enum('line_discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('line_discount_amount', 22, 4)->default(0);
            $table->decimal('unit_price_inc_tax', 22, 4)->nullable()->comment('Sell price including tax');
            
            $table->decimal('item_tax', 22, 4)->comment('Tax for one quantity');
            $table->integer('tax_id')->unsigned()->nullable();
            
            $table->decimal('discount_id')->unsigned()->nullable();
            
            $table->integer('lot_no_line_id')->unsigned()->nullable();
            
            $table->text('sell_line_note')->nullable();
            
            $table->integer('sub_unit_id')->unsigned()->nullable();
            $table->decimal('discount_amount', 22, 4)->default(0);
            
            $table->integer('res_service_staff_id')->unsigned()->nullable();
            
            $table->integer('parent_sell_line_id')->unsigned()->nullable();
            $table->enum('children_type', ['combo', 'modifier'])->nullable()->comment('Type of children for the parent, like modifier or combo');
            
            $table->integer('so_line_id')->unsigned()->nullable()->comment('Linking with sales order line');
            
            $table->decimal('so_quantity_invoiced', 22, 4)->default(0)->comment('Sales order quantity invoiced');
            
            $table->decimal('secondary_unit_quantity', 22, 4)->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('transaction_draft_id');
            $table->index('product_id');
            $table->index('variation_id');
            $table->index('parent_sell_line_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_sell_lines_drafts');
    }
}

