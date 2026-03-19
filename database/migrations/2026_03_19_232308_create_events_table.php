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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('description_html')->nullable();
            $table->string('event_type')->default('in_person');
            $table->string('status')->default('draft');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->decimal('venue_latitude', 10, 7)->nullable();
            $table->decimal('venue_longitude', 10, 7)->nullable();
            $table->string('online_link')->nullable();
            $table->string('cover_photo_path')->nullable();
            $table->unsignedInteger('rsvp_limit')->nullable();
            $table->unsignedInteger('guest_limit')->default(0);
            $table->dateTime('rsvp_opens_at')->nullable();
            $table->dateTime('rsvp_closes_at')->nullable();
            $table->boolean('is_chat_enabled')->default(true);
            $table->boolean('is_comments_enabled')->default(true);
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('series_id')->nullable()->constrained('event_series')->nullOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'slug']);
            $table->index(['group_id', 'status', 'starts_at']);
            $table->index(['starts_at', 'status']);
            $table->index(['venue_latitude', 'venue_longitude']);
            $table->index('series_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
