<?php

use App\Support\Roles;
use Illuminate\Database\Migrations\Migration;

/**
 * Roles and permissions are reference data the app can't run without, so
 * they are created by a migration (every environment gets them) rather
 * than only by the demo seeder.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Roles::sync();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The permission tables' own migration drops everything.
    }
};
