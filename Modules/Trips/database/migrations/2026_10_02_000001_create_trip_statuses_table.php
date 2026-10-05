<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Board columns, one set per company. The seven defaults are created by
 * TripStatusService::ensureDefaults() rather than seeded here: a migration
 * cannot know which companies exist when a module is enabled.
 *
 * `code` is the stable machine name (booked, assigned, ...) that survives the
 * user renaming a status later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_statuses', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id');
            $table->string('code', 32);
            $table->string('name');
            $table->string('colour', 16)->nullable();
            $table->unsignedInteger('position');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_statuses');
    }
};
