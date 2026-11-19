/**
 * Live "plus que 2 j 4 h" under a promotional price. The server prints the end date; this only appears when
 * the offer ends within a week, and updates once a minute.
 *
 * @param {{endsAt: string, labels: {countdown: string, ended: string, days: string, hours: string, minutes: string}}} config
 */
export default (config) => ({
    text: '',
    timer: null,

    init() {
        this.tick();
        this.timer = setInterval(() => this.tick(), 30000);
    },

    destroy() {
        clearInterval(this.timer);
    },

    tick() {
        const remaining = new Date(config.endsAt).getTime() - Date.now();

        if (remaining <= 0) {
            this.text = config.labels.ended;
            clearInterval(this.timer);

            return;
        }

        const minutes = Math.floor(remaining / 60000);
        const days = Math.floor(minutes / 1440);

        if (days >= 7) {
            this.text = '';

            return;
        }

        const hours = Math.floor((minutes % 1440) / 60);
        const parts = [];

        if (days > 0) {
            parts.push(`${days} ${config.labels.days}`, `${hours} ${config.labels.hours}`);
        } else if (hours > 0) {
            parts.push(`${hours} ${config.labels.hours}`, `${minutes % 60} ${config.labels.minutes}`);
        } else {
            parts.push(`${minutes % 60} ${config.labels.minutes}`);
        }

        this.text = config.labels.countdown.replace(':time', parts.join(' '));
    },
});
