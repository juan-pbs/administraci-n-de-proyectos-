<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControladorModuloGeneral extends Controller
{
    public function mostrar(Request $request, string $modulo): View|RedirectResponse
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';
        $pagina = SistemaInterfaz::pagina($modulo);

        abort_if($pagina === null, 404);

        if (! SistemaInterfaz::puedeVer($rol, $modulo)) {
            return redirect()->route('dashboard');
        }

        return view('modulos.general.mostrar', [
            'active' => $modulo,
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => $pagina,
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
        ]);
    }
}
