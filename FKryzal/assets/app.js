const modal = document.querySelector('#confirm-dialog');
let pendingForm = null,
    pendingButton = null;
document.addEventListener(
    'submit',
    (event) => {
        const form = event.target,
            button = event.submitter;
        if (form.dataset.confirmed === 'yes') {
            delete form.dataset.confirmed;
            return;
        }
        if (!button?.dataset.confirm || !modal) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        pendingForm = form;
        pendingButton = button;
        modal.querySelector('[data-confirm-message]').textContent = button.dataset.confirm;
        modal.showModal();
        modal.querySelector('[data-confirm-cancel]').focus();
    },
    true,
);
if (modal) {
    modal.querySelector('[data-confirm-cancel]').addEventListener('click', () => {
        modal.close();
        pendingButton?.focus();
        pendingForm = null;
    });
    modal.querySelector('[data-confirm-accept]').addEventListener('click', () => {
        const form = pendingForm,
            button = pendingButton;
        modal.close();
        pendingForm = null;
        if (form) {
            form.dataset.confirmed = 'yes';
            form.requestSubmit(button);
        }
    });
    modal.addEventListener('cancel', () => {
        pendingForm = null;
    });
}
const dateInputs = document.querySelectorAll('[data-future-date]');
function updateMinimum() {
    const now = new Date(Date.now() + 60000);
    const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
        .toISOString()
        .slice(0, 16);
    dateInputs.forEach((input) => (input.min = local));
}
if (dateInputs.length) {
    updateMinimum();
    dateInputs.forEach((input) => input.addEventListener('focus', updateMinimum));
}
document.querySelectorAll('[data-role-select]').forEach((select) => {
    const field = select.closest('form').querySelector('[data-specialty-field]');
    const update = () => {
        const trainer = select.value === 'entrenador';
        field.hidden = !trainer;
        field.querySelector('select').disabled = !trainer;
        field.querySelector('select').required = trainer;
    };
    select.addEventListener('change', update);
    update();
});
const normalizeSearch = (value) =>
    value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('es')
        .trim();
const search = document.querySelector('[data-search]');
if (search)
    search.addEventListener('input', () => {
        const value = normalizeSearch(search.value);
        document
            .querySelectorAll('[data-filter]')
            .forEach((card) => (card.hidden = !normalizeSearch(card.textContent).includes(value)));
    });
document.querySelectorAll('form').forEach((form) =>
    form.addEventListener('submit', () => {
        if (!form.checkValidity()) return;
        setTimeout(
            () =>
                form
                    .querySelectorAll('button[type="submit"],button:not([type])')
                    .forEach((button) => {
                        button.disabled = true;
                    }),
            0,
        );
    }),
);
const menuButton = document.querySelector('[data-menu]');
if (menuButton)
    menuButton.addEventListener('click', () => {
        const open = document.querySelector('.site-nav').classList.toggle('open');
        menuButton.setAttribute('aria-expanded', String(open));
    });
const gallery = document.querySelector('[data-gallery]');
if (gallery) {
    let current = 0;
    const slides = [...gallery.querySelectorAll('img')];
    document.querySelectorAll('[data-slide]').forEach((button) =>
        button.addEventListener('click', () => {
            slides[current].hidden = true;
            current = (current + Number(button.dataset.slide) + slides.length) % slides.length;
            slides[current].hidden = false;
            document.querySelector('[data-slide-count]').textContent =
                String(current + 1).padStart(2, '0') +
                ' / ' +
                String(slides.length).padStart(2, '0');
        }),
    );
}

document.querySelectorAll('[data-toast]').forEach((toast) => {
    let timer;
    const close = () => {
        clearTimeout(timer);
        toast.classList.add('is-hiding');
        setTimeout(() => toast.remove(), 220);
    };
    const pause = () => clearTimeout(timer);
    const resume = () => {
        clearTimeout(timer);
        if (!toast.matches(':hover') && !toast.contains(document.activeElement))
            timer = setTimeout(close, 5000);
    };
    toast.querySelector('[data-toast-close]').addEventListener('click', close);
    toast.addEventListener('mouseenter', pause);
    toast.addEventListener('mouseleave', resume);
    toast.addEventListener('focusin', pause);
    toast.addEventListener('focusout', () => setTimeout(resume, 0));
    resume();
});

