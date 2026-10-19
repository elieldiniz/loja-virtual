// Entrada suave das seções ao rolar (como no mockup). Sem JS ou com movimento reduzido, tudo aparece normalmente.
document.addEventListener('DOMContentLoaded', () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.remove('pre');
            entry.target.classList.add('in');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('[data-reveal]').forEach((el) => {
        const rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight) return; // já visível: não esconde
        el.classList.add('pre');
        observer.observe(el);
    });
});
