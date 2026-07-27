const asyncRegionSelector = (name) => `[data-async-region="${name}"]`;

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
        const response = await fetch(form.action, {
            method: form.method || 'POST',
            body: formData,
            headers: {
                Accept: 'text/html',
            },
            credentials: 'same-origin',
        });

        const html = await response.text();

        const replacedAnyRegion = replaceAsyncRegions(html, targets);

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
