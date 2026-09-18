/**
 * User Interactions Module: Ripple, Animated Counters, Progress Bars, Card Tilt
 * File: animations/interactions.js
 */

/**
 * 1. NUMBER COUNTER ANIMATION (Count-up)
 */
function animateCounter(el) {
  const rawTarget = el.dataset.target || el.innerText.replace(/[^\d]/g, '');
  const target = parseInt(rawTarget, 10);
  if (isNaN(target)) return;

  const suffix = el.dataset.suffix || '';
  const prefix = el.dataset.prefix || '';
  const duration = parseInt(el.dataset.duration || '1200', 10);
  const startTime = performance.now();

  const tick = (now) => {
    const elapsed = now - startTime;
    const progress = Math.min(elapsed / duration, 1);
    // Smooth easeOutExpo curve
    const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
    const currentVal = Math.round(eased * target);
    el.textContent = `${prefix}${currentVal.toLocaleString()}${suffix}`;

    if (progress < 1) {
      requestAnimationFrame(tick);
    }
  };

  requestAnimationFrame(tick);
}

export function initCounters() {
  const counters = document.querySelectorAll('.count-up');
  if (!counters.length) return;

  if (!('IntersectionObserver' in window)) {
    counters.forEach(el => animateCounter(el));
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  counters.forEach(el => observer.observe(el));
}

/**
 * 2. ANIMATED PROGRESS BARS
 */
export function initProgressBars() {
  const bars = document.querySelectorAll('.progress-bar-animated');
  if (!bars.length) return;

  if (!('IntersectionObserver' in window)) {
    bars.forEach(el => {
      const width = el.dataset.progress || el.style.getPropertyValue('--progress-width') || '0%';
      el.style.setProperty('--progress-width', width);
      el.classList.add('animate');
    });
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const width = el.dataset.progress || el.style.getPropertyValue('--progress-width') || '0%';
        el.style.setProperty('--progress-width', width);
        requestAnimationFrame(() => el.classList.add('animate'));
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.2 });

  bars.forEach(bar => observer.observe(bar));
}

/**
 * 3. BUTTON MATERIAL RIPPLE
 */
export function initRipple() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.ripple-btn');
    if (!btn) return;

    const rect = btn.getBoundingClientRect();
    const size = Math.max(btn.offsetWidth, btn.offsetHeight);
    const ripple = document.createElement('span');

    ripple.className = 'ripple';
    ripple.style.width = `${size}px`;
    ripple.style.height = `${size}px`;
    ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
    ripple.style.top = `${e.clientY - rect.top - size / 2}px`;

    btn.appendChild(ripple);
    ripple.addEventListener('animationend', () => ripple.remove());
  });
}

/**
 * 4. CARD 3D TILT EFFECT (Desktop only)
 */
export function initCardTilt() {
  // Only apply on non-touch devices
  if (window.matchMedia('(hover: none)').matches) return;

  const cards = document.querySelectorAll('.card-glow, .feature-box');
  cards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const centerX = rect.width / 2;
      const centerY = rect.height / 2;
      const rotateX = ((y - centerY) / centerY) * -4;
      const rotateY = ((x - centerX) / centerX) * 4;

      card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-4px)`;
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
    });
  });
}
