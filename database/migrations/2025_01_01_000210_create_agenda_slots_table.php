<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_day_id')->constrained('agenda_days')->cascadeOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('title')->default('TBD');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();

            $table->unique(['agenda_day_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_slots');
    }
};
