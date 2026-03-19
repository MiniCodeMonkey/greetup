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
            $table->string('avatar_path')->nullable()->after('password');
            $table->text('bio')->nullable()->after('avatar_path');
            $table->string('location')->nullable()->after('bio');
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('timezone', 50)->default('UTC')->after('longitude');
            $table->json('looking_for')->nullable()->after('timezone');
            $table->enum('profile_visibility', ['public', 'members_only'])->default('public')->after('looking_for');
            $table->timestamp('last_active_at')->nullable()->after('suspended_reason');
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_path',
                'bio',
                'location',
                'latitude',
                'longitude',
                'timezone',
                'looking_for',
                'profile_visibility',
                'last_active_at',
                'deleted_at',
            ]);
        });
    }
};
