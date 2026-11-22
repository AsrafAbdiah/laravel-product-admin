import Alpine from 'alpinejs';

/**
 * Toast notifications. The layout passes in the session flash messages;
 * anything else can show one with:
 *   window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Saved' } }))
 */
Alpine.data('toasts', (initial = []) => ({
    items: [],
    nextId: 1,

    init() {
        initial.forEach((toast) => this.add(toast));
        window.addEventListener('toast', (event) => this.add(event.detail));
    },

    add({ type = 'success', message }) {
        if (!message) return;
        const id = this.nextId++;
        this.items.push({ id, type, message, visible: true });
        setTimeout(() => this.remove(id), type === 'error' ? 8000 : 4000);
    },

    remove(id) {
        const toast = this.items.find((item) => item.id === id);
        if (!toast) return;
        toast.visible = false;
        setTimeout(() => (this.items = this.items.filter((item) => item.id !== id)), 200);
    },
}));

/**
 * A confirmation dialog for destructive actions, used instead of the
 * browser's confirm(). Any form with data-confirm="Question?" opens it
 * before submitting (see the submit listener below).
 */
Alpine.data('confirmDialog', () => ({
    open: false,
    title: '',
    message: '',
    confirmLabel: 'Confirm',
    onConfirm: null,

    show({ title, message, confirmLabel, onConfirm }) {
        this.title = title || 'Are you sure?';
        this.message = message || '';
        this.confirmLabel = confirmLabel || 'Confirm';
        this.onConfirm = onConfirm;
        this.open = true;
        this.$nextTick(() => this.$refs.confirmButton?.focus());
    },

    confirm() {
        this.open = false;
        this.onConfirm?.();
    },

    cancel() {
        this.open = false;
    },
}));

/**
 * Light / dark theme. The choice is remembered in localStorage; with no
 * choice the OS setting is used. An inline script in <head> applies it
 * before the page paints, so there is no flash of the wrong theme.
 */
Alpine.data('themeToggle', () => ({
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        try {
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        } catch (error) {
            // Storage can be unavailable (private mode); the toggle still works for this page.
        }
    },
}));

// Forms marked with data-confirm ask first, then submit for real.
document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;

        if (form.dataset.confirmed === 'yes') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        window.dispatchEvent(
            new CustomEvent('confirm', {
                detail: {
                    title: form.dataset.confirmTitle,
                    message: form.dataset.confirm,
                    confirmLabel: form.dataset.confirmLabel,
                    onConfirm: () => {
                        form.dataset.confirmed = 'yes';
                        form.requestSubmit();
                    },
                },
            }),
        );
    },
    true,
);

window.Alpine = Alpine;
Alpine.start();
