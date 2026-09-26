<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admite las instalaciones donde ya se aplicó la primera migración de esta ampliación.
        if (! Schema::hasColumn('revisiones', 'firma_contexto')) {
            Schema::table('revisiones', fn (Blueprint $table) => $table->string('firma_contexto', 64)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('revisiones', 'firma_contexto')) {
            Schema::table('revisiones', fn (Blueprint $table) => $table->dropColumn('firma_contexto'));
        }
    }
};
