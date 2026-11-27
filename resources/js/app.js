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
function setTheme(dark) {
    document.documentElement.classList.toggle('dark', dark);
    try {
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    } catch (error) {
        // Storage can be unavailable (private mode); the change still applies to this page.
    }
    // Keep every toggle on the page in step (e.g. after switching from the command palette).
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark } }));
}

Alpine.data('themeToggle', () => ({
    dark: document.documentElement.classList.contains('dark'),

    init() {
        window.addEventListener('theme-changed', (event) => (this.dark = event.detail.dark));
    },

    toggle() {
        setTheme(!this.dark);
    },
}));

/**
 * The Ctrl+K (Cmd+K on a Mac) command palette. Pages and actions come from
 * the server (App\Support\CommandPalette, already filtered by role) and are
 * filtered here as you type; products and categories are fetched from
 * /search, which only ever returns the current shop's records.
 */
Alpine.data('commandPalette', (commands = [], searchUrl = '') => ({
    open: false,
    query: '',
    products: [],
    categories: [],
    loading: false,
    active: 0,
    timer: null,
    controller: null,
    returnFocusTo: null,

    init() {
        this.$watch('query', () => {
            this.active = 0;
            this.scheduleSearch();
        });
    },

    // Everything currently listed, in display order, so the arrow keys can move through it.
    get results() {
        const words = this.query.toLowerCase().split(/\s+/).filter(Boolean);
        const pages = commands
            .filter((command) => {
                const text = `${command.label} ${command.keywords}`.toLowerCase();
                return words.every((word) => text.includes(word));
            })
            .map((command) => ({ group: 'Pages', label: command.label, hint: '', url: command.url, action: command.action }));

        const products = this.products.map((product) => ({
            group: 'Products',
            label: product.name,
            hint: [product.category, `${product.stock} in stock`].filter(Boolean).join(' · '),
            url: product.url,
        }));

        const categories = this.categories.map((category) => ({
            group: 'Categories',
            label: category.name,
            hint: `${category.products_count} ${category.products_count === 1 ? 'product' : 'products'}`,
            url: category.url,
        }));

        return [...pages, ...products, ...categories];
    },

    show() {
        if (this.open) return;
        this.returnFocusTo = document.activeElement;
        // Start fresh each time (cleared here, not on close, so the list doesn't change while it fades out).
        this.query = '';
        this.open = true;
        document.body.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.input.focus());
    },

    close() {
        if (!this.open) return;
        this.open = false;
        document.body.classList.remove('overflow-hidden');
        this.returnFocusTo?.focus?.();
    },

    toggle() {
        this.open ? this.close() : this.show();
    },

    // Wait until typing pauses for 200 ms, then ask the server.
    scheduleSearch() {
        clearTimeout(this.timer);
        this.controller?.abort();

        if (this.query.trim() === '') {
            this.products = [];
            this.categories = [];
            this.loading = false;
            return;
        }

        this.loading = true;
        this.timer = setTimeout(() => this.search(this.query.trim()), 200);
    },

    async search(term) {
        // Cancel the previous request, so a slow old answer can't replace a newer one.
        this.controller = new AbortController();

        try {
            const response = await fetch(`${searchUrl}?q=${encodeURIComponent(term)}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            if (!response.ok) throw new Error(`Search failed (${response.status})`);

            const data = await response.json();
            this.products = data.products;
            this.categories = data.categories;
        } catch (error) {
            if (error.name === 'AbortError') return;
            this.products = [];
            this.categories = [];
        }

        this.loading = false;
    },

    move(step) {
        const count = this.results.length;
        if (count === 0) return;
        this.active = (this.active + step + count) % count;
        this.$nextTick(() => document.getElementById(`palette-item-${this.active}`)?.scrollIntoView({ block: 'nearest' }));
    },

    choose(item = this.results[this.active]) {
        if (!item) return;

        if (item.action === 'theme') {
            setTheme(!document.documentElement.classList.contains('dark'));
            this.close();
            return;
        }

        this.close();
        window.location.href = item.url;
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
