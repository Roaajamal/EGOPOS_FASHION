<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_vouchers', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->string('voucher_no'); // رقم السند (RV-001 / PV-001)
            $table->enum('type', ['receipt', 'payment']); // النوع: قبض أو صرف
            
            // الربط مع الأشخاص (الجهات)
            $table->integer('contact_id')->unsigned()->nullable(); 
            
            // الحساب المالي (صندوق/بنك) المتأثر
            $table->integer('account_id')->unsigned(); 
            
            $table->decimal('amount', 22, 4)->default(0);
            $table->date('operation_date');
            
            $table->string('document')->nullable(); // لرفع صورة الشيك أو الإيصال
            $table->text('note')->nullable(); // البيان (مهم جداً للطباعة)
            
            $table->integer('created_by')->unsigned();
            
            // خانات إضافية قد تحتاجها للطباعة
            $table->string('received_from')->nullable(); // في حال كان الشخص غير مسجل بالنظام
            
            $table->timestamps();

            // الفهارس للسرعة
            $table->index('business_id');
            $table->index('type');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_vouchers');
    }
};
