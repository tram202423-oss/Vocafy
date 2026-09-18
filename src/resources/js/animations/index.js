/**
 * Vocafy UI Animation Engine - Main Entrypoint
 * File: animations/index.js
 */

import {
  initPageLoader,
  initScrollProgress,
  initNavbarScroll
} from './pageEffects';

import {
  initStaggerGrids,
  initScrollReveal
} from './scrollReveal';

import {
  initCounters,
  initProgressBars,
  initRipple,
  initCardTilt
} from './interactions';

// Re-export individual functions for granular usage
export {
  initPageLoader,
  initScrollProgress,
  initNavbarScroll,
  initStaggerGrids,
  initScrollReveal,
  initCounters,
  initProgressBars,
  initRipple,
  initCardTilt
};

/**
 * Boots all animation modules in correct logical sequence
 */
export function bootAnimations() {
  // 1. Page level effects
  initPageLoader();
  initScrollProgress();
  initNavbarScroll();

  // 2. Scroll reveal: assigns staggers first, then observes and shows in-view elements
  initScrollReveal();

  // 3. User interaction effects
  initCounters();
  initProgressBars();
  initRipple();
  initCardTilt();
}

export default bootAnimations;
