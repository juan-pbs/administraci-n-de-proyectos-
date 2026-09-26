<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class ArchivoAplicacion implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid() || $value->getSize() > 150 * 1024 * 1024) {
            $fail('La aplicación debe ser un archivo de hasta 150 MB.');

            return;
        }
        $extension = strtolower($value->getClientOriginalExtension());
        $stream = fopen($value->getRealPath(), 'rb');
        $header = fread($stream, 8);
        $valid = match ($extension) {
            'exe' => str_starts_with($header, 'MZ'),
            'msi' => $header === hex2bin('d0cf11e0a1b11ae1'),
            'appimage' => str_starts_with($header, "\x7fELF"),
            'deb' => $header === "!<arch>\n",
            'dmg' => false,
            'apk' => false,
            default => false,
        };
        if ($extension === 'dmg' && $value->getSize() >= 512) {
            fseek($stream, -512, SEEK_END);
            $valid = fread($stream, 4) === 'koly';
        }
        fclose($stream);
        if ($extension === 'apk') {
            $zip = new ZipArchive;
            if ($zip->open($value->getRealPath()) === true) {
                $valid = $zip->locateName('AndroidManifest.xml') !== false;
                $zip->close();
            }
        }
        if (! $valid) {
            $fail('Usa un APK, EXE, MSI, DMG, AppImage o DEB válido. Para otros paquetes, usa los archivos comprimidos.');
        }
    }
}
