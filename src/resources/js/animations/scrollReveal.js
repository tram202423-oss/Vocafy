/**
 * Scroll Reveal & Stagger Animation Module
 * File: animations/scrollReveal.js
 */

/**
 * Automatically assigns .reveal and .stagger-N to children of [data-stagger]
 */
export function initStaggerGrids() {
  const staggerGrids = document.querySelectorAll('[data-stagger]');
  staggerGrids.forEach(grid => {
    Array.from(grid.children).forEach((child, index) => {
      // Don't override if already marked
      if (!child.classList.contains('reveal')) {
        child.classList.add('reveal');
      }
      child.classList.add(`stagger-${Math.min(index + 1, 8)}`);
    });
  });
}

/**
 * Initializes IntersectionObserver to reveal elements as they scroll into view.
 * Guarantees elements already visible on load are immediately shown.
 */
export function initScrollReveal() {
  // CRITICAL: Ensure stagger grids have their reveal classes assigned BEFORE querying!
  initStaggerGrids();

  const selectors = '.reveal, .reveal-left, .reveal-right, .reveal-scale';
  const elements = document.querySelectorAll(selectors);
  if (!elements.length) return;

  // Fallback if IntersectionObserver is not supported
  if (!('IntersectionObserver' in window)) {
    elements.forEach(el => el.classList.add('visible'));
    return;
  }

  const windowHeight = window.innerHeight || document.documentElement.clientHeight;

  // Check immediately if elements are already in or near the viewport
  elements.forEach(el => {
    const rect = el.getBoundingClientRect();
    if (rect.top < windowHeight + 60 && rect.bottom > -40) {
      el.classList.add('visible');
    }
  });

  // Observer for remaining elements that need to scroll into view
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.05,
    rootMargin: '40px 0px -20px 0px'
  });

  elements.forEach(el => {
    if (!el.classList.contains('visible')) {
      observer.observe(el);
    }
  });

  // SAFETY NET: Ensure no element remains permanently hidden due to dynamic loading or layout shifts
  setTimeout(() => {
    document.querySelectorAll(selectors).forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.top < windowHeight + 100 && rect.bottom > -50) {
        el.classList.add('visible');
      }
    });
  }, 400);
}
