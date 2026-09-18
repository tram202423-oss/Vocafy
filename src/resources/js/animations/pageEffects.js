/**
 * Global Page Effects: Loader, Top Scroll Progress, Navbar elevation
 * File: animations/pageEffects.js
 */

/**
 * 1. PAGE LOADER
 * Smoothly hides the loader when page has loaded, with a fallback timeout
 */
export function initPageLoader() {
  const loader = document.getElementById('page-loader');
  if (!loader) return;

  const hide = () => {
    loader.classList.add('loaded');
  };

  if (document.readyState === 'complete') {
    setTimeout(hide, 150);
  } else {
    window.addEventListener('load', () => setTimeout(hide, 150));
    // Fallback safety timeout so loader never gets stuck
    setTimeout(hide, 1200);
  }
}

/**
 * 2. TOP SCROLL PROGRESS BAR
 * Calculates window scroll percentage and updates the bar width
 */
export function initScrollProgress() {
  const bar = document.getElementById('scroll-progress');
  if (!bar) return;

  let ticking = false;

  const update = () => {
    const doc = document.documentElement;
    const top = doc.scrollTop || document.body.scrollTop;
    const max = doc.scrollHeight - doc.clientHeight;
    bar.style.width = max > 0 ? `${(top / max) * 100}%` : '0%';
    ticking = false;
  };

  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(update);
      ticking = true;
    }
  }, { passive: true });

  update();
}

/**
 * 3. NAVBAR SCROLL ELEVATION
 * Adds shadow and blur when user scrolls down
 */
export function initNavbarScroll() {
  const nav = document.querySelector('nav');
  if (!nav) return;

  const toggle = () => {
    nav.classList.toggle('scrolled', window.scrollY > 8);
  };

  window.addEventListener('scroll', toggle, { passive: true });
  toggle();
}
