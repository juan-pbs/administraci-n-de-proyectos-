<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recuperaciones_contrasenas', function (Blueprint $table) {
            $table->id();
            $table->char('correo_hash', 64)->unique();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('codigo_hash')->nullable();
            $table->char('navegador_hash', 64)->nullable();
            $table->char('autorizacion_hash', 64)->nullable();
            $table->timestamp('reenviar_en')->nullable();
            $table->timestamp('expira_en')->nullable();
            $table->timestamp('verificado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recuperaciones_contrasenas');
    }
};
