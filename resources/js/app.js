const asyncRegionSelector = (name) => `[data-async-region="${name}"]`;

function iniciarBusquedaPanel() {
    const panel = document.querySelector('[data-busqueda-panel]');
    if (!panel) return;
    const form = panel.querySelector('[data-busqueda-form]');
    const input = form.querySelector('input[type="search"]');
    const list = panel.querySelector('[data-busqueda-resultados]');
    const status = panel.querySelector('[data-busqueda-estado]');
    const empty = panel.querySelector('[data-busqueda-vacia]');
    let timer, controller, version = 0;
    const actualizar = async () => {
        clearTimeout(timer);
        controller?.abort();
        controller = new AbortController();
        const signal = controller.signal;
        const actual = ++version;
        const consulta = input.value.trim();
        status.hidden = consulta === '';
        list.hidden = consulta === '';
        if (!consulta) {
            list.replaceChildren();
            empty.hidden = true;
            list.removeAttribute('aria-busy');
            return;
        }
        const url = new URL(panel.dataset.busquedaUrl, window.location.origin);
        url.searchParams.set('buscar', consulta);
        status.textContent = 'Buscando acciones…';
        list.replaceChildren();
        empty.hidden = true;
        list.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {credentials:'same-origin', headers:{Accept:'application/json'}, signal, cache:'no-store'});
            if (!response.ok) throw new Error('No se pudo buscar. Vuelve a intentarlo o recarga el panel.');
            const {resultados} = await response.json();
            if (actual !== version || signal.aborted) return;
            const fragment = document.createDocumentFragment();
            resultados.forEach((resultado) => {
                const destino = new URL(resultado.ruta, window.location.origin);
                if (destino.origin !== window.location.origin) return;
                const li = document.createElement('li');
                const link = document.createElement('a');
                link.href = destino.href;
                link.className = 'block h-full rounded-md border border-slate-200 p-3 transition hover:border-[#15529A] hover:bg-[#F5F9FE] focus:outline-none focus:ring-2 focus:ring-[#15529A]';
                [['modulo', 'text-xs font-semibold text-[#21A366]'], ['titulo', 'mt-1 block font-bold text-[#0D376D]'], ['descripcion', 'mt-1 block text-sm leading-5 text-slate-500']].forEach(([campo, clases]) => {
                    const span = document.createElement('span');
                    span.className = clases;
                    span.textContent = resultado[campo];
                    link.append(span);
                });
                li.append(link);
                fragment.append(li);
            });
            list.replaceChildren(fragment);
            empty.hidden = resultados.length > 0;
            status.textContent = consulta ? `${resultados.length} acciones relacionadas` : 'Acciones que puedes realizar';
        } catch (error) {
            if (error.name !== 'AbortError' && actual === version) status.textContent = error.message;
        } finally {
            if (actual === version) list.removeAttribute('aria-busy');
        }
    };
    input.addEventListener('input', () => {
        // Descartar respuestas antiguas inmediatamente, incluso antes de terminar de escribir.
        version++;
        controller?.abort();
        clearTimeout(timer);
        timer = setTimeout(actualizar, 180);
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') { input.value = ''; actualizar(); }
    });
    form.addEventListener('submit', (event) => { event.preventDefault(); actualizar(); });
    panel.querySelectorAll('[data-busqueda-ejemplo]').forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        input.value = link.dataset.busquedaEjemplo;
        input.focus();
        actualizar();
    }));
    window.addEventListener('pagehide', () => { clearTimeout(timer); controller?.abort(); });
    window.addEventListener('pageshow', (event) => { if (event.persisted) actualizar(); });
}

function limpiarCamposAcceso() {
    document.querySelectorAll('[data-auth-privado] input:not([type="hidden"])').forEach((input) => {
        if (input.type === 'checkbox') input.checked = false;
        else { input.value = ''; input.defaultValue = ''; }
    });
}

window.addEventListener('pagehide', limpiarCamposAcceso);
window.addEventListener('pageshow', (event) => {
    if (!document.querySelector('[data-auth-privado]')) return;
    const navigation = performance.getEntriesByType('navigation')[0];
    if (event.persisted || navigation?.type === 'back_forward') {
        limpiarCamposAcceso();
        document.querySelectorAll('[data-auth-privado] button').forEach((button) => { button.disabled = true; });
        // Revalidar el paso actual con el servidor, sin repetir el POST anterior.
        window.location.replace(window.location.href);
    } else if (navigation?.type === 'reload') {
        limpiarCamposAcceso();
    }
});

