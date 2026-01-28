<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_offers', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Business
            $table->unsignedInteger('business_id');
            $table->foreign('business_id')
                ->references('id')
                ->on('business')
                ->onDelete('cascade');

            // Product variation
            $table->unsignedInteger('variation_id');
            $table->foreign('variation_id')
                ->references('id')
                ->on('variations')
                ->onDelete('cascade');

            // Business location
            $table->unsignedInteger('location_id');
            $table->foreign('location_id')
                ->references('id')
                ->on('business_locations')
                ->onDelete('cascade');

            // Offer data
            $table->decimal('min_quantity', 20, 4)->default(1);
            $table->decimal('offer_price', 20, 4);
            $table->enum('price_type', ['fixed', 'percentage', 'override'])->default('fixed');

            // Offer duration
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Notes
            $table->text('notes')->nullable();

            // Created by
            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'variation_id', 'location_id', 'min_quantity'], 'product_offer_main_index');
            $table->index(['business_id', 'location_id', 'is_active'], 'product_offer_status_index');
            $table->index(['start_date', 'end_date'], 'product_offer_date_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_offers');
    }
}
