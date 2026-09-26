<?php

namespace App\Servicios;

use App\Models\ApartadoGuia;
use App\Models\Entrega;
use App\Models\FirmaDocente;
use App\Models\Proyecto;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FirmasDocentes
{
    public function normalizar(string $bytes): string
    {
        $info = @getimagesizefromstring($bytes);
        if (strlen($bytes) > 2 * 1024 * 1024 || ! $info || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)
            || $info[0] > 2400 || $info[1] > 1200 || $info[0] < 20 || $info[1] < 20) {
            throw ValidationException::withMessages(['firma' => 'Usa PNG o JPG de hasta 2 MB y 2400 × 1200 píxeles.']);
        }
        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            throw ValidationException::withMessages(['firma' => 'La imagen no se pudo leer.']);
        }
        $width = min(800, $info[0]);
        $height = max(1, (int) round($info[1] * $width / $info[0]));
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 255, 255, 127));
        imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
        $tinta = 0;
        for ($y = 0; $y < $height; $y += 3) {
            for ($x = 0; $x < $width; $x += 3) {
                $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                if ($color['alpha'] < 80 && $color['red'] + $color['green'] + $color['blue'] < 660) {
                    $tinta++;
                }
            }
        }
        if ($tinta < 10) {
            imagedestroy($image);
            imagedestroy($source);
            throw ValidationException::withMessages(['firma' => 'La imagen está vacía; dibuja o selecciona una firma visible.']);
        }
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($source);
        imagedestroy($image);

        return base64_encode($png);
    }

    public function guardarRevision(User $docente, Entrega $entrega, array $datos, bool $autoriza): void
    {
        DB::transaction(function () use ($docente, $entrega, $datos, $autoriza) {
            $proyecto = Proyecto::query()->whereKey($entrega->proyecto_id)->lockForUpdate()->firstOrFail();
            $ultima = Entrega::query()->where('proyecto_id', $entrega->proyecto_id)->where('apartado_guia_id', $entrega->apartado_guia_id)->max('version');
            abort_unless((int) $ultima === (int) $entrega->version, 409, 'Existe una versión más reciente; revisa esa entrega.');
            $firma = FirmaDocente::query()->where('docente_id', $docente->id)->first();
            if ($datos['resultado'] === 'aprobada' && (! $autoriza || ! $firma)) {
                throw ValidationException::withMessages(['autorizar_firma' => 'Para aprobar, registra tu firma y autoriza su inclusión en esta versión.']);
            }
            Revision::query()->updateOrCreate(
                ['entrega_id' => $entrega->id, 'revisor_id' => $docente->id],
                [...$datos, 'revisado_en' => now(),
                    'firma_imagen' => $datos['resultado'] === 'aprobada' ? $firma->imagen : null,
                    'firma_sha256' => $datos['resultado'] === 'aprobada' ? $firma->sha256 : null,
                    'firma_contexto' => $datos['resultado'] === 'aprobada' ? $this->contexto($proyecto, $entrega->apartado) : null,
                    'firmado_en' => $datos['resultado'] === 'aprobada' ? now() : null],
            );
            $entrega->update(['estado' => $datos['resultado']]);
        });
    }

    public function contexto(Proyecto $proyecto, ApartadoGuia $apartado): string
    {
        $proyecto->loadMissing(['guiaIntegradora', 'equipo.integrantes', 'equipo.grupoAcademico']);
        $apartado->loadMissing(['asignaturasContribuyentes', 'firmas']);
        $guia = $proyecto->guiaIntegradora;
        $grupo = $proyecto->equipo->grupoAcademico;

        return hash('sha256', json_encode([
            [$proyecto->id, $proyecto->titulo, $proyecto->descripcion, $proyecto->equipo_id],
            [$guia->id, $guia->nombre, $guia->version, $guia->competencias_evaluar, $guia->objetivo_aprendizaje],
            [$grupo->id, $grupo->lider_proyecto_id, $grupo->asignatura_lider_id, $proyecto->equipo->integrantes->pluck('id')->sort()->values()->all()],
            [$apartado->id, $apartado->orden, $apartado->titulo, $apartado->descripcion, app(PlazosProyecto::class)->fecha($proyecto, $apartado)?->toIso8601String(), $apartado->ponderacion, $apartado->requiere_codigo, $apartado->requiere_documento],
            $apartado->firmas->sortBy('id')->map(fn ($f) => [$f->id, $f->docente_id, $f->asignatura_id, $f->etiqueta, $f->requerida])->values()->all(),
            $apartado->asignaturasContribuyentes->sortBy('id')->map(fn ($a) => [$a->id, $a->pivot->requiere_firma, $a->pivot->rol_contribucion])->values()->all(),
        ], JSON_THROW_ON_ERROR));
    }

    // La imagen incluida en el PDF queda marcada para el proyecto y versión autorizados.
    public function contextualizar(Revision $revision, Proyecto $proyecto, Entrega $entrega): string
    {
        $source = imagecreatefromstring(base64_decode($revision->firma_imagen, true));
        $image = imagecreatetruecolor(800, 220);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $scale = min(740 / imagesx($source), 160 / imagesy($source));
        $w = (int) (imagesx($source) * $scale);
        $h = (int) (imagesy($source) * $scale);
        imagecopyresampled($image, $source, (int) ((800 - $w) / 2), (int) ((160 - $h) / 2), 0, 0, $w, $h, imagesx($source), imagesy($source));
        $ink = imagecolorallocate($image, 140, 151, 162);
        $mark = 'PROYECTO '.$proyecto->id.' / ENTREGA '.$entrega->id.' / V'.$entrega->version;
        imagestring($image, 2, 200, 90, $mark, $ink);
        imagestring($image, 3, 170, 180, $mark.' / '.$revision->firmado_en->format('d-m-Y H:i'), $ink);
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);
        imagedestroy($source);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
