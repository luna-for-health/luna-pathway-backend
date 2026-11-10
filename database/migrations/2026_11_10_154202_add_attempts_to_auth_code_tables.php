<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables holding 6-digit codes that need a failed-attempt counter.
     *
     * @var list<string>
     */
    private array $codeTables = ['magic_codes', 'secretary_password_resets'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->codeTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->codeTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('attempts');
            });
        }
    }
};
