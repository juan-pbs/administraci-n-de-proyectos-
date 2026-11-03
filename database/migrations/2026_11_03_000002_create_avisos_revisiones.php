<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avisos_revisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('clave', 64)->unique();
            $table->string('origen', 20);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('programado_en');
            $table->timestamp('despachado_en')->nullable();
            $table->timestamp('enviado_en')->nullable();
            $table->timestamps();
            $table->index(['estado', 'programado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_revisiones');
    }
};
