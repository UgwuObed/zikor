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
        Schema::table('storefronts', function (Blueprint $table) {
            $table->json('bank_details')->nullable()->after('phone');

            $table->dropColumn('payment_methods');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn('bank_details');

            $table->json('payment_methods')->nullable();
        });
    }
};
