<x-correo-layout titulo="Recuperación de contraseña" :mensaje="$message">
    <p style="margin:0 0 16px;">Escribe este código en la vista de recuperación del sistema:</p>
    <p style="margin:0 0 20px;padding:18px;background:#EAF7EF;border-left:4px solid #21A366;color:#0D376D;font-size:28px;font-weight:bold;letter-spacing:5px;text-align:center;">{{ $codigo }}</p>
    <p style="margin:0 0 16px;">Es válido durante 10 minutos y solo puede utilizarse una vez. No lo compartas.</p>
    <p style="margin:0;color:#64748b;font-size:14px;">Si no solicitaste este código, puedes ignorar este correo. Tu contraseña sigue siendo la misma.</p>
</x-correo-layout>
