/**
 * Dismissible announcement. The key contains a hash of the text and link, so a changed announcement is
 * shown again. A tiny script in <head> hides an already dismissed one before first paint.
 *
 * @param {string} hash
 */
export default (hash) => ({
    dismiss() {
        try {
            localStorage.setItem(`mitek_announce_${hash}`, '1');
        } catch (error) {
            // Storage can be unavailable (private mode): the bar then simply hides for this page view.
        }

        document.documentElement.classList.add('announce-dismissed');
        this.$root.remove();
    },
});
