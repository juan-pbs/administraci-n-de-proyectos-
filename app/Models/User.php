<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nombre', 'correo', 'matricula', 'rol_id', 'carrera_id', 'grupo_academico_id', 'estado', 'contrasena', 'debe_cambiar_contrasena', 'contrasena_actualizada_en'])]
#[Hidden(['contrasena', 'token_recordar'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    /**
     * @return BelongsTo<Carrera, $this>
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    /**
     * @return BelongsTo<GrupoAcademico, $this>
     */
    public function grupoAcademico(): BelongsTo
    {
        return $this->belongsTo(GrupoAcademico::class, 'grupo_academico_id');
    }

    /**
     * @return BelongsToMany<Carrera, $this>
     */
    public function carrerasComoDocente(): BelongsToMany
    {
        return $this->belongsToMany(Carrera::class, 'docentes_carrera', 'docente_id', 'carrera_id')
            ->withPivot(['activo'])
            ->withTimestamps('creado_en', 'actualizado_en');
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->nombre === $role;
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    public function getRememberTokenName(): string
    {
        return 'token_recordar';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correo_verificado_en' => 'datetime',
            'contrasena' => 'hashed',
            'debe_cambiar_contrasena' => 'boolean',
            'contrasena_actualizada_en' => 'datetime',
        ];
    }
}
