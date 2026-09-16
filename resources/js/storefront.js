import '../sass/storefront.scss';

document.addEventListener('DOMContentLoaded', () => {
    const slides = [...document.querySelectorAll('.hero__slide')];
    const dots = document.querySelector('.hero__dots');
    let current = 0;
    let timer;
    const show = (index) => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('is-active', i === current));
        [...dots.children].forEach((dot, i) => dot.classList.toggle('is-active', i === current));
    };
    if (dots && slides.length) {
        slides.forEach((_, i) => { const dot = document.createElement('button'); dot.setAttribute('aria-label', `Chuyển đến ảnh ${i + 1}`); dot.addEventListener('click', () => { show(i); restart(); }); dots.append(dot); });
        const restart = () => { clearInterval(timer); timer = setInterval(() => show(current + 1), 5500); };
        document.querySelector('.hero__arrow--prev')?.addEventListener('click', () => { show(current - 1); restart(); });
        document.querySelector('.hero__arrow--next')?.addEventListener('click', () => { show(current + 1); restart(); });
        show(0); restart();
    }
    document.querySelector('.menu-toggle')?.addEventListener('click', () => document.querySelector('.nav-links')?.classList.toggle('is-open'));
    document.querySelectorAll('.occasion-tabs button').forEach(button => button.addEventListener('click', () => { document.querySelector('.occasion-tabs .is-active')?.classList.remove('is-active'); button.classList.add('is-active'); }));
    document.querySelector('.newsletter form')?.addEventListener('submit', (event) => { event.preventDefault(); const button = event.currentTarget.querySelector('button'); if (button) button.textContent = 'Đã đăng ký ✓'; });
});