const stickyHeader = document.querySelector('.workspace-topbar,.site-header');
if (stickyHeader && window.ResizeObserver) {
    new ResizeObserver(() => {
        document.documentElement.style.setProperty(
            '--sticky-height',
            stickyHeader.getBoundingClientRect().height + 20 + 'px',
        );
    }).observe(stickyHeader);
}

const photoDialog = document.querySelector('#photo-dialog');
if (photoDialog) {
    const input = photoDialog.querySelector('[data-photo-input]'),
        preview = photoDialog.querySelector('[data-photo-preview]'),
        feedback = photoDialog.querySelector('[data-photo-feedback]'),
        save = photoDialog.querySelector('[data-photo-save]');
    const original = preview.innerHTML;
    let previewUrl = null,
        selection = 0;
    const resetPhoto = () => {
        selection++;
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        input.value = '';
        preview.innerHTML = original;
        feedback.textContent = '';
        feedback.classList.remove('error');
        save.disabled = true;
    };
    document.querySelectorAll('[data-photo-open]').forEach((button) =>
        button.addEventListener('click', () => {
            resetPhoto();
            photoDialog.showModal();
        }),
    );
    photoDialog
        .querySelectorAll('[data-photo-close]')
        .forEach((button) => button.addEventListener('click', () => photoDialog.close()));
    photoDialog.addEventListener('close', resetPhoto);
    input.addEventListener('change', () => {
        const file = input.files[0],
            current = ++selection;
        save.disabled = true;
        feedback.textContent = '';
        feedback.classList.remove('error');
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        preview.innerHTML = original;
        if (!file) return;
        const reject = (message) => {
            feedback.textContent = message;
            feedback.classList.add('error');
            input.value = '';
            save.disabled = true;
        };
        if (
            !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) ||
            file.size > 2 * 1024 * 1024
        ) {
            reject('Elige una imagen JPG, PNG o WebP de hasta 2 MB.');
            return;
        }
        previewUrl = URL.createObjectURL(file);
        const image = new Image();
        image.alt = 'Vista previa de tu foto';
        image.onload = () => {
            if (current !== selection) return;
            if (image.naturalWidth > 6000 || image.naturalHeight > 6000) {
                reject('La imagen no puede superar 6000 píxeles por lado.');
                return;
            }
            preview.replaceChildren(image);
            feedback.textContent = 'Imagen lista para guardar.';
            save.disabled = false;
        };
        image.onerror = () => {
            if (current === selection) reject('No se ha podido abrir la imagen. Elige otra.');
        };
        image.src = previewUrl;
    });
    photoDialog.querySelector('[data-photo-form]').addEventListener('submit', (event) => {
        if (save.disabled) {
            event.preventDefault();
            return;
        }
        save.disabled = true;
        save.textContent = 'Guardando…';
    });
    if (photoDialog.dataset.photoAuto === 'yes') photoDialog.showModal();
}

