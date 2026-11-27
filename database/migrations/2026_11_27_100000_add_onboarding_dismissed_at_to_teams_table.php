<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Set when a manager hides the "Get your shop set up" checklist.
            $table->timestamp('onboarding_dismissed_at')->nullable()->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('onboarding_dismissed_at');
        });
    }
};
