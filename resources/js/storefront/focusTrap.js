const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Keeps Tab and Shift+Tab inside a dialog while it is open.
 *
 * @param {HTMLElement} container
 * @returns {() => void} call it to release the trap
 */
export function trapFocus(container) {
    const onKeydown = (event) => {
        if (event.key !== 'Tab') {
            return;
        }

        const items = [...container.querySelectorAll(FOCUSABLE)].filter((element) => element.getClientRects().length > 0);

        if (items.length === 0) {
            event.preventDefault();

            return;
        }

        const first = items[0];
        const last = items[items.length - 1];
        const outside = !container.contains(document.activeElement);

        if (event.shiftKey && (document.activeElement === first || outside)) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || outside)) {
            event.preventDefault();
            first.focus();
        }
    };

    document.addEventListener('keydown', onKeydown);

    return () => document.removeEventListener('keydown', onKeydown);
}
