import './bootstrap';

const updateClock = () => {
    const clock = document.querySelector('[data-live-clock]');
    if (!clock) return;
    const timezone = clock.dataset.timezone;
    const parts = new Intl.DateTimeFormat('en-US', { timeZone: timezone, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }).formatToParts(new Date());
    const value = Object.fromEntries(parts.map(({ type, value }) => [type, value]));
    clock.textContent = `${value.hour}:${value.minute}:${value.second}`;
    document.querySelector('[data-live-meridiem]').textContent = value.dayPeriod;
};
updateClock();
window.setInterval(updateClock, 1000);

const card = document.querySelector('[data-id-card]');
const cardToggle = document.querySelector('[data-card-toggle]');
const flipCard = () => {
    if (!card) return;
    const flipped = card.classList.toggle('flipped');
    card.setAttribute('aria-pressed', String(flipped));
    cardToggle?.setAttribute('aria-pressed', String(flipped));
};
card?.addEventListener('click', flipCard);
card?.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); flipCard(); } });
cardToggle?.addEventListener('click', flipCard);

document.querySelectorAll('[data-submit-once]').forEach(form => form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');
    if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); button.textContent = 'Recording…'; }
}));
