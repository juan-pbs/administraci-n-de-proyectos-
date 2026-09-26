<?php

use App\Models\User;
use App\Servicios\AvisosAsignaciones;
use App\Servicios\CierresProyectos;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('asignaciones:notificar', function (AvisosAsignaciones $avisos) {
    $this->info('Avisos despachados: '.$avisos->sincronizar());
})->purpose('Programa avisos de asignaciones y recordatorios de entregas');

Schedule::command('asignaciones:notificar')->everyMinute()->withoutOverlapping();

Artisan::command('cierres:revisar {--docente= : Matrícula del líder para limitar la revisión}', function (CierresProyectos $servicio) {
    $docente = $this->option('docente') ? User::where('matricula', $this->option('docente'))->firstOrFail() : null;
    $this->info('Avisos de cierre despachados: '.$servicio->sincronizar($docente));
})->purpose('Detecta documentos pendientes al terminar el periodo o una prórroga y avisa al líder');

Schedule::command('cierres:revisar')->everyMinute()->withoutOverlapping();
