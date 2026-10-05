<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The trip's timeline: every status change, edit, document link and payment,
 * with who did it and when. `created_at` only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('trip_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('type', 32);
            $table->text('message')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['company_id', 'trip_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_events');
    }
};
