<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDraftMigrationMappingTable extends Migration
{
    /**
     * Run the migrations.
     * Creates a mapping table to track which old transaction IDs were migrated to new draft IDs
     *
     * @return void
     */
    public function up()
    {
        Schema::create('draft_migration_mapping', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('old_transaction_id')->unsigned()->comment('Original ID in transactions table');
            $table->integer('new_draft_id')->unsigned()->comment('New ID in transaction_drafts table');
            $table->timestamp('migrated_at')->nullable();
            
            $table->index('old_transaction_id');
            $table->index('new_draft_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('draft_migration_mapping');
    }
}

