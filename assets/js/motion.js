// GSAP motion. Runs only if GSAP loaded and the visitor has not asked for reduced motion.
(() => {
  const root = document.documentElement;
  if (!window.gsap || !window.ScrollTrigger) { root.classList.remove('motion'); return; }
  gsap.registerPlugin(ScrollTrigger);

  gsap.matchMedia().add('(prefers-reduced-motion: no-preference)', () => {
    // Hero: one orchestrated entrance
    const title = document.querySelector('[data-hero-title]');
    if (title) {
      title.innerHTML = title.textContent.trim().split(/\s+/).map(w =>
        `<span class="inline-block overflow-hidden pb-[.14em] -mb-[.14em] align-bottom"><span data-hero-word class="inline-block">${w}</span></span>`).join(' ');
      title.style.visibility = 'visible';
      gsap.timeline({ defaults: { ease: 'power3.out' } })
        .fromTo('[data-hero-img]', { scale: 1.15 }, { scale: 1.02, duration: 2.4, ease: 'power2.out' }, 0)
        .fromTo('[data-hero-word]', { yPercent: 115 }, { yPercent: 0, duration: 1, stagger: .06 }, .15)
        .fromTo('[data-hero-sub]', { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: .8 }, .8)
        .fromTo('[data-hero-form]', { y: 40, opacity: 0 }, { y: 0, opacity: 1, duration: .9 }, 1);

      gsap.to('[data-hero-img]', { yPercent: 10, ease: 'none',
        scrollTrigger: { trigger: '[data-hero]', start: 'top top', end: 'bottom top', scrub: true } });
    }

    // Scroll reveals, batched so siblings stagger
    ScrollTrigger.batch('[data-reveal]', {
      start: 'top 88%', once: true,
      onEnter: els => gsap.to(els, { opacity: 1, y: 0, duration: .8, stagger: .1, ease: 'power3.out', overwrite: true })
    });

    // Images open up as they scroll in
    gsap.utils.toArray('[data-clip]').forEach(el => {
      gsap.fromTo(el, { clipPath: 'inset(10% 8% 10% 8% round 1.5rem)', scale: 1.08 },
        { clipPath: 'inset(0% 0% 0% 0% round 1.5rem)', scale: 1, ease: 'none',
          scrollTrigger: { trigger: el, start: 'top 92%', end: 'top 35%', scrub: true } });
    });
  });
})();
