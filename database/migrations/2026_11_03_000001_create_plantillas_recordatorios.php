<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_recordatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->text('texto');
            $table->timestamps();
            $table->unique(['docente_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_recordatorios');
    }
};
