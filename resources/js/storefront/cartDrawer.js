import Alpine from 'alpinejs';
import { trapFocus } from './focusTrap';

const clone = (value) => JSON.parse(JSON.stringify(value));

/**
 * Mini-cart drawer. The server owns every price: the browser only sends quantities and displays the
 * priced cart it gets back. Quantity changes and removals are applied optimistically and rolled back
 * to the last server state when the request fails.
 *
 * @param {{summaryUrl: string, count: number, errorMessage: string}} config
 */
export default (config) => ({
    open: false,
    loading: false,
    cart: { count: config.count, lines: [], urls: {}, cod: null, adjustments: [] },
    confirmed: null,
    notice: '',
    error: '',
    previouslyFocused: null,
    releaseTrap: null,
    timers: {},
    noticeTimer: null,

    get lines() {
        return this.cart.lines ?? [];
    },

    get isEmpty() {
        return !this.loading && this.lines.length === 0;
    },

    /** @param {EventTarget|null} trigger the element that asked for the drawer, to give focus back to it */
    async show(trigger = null) {
        if (this.open) {
            return;
        }

        this.previouslyFocused = trigger instanceof HTMLElement && trigger !== document.body ? trigger : document.activeElement;
        this.open = true;
        document.documentElement.classList.add('overflow-hidden');

        this.$nextTick(() => {
            this.releaseTrap = trapFocus(this.$refs.panel);
            this.$refs.close?.focus();
        });

        await this.refresh();
    },

    hide() {
        if (!this.open) {
            return;
        }

        this.open = false;
        this.error = '';
        this.notice = '';
        document.documentElement.classList.remove('overflow-hidden');
        this.releaseTrap?.();
        this.releaseTrap = null;
        this.previouslyFocused?.focus?.();
    },

    async refresh() {
        this.loading = this.lines.length === 0;

        try {
            const data = await this.request('GET', config.summaryUrl);
            this.apply(data);
        } catch (error) {
            this.error = error.message;
        } finally {
            this.loading = false;
        }
    },

    apply(data) {
        this.cart = data;
        this.confirmed = clone(data);
        Alpine.store('cart').count = data.count;
    },

    /** The "Ajouter au panier" form posts through the drawer; without a usable response it submits normally. */
    async add(form, trigger = null) {
        try {
            const data = await this.request('POST', form.action, new FormData(form));
            this.apply(data);
            this.flash(data.added);
        } catch (error) {
            if (error.validation) {
                this.error = error.message;
            } else {
                form.submit();

                return;
            }
        }

        await this.show(trigger);
    },

    setQuantity(line, requested) {
        const quantity = Math.min(Math.max(parseInt(requested, 10) || 0, 0), line.max);

        if (quantity === 0) {
            return this.remove(line);
        }

        if (quantity === line.quantity) {
            return;
        }

        this.error = '';
        this.cart.count += quantity - line.quantity;
        Alpine.store('cart').count = this.cart.count;
        line.quantity = quantity;
        line.pending = true;

        clearTimeout(this.timers[line.slug]);
        this.timers[line.slug] = setTimeout(() => this.commit(line, quantity), 300);
    },

    async commit(line, quantity) {
        try {
            const data = await this.request('PATCH', line.update_url, { quantite: quantity });

            // A newer change on this line is already queued: wait for its answer.
            if (this.lines.find((item) => item.slug === line.slug)?.quantity === quantity) {
                this.apply(data);
            }
        } catch (error) {
            this.rollback(error.message);
        }
    },

    async remove(line) {
        clearTimeout(this.timers[line.slug]);
        this.error = '';
        this.cart.count -= line.quantity;
        Alpine.store('cart').count = this.cart.count;
        this.cart.lines = this.lines.filter((item) => item.slug !== line.slug);

        try {
            this.apply(await this.request('DELETE', line.remove_url));
        } catch (error) {
            this.rollback(error.message);
        }
    },

    rollback(message) {
        if (this.confirmed) {
            this.cart = clone(this.confirmed);
            Alpine.store('cart').count = this.cart.count;
        }

        this.error = message || config.errorMessage;
    },

    flash(message) {
        this.notice = message ?? '';
        clearTimeout(this.noticeTimer);
        this.noticeTimer = setTimeout(() => (this.notice = ''), 4000);
    },

    async request(method, url, body = null) {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        };

        if (body && !(body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(body);
        }

        let response;

        try {
            response = await fetch(url, { method, headers, body, credentials: 'same-origin' });
        } catch (error) {
            throw Object.assign(new Error(config.errorMessage), { network: true });
        }

        if (response.ok) {
            return response.json();
        }

        const payload = await response.json().catch(() => ({}));
        const message = payload.errors ? Object.values(payload.errors).flat()[0] : payload.message;

        throw Object.assign(new Error(message || config.errorMessage), { validation: response.status === 422 });
    },
});
