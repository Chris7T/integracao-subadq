<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf', 11)->unique()->after('email');
            $table->unsignedBigInteger('subacquirer_id')->after('cpf');
            
            $table->foreign('subacquirer_id')
                ->references('id')
                ->on('subacquirers')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['subacquirer_id']);
            $table->dropColumn(['cpf', 'subacquirer_id']);
        });
    }
};
