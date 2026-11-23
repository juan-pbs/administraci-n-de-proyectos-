<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class CierreProyecto extends Model
{
    protected $table = 'cierres_proyectos';

    protected $guarded = ['id'];

    protected $attributes = ['estado' => 'pendiente', 'ronda' => 1];

    protected function casts(): array
    {
        return ['pendientes' => 'array', 'detectado_en' => 'datetime', 'decidido_en' => 'datetime'];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function prorrogas(): HasMany
    {
        return $this->hasMany(ProrrogaApartado::class, 'cierre_proyecto_id');
    }

    public function fechaProrrogaActual(): ?Carbon
    {
        return $this->prorrogas->where('ronda', $this->ronda)->max('fecha_limite');
    }

    public function etiquetaEstado(): string
    {
        $fecha = in_array($this->estado, ['prorroga', 'pendiente'], true) ? $this->fechaProrrogaActual() : null;
        if ($fecha && ! $fecha->isFuture()) {
            return 'Prórroga vencida';
        }

        return $this->estado === 'prorroga' ? 'Prórroga' : ucfirst($this->estado);
    }
}