function iniciarRecuperacion() {
    document.querySelectorAll('[data-auth-privado] form, form[data-auth-privado]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.enviando === 'true') { event.preventDefault(); return; }
            form.dataset.enviando = 'true';
            form.querySelectorAll('button[type="submit"]').forEach((button) => { button.disabled = true; });
        });
    });
    document.querySelectorAll('[data-reenviar-codigo]').forEach((form) => {
        const button = form.querySelector('[data-reenviar-boton]');
        const espera = Math.max(0, Number(form.dataset.reenviarEn) - Number(form.dataset.servidorAhora));
        const limite = performance.now() + espera * 1000;
        const actualizar = () => {
            const segundos = Math.max(0, Math.ceil((limite - performance.now()) / 1000));
            button.disabled = segundos > 0 || form.dataset.enviando === 'true';
            button.textContent = segundos > 0 ? `Volver a enviar código en ${String(Math.floor(segundos / 60)).padStart(2, '0')}:${String(segundos % 60).padStart(2, '0')}` : 'Volver a enviar código';
        };
        actualizar();
        const interval = setInterval(actualizar, 1000);
        window.addEventListener('pagehide', () => clearInterval(interval), {once: true});
    });
}

function iniciarFirmas() {
    document.querySelectorAll('[data-firma-form]').forEach((form) => {
        const canvas = form.querySelector('[data-firma-canvas]');
        const context = canvas.getContext('2d');
        const hidden = form.querySelector('[data-firma-datos]');
        const help = form.querySelector('[data-firma-ayuda]');
        let drawing = false;
        let drawn = false;
        context.lineWidth = 3;
        context.lineCap = 'round';
        context.strokeStyle = '#172334';
        const point = (event) => {
            const rect = canvas.getBoundingClientRect();
            return [(event.clientX - rect.left) * canvas.width / rect.width, (event.clientY - rect.top) * canvas.height / rect.height];
        };
        canvas.addEventListener('pointerdown', (event) => {
            drawing = true;
            canvas.setPointerCapture(event.pointerId);
            context.beginPath();
            context.moveTo(...point(event));
        });
        canvas.addEventListener('pointermove', (event) => {
            if (!drawing) return;
            context.lineTo(...point(event));
            context.stroke();
            drawn = true;
            help.textContent = 'Firma dibujada. Confirma tu contraseña y guarda.';
        });
        ['pointerup', 'pointercancel'].forEach((type) => canvas.addEventListener(type, () => { drawing = false; }));
        form.querySelector('[data-firma-limpiar]').addEventListener('click', () => {
            context.clearRect(0, 0, canvas.width, canvas.height);
            hidden.value = '';
            drawn = false;
            help.textContent = 'El dibujo se borró.';
        });
        form.addEventListener('submit', (event) => {
            if (!form.querySelector('input[type=file]').files.length && !drawn) {
                event.preventDefault();
                help.textContent = 'Sube una imagen o dibuja tu firma antes de guardar.';
                return;
            }
            hidden.value = drawn ? canvas.toDataURL('image/png') : '';
        });
    });
}

function iniciarPreviewApartado() {
    document.querySelectorAll('[data-preview-apartado]').forEach((form) => {
        const region = form.parentElement;
        const frame = region.querySelector('[data-preview-frame]');
        const status = region.querySelector('[data-preview-estado]');
        let timer, controller, objectUrl;
        const update = async () => {
            if (!form.reportValidity()) return;
            controller?.abort();
            controller = new AbortController();
            const signal = controller.signal;
            status.textContent = 'Preparando vista previa…';
            try {
                const response = await fetch(form.dataset.previewUrl, {method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {Accept: 'application/pdf, application/json'}, signal});
                if (!response.ok || !response.headers.get('Content-Type')?.includes('application/pdf')) throw new Error('Revisa los datos del apartado para generar la vista previa.');
                const blob = await response.blob();
                if (signal.aborted) return;
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                objectUrl = URL.createObjectURL(blob);
                frame.src = objectUrl;
                status.textContent = 'Vista previa con cambios sin guardar. Pulsa “Guardar apartado” para aplicarlos.';
            } catch (error) {
                if (error.name !== 'AbortError') status.textContent = error.message;
            }
        };
        form.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(update, 800); });
        form.addEventListener('change', () => { clearTimeout(timer); timer = setTimeout(update, 300); });
        form.querySelector('[data-actualizar-preview]').addEventListener('click', () => { clearTimeout(timer); update(); });
        window.addEventListener('pagehide', () => { controller?.abort(); if (objectUrl) URL.revokeObjectURL(objectUrl); });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarBusquedaPanel();
    iniciarRecuperacion();
    iniciarFirmas();
    iniciarPreviewApartado();
    document.querySelector('[data-cargar-demo]')?.addEventListener('click', () => {
        const frame = document.querySelector('[data-demo-frame]');
        frame.src = frame.dataset.url;
        frame.classList.remove('hidden');
    });
});

