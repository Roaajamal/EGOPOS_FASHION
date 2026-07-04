<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSpecialOffers extends Migration
{
    public function up()
    {
        // رأس العرض الخاص
        Schema::create('product_special_offers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('business_id');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            // null = يطبّق على كل الفروع
            $table->unsignedInteger('location_id')->nullable();
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');

            $table->string('name');

            // bogo = اشتري X واحصل على Y مجاناً
            // nth_percent = اشتري X والقطعة/القطع التالية بخصم %
            // percent_items = خصm % على الأصناف المحددة
            $table->enum('offer_type', ['bogo', 'nth_percent', 'percent_items']);

            $table->decimal('buy_qty', 20, 4)->default(1);   // العدد المطلوب شراؤه (bogo/nth)
            $table->decimal('free_qty', 20, 4)->default(1);  // المجاني (bogo) أو عدد القطع المخصومة (nth)
            $table->decimal('percent', 8, 2)->default(0);    // النسبة (nth_percent/percent_items)

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->index(['business_id', 'location_id', 'is_active'], 'special_offer_status_index');
        });

        // الأصناف المشمولة بالعرض الخاص
        Schema::create('product_special_offer_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('special_offer_id');
            $table->foreign('special_offer_id')->references('id')->on('product_special_offers')->onDelete('cascade');

            $table->unsignedInteger('variation_id');
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');

            $table->timestamps();

            $table->index(['special_offer_id', 'variation_id'], 'special_offer_item_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_special_offer_items');
        Schema::dropIfExists('product_special_offers');
    }
}
