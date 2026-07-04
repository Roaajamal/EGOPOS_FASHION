<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 🆕 طلبات تجديد الاشتراك (يُنشئها المستخدم/الأدمن، ويعتمدها الأدمن)
class CreateEgoRenewalRequests extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('ego_renewal_requests')) {
            Schema::create('ego_renewal_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('requested_by')->nullable();
                $table->unsignedInteger('duration_value')->default(1);
                $table->enum('duration_unit', ['month', 'year'])->default('month');
                $table->text('note')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'status'], 'ego_renewal_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('ego_renewal_requests');
    }
}
