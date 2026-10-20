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
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('dysmenorrhea')->nullable()->after('history');
            $table->unsignedTinyInteger('pelvic_pain')->nullable()->after('dysmenorrhea');
            $table->unsignedTinyInteger('dyspareunia')->nullable()->after('pelvic_pain');
            $table->unsignedTinyInteger('digestive_issues')->nullable()->after('dyspareunia');
            $table->unsignedTinyInteger('urinary_issues')->nullable()->after('digestive_issues');
            $table->unsignedTinyInteger('fatigue')->nullable()->after('urinary_issues');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropColumn([
                'dysmenorrhea',
                'pelvic_pain',
                'dyspareunia',
                'digestive_issues',
                'urinary_issues',
                'fatigue',
            ]);
        });
    }
};
