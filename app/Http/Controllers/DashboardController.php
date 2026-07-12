<?php

namespace App\Http\Controllers;

use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing('role');
        $role = $user->role?->nombre ?? 'estudiante';

        return view('dashboard', [
            'role' => $role,
            'roleName' => $user->role?->nombre_visible ?? 'Estudiante',
            'panel' => SistemaInterfaz::dashboardPara($role),
            'navegacion' => SistemaInterfaz::navegacionPara($role),
        ]);
    }
}
