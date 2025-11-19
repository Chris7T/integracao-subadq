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
        Schema::create('pix', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subacquirer_id');
            $table->string('external_id')->nullable();
            $table->string('pix_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status');
            $table->string('payer_name')->nullable();
            $table->string('payer_cpf', 11)->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('subacquirer_id')
                ->references('id')
                ->on('subacquirers')
                ->onDelete('restrict');

            $table->index('external_id');
            $table->index('pix_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index(['subacquirer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pix');
    }
};
