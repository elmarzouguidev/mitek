/**
 * "Tous nos produits" disclosure: opens on click (touch and keyboard) and on hover with a short intent
 * delay for mouse pointers. All the links are in the server-rendered HTML; this only toggles visibility.
 */
export default () => ({
    open: false,
    hoverOpened: false,
    timer: null,

    toggle() {
        clearTimeout(this.timer);

        // A click right after a hover-open keeps the menu open instead of flickering it shut.
        if (this.open && this.hoverOpened) {
            this.hoverOpened = false;

            return;
        }

        this.open = !this.open;
        this.hoverOpened = false;
    },

    hoverIn(event) {
        if (event.pointerType !== 'mouse') {
            return;
        }

        clearTimeout(this.timer);

        if (!this.open) {
            this.timer = setTimeout(() => {
                this.open = true;
                this.hoverOpened = true;
            }, 150);
        }
    },

    hoverOut(event) {
        if (event.pointerType !== 'mouse') {
            return;
        }

        clearTimeout(this.timer);

        if (this.open && this.hoverOpened) {
            this.timer = setTimeout(() => this.close(), 150);
        } else if (!this.open) {
            // The pointer left before the intent delay elapsed.
            this.timer = null;
        }
    },

    close(returnFocus = false) {
        clearTimeout(this.timer);
        this.open = false;
        this.hoverOpened = false;

        if (returnFocus) {
            this.$refs.trigger?.focus();
        }
    },

    /** Keyboard users tabbing out of the menu close it. */
    focusOut(event) {
        if (this.open && !this.$root.contains(event.relatedTarget)) {
            this.close();
        }
    },
});
