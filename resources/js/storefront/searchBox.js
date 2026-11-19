/**
 * Header search: an ARIA combobox with grouped suggestions (products, categories, brands, printers,
 * services). Without JavaScript the form still submits to the search page.
 *
 * @param {string} url suggestions endpoint
 * @param {{search: string, all: string, none: string, count: string}} labels ":term" and ":count" are replaced
 */
export default (url, labels) => ({
    url,
    labels,
    query: '',
    groups: [],
    flat: [],
    open: false,
    active: -1,
    status: '',
    timer: null,
    controller: null,

    get allUrl() {
        return `${this.labels.search}?q=${encodeURIComponent(this.query.trim())}`;
    },

    get allLabel() {
        return this.labels.all.replace(':term', this.query.trim());
    },

    /** The "see all results" row is the last option. */
    get lastIndex() {
        return this.flat.length;
    },

    onInput() {
        clearTimeout(this.timer);
        this.active = -1;

        if (this.query.trim().length < 2) {
            this.close();
            this.groups = [];
            this.flat = [];

            return;
        }

        this.timer = setTimeout(() => this.fetchSuggestions(), 200);
    },

    async fetchSuggestions() {
        this.controller?.abort();
        this.controller = new AbortController();

        try {
            const response = await fetch(`${this.url}?q=${encodeURIComponent(this.query.trim())}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });

            if (!response.ok) {
                return;
            }

            const body = await response.json();
            let index = 0;

            this.groups = body.groups.map((group) => ({
                ...group,
                items: group.items.map((item) => ({ ...item, index: index++ })),
            }));
            this.flat = this.groups.flatMap((group) => group.items);
            this.open = true;
            this.status = this.flat.length ? this.labels.count.replace(':count', this.flat.length) : this.labels.none;
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
            }
        }
    },

    move(step) {
        if (!this.open) {
            if (this.flat.length) {
                this.open = true;
            }

            return;
        }

        const total = this.flat.length + 1;
        this.active = this.active === -1 && step < 0 ? total - 1 : (this.active + step + total) % total;
        this.$nextTick(() => document.getElementById(`sb-opt-${this.active}`)?.scrollIntoView({ block: 'nearest' }));
    },

    /** Enter on an active option follows it; otherwise the form submits to the results page. */
    choose(event) {
        if (!this.open || this.active < 0) {
            return;
        }

        event.preventDefault();
        window.location.href = this.active === this.lastIndex ? this.allUrl : this.flat[this.active].url;
    },

    close() {
        this.open = false;
        this.active = -1;
    },

    onEscape() {
        if (this.open) {
            this.close();
        } else {
            this.query = '';
        }
    },
});
