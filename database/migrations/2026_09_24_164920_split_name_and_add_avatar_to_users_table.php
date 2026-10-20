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
            $table->string('first_name')->nullable()->after('id');
            // Nullable unlike the source project: the passwordless verify-code flow
            // auto-creates a user from just an email, with no last name available yet.
            $table->string('last_name')->nullable()->after('first_name');
            $table->integer('avatar_id')->default(1)->after('phone');
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->dropColumn(['first_name', 'last_name', 'avatar_id']);
        });
    }
};
