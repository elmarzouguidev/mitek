/**
 * Copies a text to the clipboard and confirms it for assistive technology and sighted users.
 *
 * @param {string} text
 */
export default (text) => ({
    copied: false,
    timer: null,

    async copy() {
        try {
            await navigator.clipboard.writeText(text);
        } catch (error) {
            const field = document.createElement('textarea');
            field.value = text;
            field.setAttribute('readonly', '');
            field.style.position = 'fixed';
            field.style.opacity = '0';
            document.body.appendChild(field);
            field.select();
            document.execCommand('copy');
            field.remove();
        }

        this.copied = true;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => (this.copied = false), 2000);
    },
});