function setFormBusy(form, busy) {
    const controls = form.querySelectorAll('button, input, select, textarea');

    controls.forEach((control) => {
        if (busy) {
            control.dataset.wasDisabled = control.disabled ? 'true' : 'false';
            control.disabled = true;
        } else if (control.dataset.wasDisabled !== 'true') {
            control.disabled = false;
        }
    });

    const submitter = form.querySelector('button[type="submit"], button:not([type])');
    if (!submitter) {
        return;
    }

    if (busy) {
        submitter.dataset.originalText = submitter.textContent;
        submitter.textContent = 'Guardando...';
    } else if (submitter.dataset.originalText) {
        submitter.textContent = submitter.dataset.originalText;
    }
}

function replaceAsyncRegions(html, targets) {
    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
    const uniqueTargets = new Set(['mensajes', ...targets]);
    let replacedAnyRegion = false;

    uniqueTargets.forEach((target) => {
        const currentRegion = document.querySelector(asyncRegionSelector(target));
        const nextRegion = nextDocument.querySelector(asyncRegionSelector(target));

        if (currentRegion && nextRegion) {
            currentRegion.replaceWith(nextRegion);
            replacedAnyRegion = true;
        }
    });

    return replacedAnyRegion;
}

function showAsyncError(message) {
    const messageRegion = document.querySelector(asyncRegionSelector('mensajes'));

    if (!messageRegion) {
        return;
    }

    const errorContainer = document.createElement('div');
    errorContainer.className = 'mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800';
    errorContainer.textContent = message;

    messageRegion.replaceChildren(errorContainer);
}

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-async-form]');

    if (!form) {
        return;
    }

    event.preventDefault();

    if (form.dataset.asyncBusy === 'true') {
        return;
    }

    const targets = (form.dataset.asyncTargets || '')
        .split(/\s+/)
        .map((target) => target.trim())
        .filter(Boolean);

    const formData = new FormData(form);

    form.dataset.asyncBusy = 'true';
    setFormBusy(form, true);

    try {
        const method = (form.method || 'POST').toUpperCase();
        const url = new URL(form.action, window.location.href);

        if (method === 'GET') {
            url.search = new URLSearchParams(formData).toString();
        }

        const response = await fetch(url, {
            method,
            body: method === 'GET' ? undefined : formData,
            headers: {
                Accept: 'text/html',
            },
            credentials: 'same-origin',
        });

        const html = await response.text();

        const replacedAnyRegion = replaceAsyncRegions(html, targets);

        if (response.ok && method === 'GET') {
            window.history.replaceState({}, '', url);
        }

        if (!response.ok) {
            if (replacedAnyRegion) {
                return;
            }

            throw new Error('No se pudo completar la accion.');
        }
    } catch (error) {
        showAsyncError(error.message || 'No se pudo completar la accion.');
    } finally {
        if (form.isConnected) {
            form.dataset.asyncBusy = 'false';
            setFormBusy(form, false);
        }
    }
});

document.addEventListener('click', async (event) => {
    const link = event.target.closest('a[data-async-link]');
    if (!link) {
        return;
    }

    event.preventDefault();
    const targets = (link.dataset.asyncTargets || '')
        .split(/\s+/)
        .map((target) => target.trim())
        .filter(Boolean);

    try {
        const response = await fetch(link.href, {
            headers: { Accept: 'text/html' },
            credentials: 'same-origin',
        });
        const html = await response.text();
        if (!response.ok || !replaceAsyncRegions(html, targets)) {
            throw new Error('No se pudo actualizar la lista.');
        }
        window.history.replaceState({}, '', link.href);
    } catch (error) {
        showAsyncError(error.message || 'No se pudo actualizar la lista.');
    }
});
