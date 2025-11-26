<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_number')->comment('1-based day index within the event');
            $table->date('date');
            $table->timestamps();

            $table->unique('day_number');
            $table->unique('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_days');
    }
};
