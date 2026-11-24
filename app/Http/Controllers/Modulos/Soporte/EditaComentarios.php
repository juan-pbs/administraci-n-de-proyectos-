<?php

namespace App\Http\Controllers\Modulos\Soporte;

use App\Models\ComentarioRevision;
use App\Models\Entrega;
use App\Soporte\AuditoriaAcademica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait EditaComentarios
{
    private function guardarCambioComentario(Request $request, Entrega $entrega, ComentarioRevision $comentario): RedirectResponse
    {
        $comentario = ComentarioRevision::whereKey($comentario->id)->lockForUpdate()->firstOrFail();
        abort_unless((int) $comentario->autor_id === (int) $request->user()->id
            && (int) $comentario->revision->entrega_id === (int) $entrega->id, 403);
        $antes = $comentario->only(['comentario', 'autor_id', 'revision_id', 'creado_en']);

        if ($request->isMethod('DELETE')) {
            $request->validate(['confirmar_eliminacion' => ['accepted']]);
            $comentario->delete();
            AuditoriaAcademica::registrar($request->user()->id, 'comentarios', $comentario->id, 'ocultar', $antes,
                ['eliminado_en' => $comentario->eliminado_en]);

            return back()->with('status', 'Comentario eliminado.');
        }

        $comentario->update($request->validate(['comentario' => ['required', 'string', 'max:1500']]));
        if ($comentario->wasChanged('comentario')) {
            AuditoriaAcademica::registrar($request->user()->id, 'comentarios', $comentario->id, 'editar', $antes, ['comentario' => $comentario->comentario]);
        }

        return back()->with('status', 'Comentario actualizado.');
    }
}
