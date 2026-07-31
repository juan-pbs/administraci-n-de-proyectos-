<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $coordinacionId = DB::table('roles')->insertGetId([
            'nombre' => 'coordinacion',
            'nombre_visible' => 'Coordinación',
            'descripcion' => 'Administra usuarios, periodos, carreras, grupos, jerarquías, asignaturas, guías y proyectos.',
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $docenteLiderId = DB::table('roles')->insertGetId([
            'nombre' => 'docente_lider',
            'nombre_visible' => 'Docente líder',
            'descripcion' => 'Organiza alumnos y equipos, define el contexto del proyecto y revisa entregas.',
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);

        $rolesCoordinacion = DB::table('roles')
            ->whereIn('nombre', ['direccion_coordinacion', 'encargado_proyectos'])
            ->pluck('id');
        $rolesDocenteLider = DB::table('roles')
            ->whereIn('nombre', ['lider_proyecto', 'docente_asesor'])
            ->pluck('id');

        DB::table('usuarios')->whereIn('rol_id', $rolesCoordinacion)->update(['rol_id' => $coordinacionId]);
        DB::table('usuarios')->whereIn('rol_id', $rolesDocenteLider)->update(['rol_id' => $docenteLiderId]);

        DB::table('roles')->whereIn('id', [...$rolesCoordinacion, ...$rolesDocenteLider])->delete();
    }

    public function down(): void
    {
        $rolesAnteriores = [
            'direccion_coordinacion' => ['Dirección / Coordinación', 'Administra usuarios, periodos, grupos, carga de alumnos, guías integradoras y seguimiento general.'],
            'encargado_proyectos' => ['Encargado de proyectos', 'Administra una carrera por periodo y cuatrimestre.'],
            'lider_proyecto' => ['Líder de proyecto', 'Organiza alumnos, equipos y contexto del proyecto.'],
            'docente_asesor' => ['Docente / Asesor', 'Revisa proyectos y entregables.'],
        ];

        foreach ($rolesAnteriores as $nombre => [$nombreVisible, $descripcion]) {
            DB::table('roles')->insert([
                'nombre' => $nombre,
                'nombre_visible' => $nombreVisible,
                'descripcion' => $descripcion,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);
        }

        $coordinacionId = DB::table('roles')->where('nombre', 'coordinacion')->value('id');
        $direccionId = DB::table('roles')->where('nombre', 'direccion_coordinacion')->value('id');
        $docenteLiderId = DB::table('roles')->where('nombre', 'docente_lider')->value('id');
        $liderId = DB::table('roles')->where('nombre', 'lider_proyecto')->value('id');

        DB::table('usuarios')->where('rol_id', $coordinacionId)->update(['rol_id' => $direccionId]);
        DB::table('usuarios')->where('rol_id', $docenteLiderId)->update(['rol_id' => $liderId]);
        DB::table('roles')->whereIn('nombre', ['coordinacion', 'docente_lider'])->delete();
    }
};
