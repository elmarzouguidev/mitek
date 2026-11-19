/**
 * Search autocomplete: debounced calls to the JSON suggestions endpoint (max 8 results).
 *
 * @param {string} url suggestions endpoint
 */
export default (url) => ({
    url,
    query: '',
    results: [],
    open: false,
    highlighted: -1,
    timer: null,
    controller: null,

    onInput() {
        clearTimeout(this.timer);

        if (this.query.trim().length < 2) {
            this.results = [];
            this.open = false;

            return;
        }

        this.timer = setTimeout(() => this.fetchResults(), 250);
    },

    async fetchResults() {
        this.controller?.abort();
        this.controller = new AbortController();

        try {
            const response = await fetch(`${this.url}?q=${encodeURIComponent(this.query.trim())}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });

            if (response.ok) {
                this.results = (await response.json()).data;
                this.highlighted = -1;
                this.open = this.results.length > 0;
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
            }
        }
    },

    move(step) {
        if (!this.open) {
            return;
        }

        this.highlighted = (this.highlighted + step + this.results.length) % this.results.length;
    },

    /** Enter goes to the highlighted suggestion, otherwise the form submits to the full results page. */
    choose(event) {
        if (this.open && this.highlighted >= 0) {
            event.preventDefault();
            window.location.href = this.results[this.highlighted].url;
        }
    },

    close() {
        this.open = false;
        this.highlighted = -1;
    },
});