for (const button of document.querySelectorAll('[data-copy-code]')) {
    const original = button.innerHTML;
    let resetCopy;
    button.addEventListener('click', async () => {
        const code = button.dataset.copyCode,
            feedback = button.closest('.reward-card').querySelector('.copy-feedback');
        try {
            if (navigator.clipboard && window.isSecureContext)
                await navigator.clipboard.writeText(code);
            else {
                const input = document.createElement('textarea');
                input.value = code;
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.append(input);
                input.select();
                const copied = document.execCommand('copy');
                input.remove();
                if (!copied) throw new Error('copy');
            }
            clearTimeout(resetCopy);
            button.classList.add('is-copied');
            button.setAttribute('aria-label', 'Código copiado');
            button.innerHTML =
                '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg><span>Copiado</span>';
            feedback.classList.add('success');
            feedback.innerHTML =
                '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg><span>¡Código copiado!</span>';
            resetCopy = setTimeout(() => {
                feedback.textContent = '';
                feedback.classList.remove('success');
                button.classList.remove('is-copied');
                button.innerHTML = original;
                button.setAttribute('aria-label', 'Copiar código');
            }, 3500);
        } catch {
            feedback.classList.remove('success');
            feedback.textContent = 'No se pudo copiar. Selecciona el código para copiarlo.';
        }
    });
}
const trainerDialog = document.querySelector('#trainer-dialog');
if (trainerDialog) {
    let trainerTrigger;
    for (const button of document.querySelectorAll('[data-trainer-open]'))
        button.addEventListener('click', () => {
            trainerTrigger = button;
            const data = button.dataset,
                avatar = trainerDialog.querySelector('[data-trainer-avatar]');
            avatar.replaceChildren(button.querySelector('.avatar').cloneNode(true));
            trainerDialog.querySelector('#trainer-title').textContent = data.trainerName;
            trainerDialog.querySelector('[data-trainer-specialty]').textContent =
                data.specialty || 'Entrenador de KryZal';
            trainerDialog.querySelector('[data-trainer-class]').textContent =
                data.className + ' · ' + data.classTime;
            const contact = trainerDialog.querySelector('[data-trainer-contact]');
            contact.hidden = data.contact !== '1';
            contact.href =
                data.booked === '1'
                    ? '?page=requests&new=1&class_id=' + encodeURIComponent(data.classId)
                    : '?page=requests&new=1&recipient_id=' + encodeURIComponent(data.trainerId);
            trainerDialog.querySelector('[data-trainer-hint]').hidden = data.contact === '1';
            trainerDialog.showModal();
        });
    trainerDialog
        .querySelector('[data-trainer-close]')
        .addEventListener('click', () => trainerDialog.close());
    trainerDialog.addEventListener('close', () => trainerTrigger?.focus());
    trainerDialog.addEventListener('click', (event) => {
        if (event.target === trainerDialog) {
            const box = trainerDialog.getBoundingClientRect();
            if (
                event.clientX < box.left ||
                event.clientX > box.right ||
                event.clientY < box.top ||
                event.clientY > box.bottom
            )
                trainerDialog.close();
        }
    });
}
for (const button of document.querySelectorAll('[data-copy-text]'))
    button.addEventListener('click', async () => {
        const feedback = button.closest('.local-message').querySelector('.copy-feedback');
        try {
            if (navigator.clipboard && window.isSecureContext)
                await navigator.clipboard.writeText(button.dataset.copyText);
            else {
                const input = document.createElement('textarea');
                input.value = button.dataset.copyText;
                input.style.position = 'fixed';
                document.body.append(input);
                input.select();
                const copied = document.execCommand('copy');
                input.remove();
                if (!copied) throw new Error('copy');
            }
            feedback.textContent = 'Enlace copiado. Ábrelo en una ventana privada o sin sesión.';
        } catch {
            feedback.textContent = 'No se pudo copiar. Abre el mensaje y selecciona el enlace.';
        }
    });

for (const picker of document.querySelectorAll('[data-recipient-picker]')) {
    const search = picker.querySelector('[data-recipient-search]'),
        select = picker.querySelector('[data-recipient-select]');
    const people = [...select.options]
        .filter((option) => option.value !== '0')
        .map((option) => ({
            value: option.value,
            text: option.textContent,
            search: option.dataset.search,
        }));
    const normalize = (value) =>
        value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('es');
    search.addEventListener('input', () => {
        const chosen = select.value,
            query = normalize(search.value.trim()),
            matches = people.filter((person) => normalize(person.search).includes(query));
        select.replaceChildren(new Option('Administración', '0'));
        for (const person of matches) select.add(new Option(person.text, person.value));
        if (chosen !== '0' && !matches.some((person) => person.value === chosen)) {
            const previous = people.find((person) => person.value === chosen);
            if (previous) select.add(new Option(previous.text + ' (seleccionado)', previous.value));
        }
        select.value = chosen;
        picker.querySelector('[data-recipient-empty]').hidden = matches.length > 0;
    });
}

document.addEventListener(
    'error',
    (event) => {
        const image = event.target;
        if (!(image instanceof HTMLImageElement) || !image.parentElement?.matches('.avatar'))
            return;
        const avatar = image.parentElement;
        avatar.textContent = avatar.dataset.avatarInitial || '';
    },
    true,
);

document.querySelectorAll('[data-auto-submit]').forEach((select) => {
    select.addEventListener('change', () => select.form.requestSubmit());
});
