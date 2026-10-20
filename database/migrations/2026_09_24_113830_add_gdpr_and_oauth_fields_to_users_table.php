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
            $table->boolean('has_accepted_gdpr')->default(false)->after('phone');
            $table->string('provider')->nullable()->after('has_accepted_gdpr');
            $table->string('provider_id')->nullable()->after('provider');
            $table->boolean('is_private_email')->default(false)->after('provider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['has_accepted_gdpr', 'provider', 'provider_id', 'is_private_email']);
        });
    }
};
