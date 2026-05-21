import './styles/store/store_landing.css';

document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('.ig-hero__slide');
    const dots = document.querySelectorAll('.ig-hero__dot');
    let idx = 0;
    let timer;

    const show = (i) => {
        idx = (i + slides.length) % slides.length;
        slides.forEach((s, j) => s.classList.toggle('ig-hero__slide--active', j === idx));
        dots.forEach((d, j) => {
            d.classList.toggle('ig-hero__dot--active', j === idx);
            d.setAttribute('aria-selected', j === idx ? 'true' : 'false');
        });
    };

    const next = () => show(idx + 1);

    if (slides.length > 1) {
        timer = window.setInterval(next, 6000);
        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => {
                window.clearInterval(timer);
                show(i);
                timer = window.setInterval(next, 6000);
            });
        });
    }

    document.querySelectorAll('.ig-filter').forEach((btn) => {
        btn.addEventListener('click', () => {
            const group = btn.closest('.ig-filters');
            if (!group) {
                return;
            }
            group.querySelectorAll('.ig-filter').forEach((b) => b.classList.remove('ig-filter--active'));
            btn.classList.add('ig-filter--active');
            const platform = btn.getAttribute('data-platform');
            document.querySelectorAll('.ig-game-card').forEach((card) => {
                const p = card.getAttribute('data-platform');
                const showCard = platform === 'all' || !p || p === platform;
                card.classList.toggle('ig-game-card--hidden', !showCard);
            });
        });
    });
});
