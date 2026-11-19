/**
 * City-based delivery estimate. The server computes the dates and the fee (same grid as the checkout);
 * this only asks for it, remembers the city, and re-asks when the product quantity changes. Without
 * JavaScript the form is a plain GET that renders the estimate on the page.
 *
 * @param {{url: string, product: string|null, cart: boolean, quantitySelector: string|null, city: string, hasResult: boolean, errorMessage: string}} config
 */
export default (config) => ({
    city: config.city,
    result: null,
    error: '',
    loading: false,
    live: false,
    controller: null,
    timer: null,

    init() {
        if (!this.city) {
            try {
                this.city = localStorage.getItem('mitek_city') ?? '';
            } catch (error) {
                // Storage can be unavailable: the visitor types the city again.
            }
        }

        if (this.city && !config.hasResult) {
            this.fetchEstimate();
        }

        if (config.quantitySelector) {
            document.querySelector(config.quantitySelector)?.addEventListener('input', () => {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.city.trim() && this.fetchEstimate(), 350);
            });
        }
    },

    submit() {
        if (this.city.trim().length < 2) {
            return;
        }

        this.fetchEstimate();
    },

    async fetchEstimate() {
        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;
        this.error = '';

        const params = new URLSearchParams({ ville: this.city.trim() });

        if (config.cart) {
            params.set('panier', '1');
        } else if (config.product) {
            params.set('produit', config.product);
            params.set('quantite', String(Math.max(1, parseInt(document.querySelector(config.quantitySelector ?? '')?.value, 10) || 1)));
        }

        try {
            const response = await fetch(`${config.url}?${params}`, { headers: { Accept: 'application/json' }, signal: this.controller.signal });

            if (response.ok) {
                this.result = await response.json();
                this.live = true;

                try {
                    localStorage.setItem('mitek_city', this.city.trim());
                } catch (error) {
                    // Not remembered, not a problem.
                }
            } else {
                const body = await response.json().catch(() => ({}));
                this.result = null;
                this.live = true;
                this.error = body.errors?.ville?.[0] ?? body.message ?? config.errorMessage;
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.live = true;
                this.result = null;
                this.error = config.errorMessage;
            }
        } finally {
            this.loading = false;
        }
    },
});
