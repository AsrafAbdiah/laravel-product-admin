<?php

use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Moves from one global "is_admin" flag to a role per team membership:
 * in each team the first admin becomes the owner, other admins become
 * admins, and everyone else becomes a viewer (the old non-admins could
 * not do anything, so read-only is the closest match).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roleIds = Role::query()->whereNull('team_id')->pluck('id', 'name');

        $memberships = DB::table('team_user')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->orderBy('team_user.team_id')
            ->orderBy('users.id')
            ->get(['team_user.team_id', 'users.id as user_id', 'users.is_admin']);

        $teamsWithOwner = [];
        foreach ($memberships as $membership) {
            $role = Roles::VIEWER;
            if ($membership->is_admin) {
                $role = isset($teamsWithOwner[$membership->team_id]) ? Roles::ADMIN : Roles::OWNER;
                $teamsWithOwner[$membership->team_id] = true;
            }

            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleIds[$role],
                'model_type' => (new User)->getMorphClass(),
                'model_id' => $membership->user_id,
                'team_id' => $membership->team_id,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        $adminRoleIds = Role::query()->whereIn('name', [Roles::OWNER, Roles::ADMIN])->pluck('id');
        $adminUserIds = DB::table('model_has_roles')->whereIn('role_id', $adminRoleIds)->pluck('model_id');
        DB::table('users')->whereIn('id', $adminUserIds)->update(['is_admin' => true]);
    }
};
