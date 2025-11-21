<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pix', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->renameColumn('external_id', 'transaction_id');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('pix', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
            $table->renameColumn('transaction_id', 'external_id');
            $table->index('external_id');
        });
    }
};

