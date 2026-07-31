<x-pagina-error
    codigo="429"
    titulo="Demasiadas solicitudes"
    mensaje="Se realizaron demasiados intentos en poco tiempo. Espera un momento antes de continuar."
    detalle="Esta medida ayuda a proteger tu cuenta y la disponibilidad del sistema."
    tono="ambar"
    :reintentar="true"
/>
