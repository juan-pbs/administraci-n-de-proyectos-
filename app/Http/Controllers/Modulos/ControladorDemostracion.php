<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\ProductoCodigo;
use App\Rules\UrlDemostracion;
use App\Soporte\SistemaInterfaz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ControladorDemostracion extends Controller
{
    public function mostrar(Request $request, ProductoCodigo $producto)
    {
        $usuario = $request->user()->loadMissing('role');
        $producto->loadMissing('entrega.apartado', 'entrega.equipo.grupoAcademico', 'proyecto');
        abort_unless($usuario->hasRole('docente_lider') && $producto->entrega?->apartado?->requiere_codigo
            && $producto->entrega->equipo->grupoAcademico->newQuery()->whereKey($producto->entrega->equipo->grupo_academico_id)->conMateriaLiderDelDocente($usuario->id)->exists(), 403);
        abort_unless($producto->demostracion_url, 404);
        abort_if(Validator::make(['url' => $producto->demostracion_url], ['url' => new UrlDemostracion])->fails(), 422);

        return response()->view('modulos.docente-lider.demostracion', [
            'producto' => $producto, 'navegacion' => SistemaInterfaz::navegacionPara('docente_lider'), 'roleName' => $usuario->role->nombre_visible,
        ])->withHeaders([
            'Cache-Control' => 'no-store, private', 'X-Frame-Options' => 'SAMEORIGIN', 'Referrer-Policy' => 'no-referrer',
            'Content-Security-Policy' => "frame-src https:; object-src 'none'; base-uri 'self'; form-action 'self'",
        ]);
    }
}
