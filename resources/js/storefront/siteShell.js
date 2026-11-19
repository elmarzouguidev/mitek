/**
 * Page-level state of the header: the compact sticky header after scrolling and the mobile drawer.
 */
export default () => ({
    compact: false,
    drawerOpen: false,
    family: null,

    init() {
        this.onScroll();
    },

    onScroll() {
        this.compact = window.scrollY > 24;
    },

    openDrawer() {
        this.drawerOpen = true;
        document.documentElement.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.drawerClose?.focus());
    },

    closeDrawer(returnFocus = true) {
        if (!this.drawerOpen) {
            return;
        }

        this.drawerOpen = false;
        document.documentElement.classList.remove('overflow-hidden');

        if (returnFocus) {
            this.$refs.drawerTrigger?.focus();
        }
    },

    toggleFamily(slug) {
        this.family = this.family === slug ? null : slug;
    },
});
