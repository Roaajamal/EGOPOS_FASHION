<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * عمود لتخزين توليفات اللون/المقاس للمنتجات المتغيرة (لطباعة الباركود).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'size_color_combinations')) {
                $table->longText('size_color_combinations')->nullable()->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'size_color_combinations')) {
                $table->dropColumn('size_color_combinations');
            }
        });
    }
};
