<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductBundlesAndAltBarcodes extends Migration
{
    public function up()
    {
        // ============================================
        // 1) مجموعة عروض (حزم): رأس الحزمة
        // ============================================
        Schema::create('product_offer_bundles', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('business_id');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            // null = يطبّق على كل الفروع
            $table->unsignedInteger('location_id')->nullable();
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');

            $table->string('name')->nullable();
            $table->decimal('bundle_price', 20, 4); // السعر الخاص للحزمة كاملة

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->index(['business_id', 'location_id', 'is_active'], 'bundle_status_index');
        });

        // ============================================
        // 2) عناصر الحزمة: المنتجات المكوّنة لها
        // ============================================
        Schema::create('product_offer_bundle_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('bundle_id');
            $table->foreign('bundle_id')->references('id')->on('product_offer_bundles')->onDelete('cascade');

            $table->unsignedInteger('variation_id');
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');

            $table->decimal('quantity', 20, 4)->default(1);

            $table->timestamps();

            $table->index(['bundle_id', 'variation_id'], 'bundle_item_index');
        });

        // ============================================
        // 3) الباركود البديل: عدة باركودات لمنتج واحد
        // ============================================
        Schema::create('product_alt_barcodes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('business_id');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->unsignedInteger('variation_id');
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');

            $table->string('alt_barcode', 191);

            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            // نفس الباركود لا يتكرر داخل نفس النشاط
            $table->unique(['business_id', 'alt_barcode'], 'alt_barcode_unique');
            $table->index(['business_id', 'variation_id'], 'alt_barcode_variation_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_offer_bundle_items');
        Schema::dropIfExists('product_offer_bundles');
        Schema::dropIfExists('product_alt_barcodes');
    }
}
