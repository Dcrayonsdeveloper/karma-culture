<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names', [
            'permissions' => 'permissions',
            'roles' => 'roles',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        $columnNames = config('permission.column_names', [
            'role_pivot_key' => 'role_id',
            'permission_pivot_key' => 'permission_id',
            'model_morph_key' => 'model_id',
            'team_foreign_key' => 'team_id',
        ]);

        Schema::create($tableNames['permissions'] ?? 'permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'] ?? 'roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'] ?? 'model_has_permissions', function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedBigInteger($columnNames['permission_pivot_key'] ?? 'permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key'] ?? 'model_id');

            $table->index([$columnNames['model_morph_key'] ?? 'model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($columnNames['permission_pivot_key'] ?? 'permission_id')
                ->references('id')
                ->on($tableNames['permissions'] ?? 'permissions')
                ->onDelete('cascade');

            $table->primary(
                [$columnNames['permission_pivot_key'] ?? 'permission_id', $columnNames['model_morph_key'] ?? 'model_id', 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        });

        Schema::create($tableNames['model_has_roles'] ?? 'model_has_roles', function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedBigInteger($columnNames['role_pivot_key'] ?? 'role_id');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key'] ?? 'model_id');

            $table->index([$columnNames['model_morph_key'] ?? 'model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($columnNames['role_pivot_key'] ?? 'role_id')
                ->references('id')
                ->on($tableNames['roles'] ?? 'roles')
                ->onDelete('cascade');

            $table->primary(
                [$columnNames['role_pivot_key'] ?? 'role_id', $columnNames['model_morph_key'] ?? 'model_id', 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
        });

        Schema::create($tableNames['role_has_permissions'] ?? 'role_has_permissions', function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedBigInteger($columnNames['permission_pivot_key'] ?? 'permission_id');
            $table->unsignedBigInteger($columnNames['role_pivot_key'] ?? 'role_id');

            $table->foreign($columnNames['permission_pivot_key'] ?? 'permission_id')
                ->references('id')
                ->on($tableNames['permissions'] ?? 'permissions')
                ->onDelete('cascade');

            $table->foreign($columnNames['role_pivot_key'] ?? 'role_id')
                ->references('id')
                ->on($tableNames['roles'] ?? 'roles')
                ->onDelete('cascade');

            $table->primary(
                [$columnNames['permission_pivot_key'] ?? 'permission_id', $columnNames['role_pivot_key'] ?? 'role_id'],
                'role_has_permissions_permission_id_role_id_primary'
            );
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names', [
            'permissions' => 'permissions',
            'roles' => 'roles',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        Schema::drop($tableNames['role_has_permissions'] ?? 'role_has_permissions');
        Schema::drop($tableNames['model_has_roles'] ?? 'model_has_roles');
        Schema::drop($tableNames['model_has_permissions'] ?? 'model_has_permissions');
        Schema::drop($tableNames['roles'] ?? 'roles');
        Schema::drop($tableNames['permissions'] ?? 'permissions');
    }
};
