/**
 * Checkout summary: asks the server for the shipping cost and the usable payment methods whenever the
 * city or the carrier changes. All amounts and rules (weight grid, 2,000 DH cash-on-delivery limit)
 * stay in PHP; the order is re-validated on submit anyway.
 *
 * @param {{url: string, city: string, carrier: string, payment: string, isCompany: boolean}} config
 */
export default (config) => ({
    url: config.url,
    city: config.city,
    carrier: config.carrier,
    payment: config.payment,
    isCompany: config.isCompany,
    quote: null,
    allowed: null,
    error: null,
    loading: false,
    timer: null,
    controller: null,

    init() {
        if (this.city.trim() !== '') {
            this.estimate();
        }
    },

    onChange() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.estimate(), 300);
    },

    isAllowed(method) {
        return this.allowed === null || this.allowed.includes(method);
    },

    async estimate() {
        if (this.city.trim() === '') {
            this.quote = null;
            this.allowed = null;
            this.error = null;

            return;
        }

        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;

        try {
            const params = new URLSearchParams({ ville: this.city.trim(), transporteur: this.carrier });
            const response = await fetch(`${this.url}?${params}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            const body = await response.json();

            if (response.ok) {
                this.quote = body.quote;
                this.allowed = body.methods;
                this.error = null;

                if (this.payment && !this.allowed.includes(this.payment)) {
                    this.payment = '';
                }
            } else {
                this.quote = null;
                this.allowed = null;
                this.error = body.message ?? 'Erreur de calcul de la livraison.';
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
            }
        } finally {
            this.loading = false;
        }
    },
});
