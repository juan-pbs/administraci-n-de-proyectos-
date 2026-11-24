<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupos_academicos', function (Blueprint $table): void {
            $table->string('estado', 20)->default('activo');
        });
    }

    public function down(): void
    {
        Schema::table('grupos_academicos', fn (Blueprint $table) => $table->dropColumn('estado'));
    }
};
