import { trapFocus } from './focusTrap';

/**
 * "Être rappelé" dialog. The form posts to the same route as the full-page form; without JavaScript the
 * header link simply opens that page.
 *
 * @param {{errorMessage: string, throttledMessage: string}} config
 */
export default (config) => ({
    open: false,
    submitting: false,
    sent: false,
    message: '',
    errors: {},
    formError: '',
    previouslyFocused: null,
    releaseTrap: null,

    show(trigger = null) {
        if (this.open) {
            return;
        }

        this.previouslyFocused = trigger instanceof HTMLElement && trigger !== document.body ? trigger : document.activeElement;
        this.sent = false;
        this.errors = {};
        this.formError = '';
        this.open = true;
        document.documentElement.classList.add('overflow-hidden');

        this.$nextTick(() => {
            this.releaseTrap = trapFocus(this.$refs.panel);
            this.$refs.panel.querySelector('input[name="name"]')?.focus();
        });
    },

    hide() {
        if (!this.open) {
            return;
        }

        this.open = false;
        document.documentElement.classList.remove('overflow-hidden');
        this.releaseTrap?.();
        this.releaseTrap = null;
        this.previouslyFocused?.focus?.();
    },

    async submit(form) {
        this.errors = {};
        this.formError = '';
        this.submitting = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: new FormData(form),
                credentials: 'same-origin',
            });

            if (response.status === 422) {
                this.errors = (await response.json()).errors ?? {};
                this.$nextTick(() => form.querySelector('[aria-invalid="true"]')?.focus());

                return;
            }

            if (response.status === 429) {
                this.formError = config.throttledMessage;

                return;
            }

            if (!response.ok || !(response.headers.get('content-type') ?? '').includes('json')) {
                this.formError = config.errorMessage;

                return;
            }

            this.message = (await response.json()).message;
            this.sent = true;
            form.reset();
        } catch (error) {
            this.formError = config.errorMessage;
        } finally {
            this.submitting = false;
        }
    },
});
