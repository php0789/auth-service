<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('event_type', 150)->index();
            $table->ulid('aggregate_id')->index();
            $table->json('payload');
            $table->json('metadata');
            $table->timestamp('occurred_at')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
