/**
 * Product image gallery. The first image is rendered by the server, so the page works without JS.
 *
 * @param {Array<{thumb: string, large: string, alt: string}>} images
 */
export default (images) => ({
    images,
    index: 0,

    get current() {
        return this.images[this.index] ?? null;
    },

    select(index) {
        this.index = index;
    },

    next() {
        this.index = (this.index + 1) % this.images.length;
    },

    previous() {
        this.index = (this.index - 1 + this.images.length) % this.images.length;
    },
});
