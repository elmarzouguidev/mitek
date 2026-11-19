/**
 * Quantity selector that asks the server for the price: tier resolution and VAT stay in PHP (PriceCalculator).
 *
 * @param {string} url JSON price endpoint
 * @param {object} initial price quote for quantity 1
 */
export default (url, initial) => ({
    url,
    quantity: 1,
    quote: initial,
    loading: false,
    cartNotice: false,
    timer: null,
    controller: null,

    update() {
        const quantity = Math.max(1, parseInt(this.quantity, 10) || 1);

        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.fetchQuote(quantity), 250);
    },

    async fetchQuote(quantity) {
        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;

        try {
            const response = await fetch(`${this.url}?quantite=${quantity}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });

            if (response.ok) {
                this.quote = await response.json();
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
