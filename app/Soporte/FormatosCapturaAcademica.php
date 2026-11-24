<?php

namespace App\Soporte;

class FormatosCapturaAcademica
{
    public const MATRICULA_DOCENTE = '[A-Za-z0-9.\x2D]+';

    public const NOMBRE_PERIODO = '(?!.*[\p{Default_Ignorable_Code_Point}\p{Extended_Pictographic}\p{Me}])[\p{L}\p{M}\p{N} .\x2D–\x2F\x28\x29]+';

    public const NOMBRE_CARRERA = '(?!.*[\p{Default_Ignorable_Code_Point}\p{Extended_Pictographic}\p{Me}])[\p{L}\p{M}\p{N} .,:;\x28\x29\x2F+&\x2D–\x27’]+';

    public const CLAVE_CARRERA = '[A-Za-z0-9_\x2D]+';

    public const MENSAJE_MATRICULA_DOCENTE = 'La matrícula del docente solo admite letras de A a Z, números, puntos y guiones.';

    public const MENSAJE_NOMBRE_PERIODO = 'El nombre del periodo solo admite letras, números, espacios, puntos, guiones, diagonales y paréntesis.';

    public const MENSAJE_NOMBRE_CARRERA = 'El nombre de la carrera solo admite letras, números, espacios y los signos . , : ; ( ) / + & - – y apóstrofos.';

    public const MENSAJE_CLAVE_CARRERA = 'La clave de la carrera solo admite letras de A a Z, números, guiones y guiones bajos.';

    public static function regla(string $patron): string
    {
        return 'regex:/\A'.$patron.'\z/u';
    }
}
