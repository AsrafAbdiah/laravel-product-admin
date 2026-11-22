<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Makes the inventory multi-tenant: every category, product and stock
 * movement now belongs to a team.
 *
 * Existing rows (from before teams existed) are moved into one "My Shop"
 * team, and every existing user becomes a member of it, so nothing is lost.
 */
return new class extends Migration
{
    private const TABLES = ['categories', 'products', 'stock_movements'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teamId = $this->createTeamForExistingData();

        foreach (self::TABLES as $tableName) {
            // 1. Add the column as nullable so existing rows are allowed.
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->nullable()->after('id');
            });

            // 2. Fill it in for existing rows.
            if ($teamId) {
                DB::table($tableName)->update(['team_id' => $teamId]);
            }

            // 3. Now every row has a team: make it required and add the foreign key.
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->nullable(false)->change();
                $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
                $table->index('team_id');
            });
        }

        // Two shops may both have a "Books" category, so names are unique per team.
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['team_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'name']);
            $table->unique('name');
        });

        foreach (array_reverse(self::TABLES) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['team_id']);
                $table->dropIndex(['team_id']);
                $table->dropColumn('team_id');
            });
        }
    }

    /**
     * Returns the id of a team holding all pre-existing data, or null when
     * the database is empty (fresh installs and tests).
     */
    private function createTeamForExistingData(): ?int
    {
        $hasData = DB::table('users')->exists()
            || DB::table('categories')->exists()
            || DB::table('products')->exists();

        if (! $hasData) {
            return null;
        }

        $now = now();
        $teamId = DB::table('teams')->insertGetId([
            'name' => 'My Shop',
            'slug' => 'my-shop-'.Str::lower(Str::random(6)),
            'plan' => 'free',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (DB::table('users')->pluck('id') as $userId) {
            DB::table('team_user')->insertOrIgnore([
                'team_id' => $teamId,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('users')->whereNull('current_team_id')->update(['current_team_id' => $teamId]);

        return $teamId;
    }
};
