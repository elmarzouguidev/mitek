import { trapFocus } from './focusTrap';

/**
 * Page-level state of the header: the compact sticky header after scrolling and the mobile drawer.
 */
export default () => ({
    compact: false,
    drawerOpen: false,
    family: null,
    releaseTrap: null,

    init() {
        this.onScroll();
    },

    onScroll() {
        this.compact = window.scrollY > 24;
    },

    openDrawer() {
        this.drawerOpen = true;
        document.documentElement.classList.add('overflow-hidden');
        this.$nextTick(() => {
            this.releaseTrap = trapFocus(document.getElementById('mobile-drawer'));
            this.$refs.drawerClose?.focus();
        });
    },

    closeDrawer(returnFocus = true) {
        if (!this.drawerOpen) {
            return;
        }

        this.drawerOpen = false;
        document.documentElement.classList.remove('overflow-hidden');
        this.releaseTrap?.();
        this.releaseTrap = null;

        if (returnFocus) {
            this.$refs.drawerTrigger?.focus();
        }
    },

    toggleFamily(slug) {
        this.family = this.family === slug ? null : slug;
    },
});
