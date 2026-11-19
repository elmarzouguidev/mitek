/**
 * Long facet lists (brands, printer models): an inline search and a "Voir plus" limit. The server renders
 * every value, so without JavaScript the whole list is visible and the filters still work.
 *
 * @param {number} limit values shown before "Voir plus"
 */
export default (limit = 8) => ({
    q: '',
    all: false,

    norm(text) {
        return text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    },

    show(name, index, selected) {
        const query = this.norm(this.q.trim());

        if (query !== '') {
            return this.norm(name).includes(query);
        }

        return this.all || selected || index < limit;
    },

    get searching() {
        return this.q.trim() !== '';
    },
});
