<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('settings_fatora', function (Blueprint $table) {
            $table->string('invoice_type', 50)
                  ->nullable()
                  ->after('tin');
                  
        });
    }

    public function down()
    {
        Schema::table('settings_fatora', function (Blueprint $table) {
            $table->string('invoice_type', 10)
                  ->nullable()
                  ->after('tin');
                  
        });
    }
};
