/**
 * ELSAR5A THEATRE — PHASE 2A STAGE CHOREOGRAPHY & SCROLL ENGINE
 * Zero external runtime dependencies. 60-120fps GPU compositor-friendly.
 */
(function () {
  'use strict';

  var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var curtainOverlay = document.getElementById('elsar5a-curtain-overlay');
  var skipBtn = document.getElementById('elsar5a-curtain-skip');
  var isCurtainFinished = false;
  var curtainTimers = [];

  function finishCurtainImmediately() {
    if (isCurtainFinished) return;
    isCurtainFinished = true;
    curtainTimers.forEach(function (id) { clearTimeout(id); });
    document.body.classList.remove('curtain-is-closed', 'curtain-is-opening');
    document.body.classList.add('curtain-is-open');
    if (curtainOverlay) {
      curtainOverlay.setAttribute('data-curtain-state', 'open');
    }
    if (skipBtn) {
      skipBtn.classList.add('is-hidden');
    }
  }

  // 1. Curtain Overture Sequence on Homepage
  if (curtainOverlay && curtainOverlay.getAttribute('data-curtain-enabled') === 'true' && !prefersReducedMotion) {
    document.body.classList.add('curtain-is-closed');

    // Beat 1 (80ms): Ignite golden center seam
    curtainTimers.push(setTimeout(function () {
      if (!isCurtainFinished) {
        curtainOverlay.setAttribute('data-curtain-state', 'igniting');
      }
    }, 80));

    // Beat 2 (420ms): Part curtains in 3D & push camera forward onto stage
    curtainTimers.push(setTimeout(function () {
      if (!isCurtainFinished) {
        curtainOverlay.setAttribute('data-curtain-state', 'opening');
        document.body.classList.remove('curtain-is-closed');
        document.body.classList.add('curtain-is-opening');
      }
    }, 420));

    // Beat 3 (1850ms): Settle into open proscenium state
    curtainTimers.push(setTimeout(function () {
      finishCurtainImmediately();
    }, 1850));

    // Allow instant skip via button, Escape/Space key, wheel, or touch swipe
    if (skipBtn) {
      skipBtn.addEventListener('click', function () {
        finishCurtainImmediately();
        var showsTarget = document.getElementById('shows');
        if (showsTarget) {
          showsTarget.scrollIntoView({ behavior: 'smooth' });
        }
      });
    }

    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' || e.key === ' ' || e.key === 'ArrowDown') {
        finishCurtainImmediately();
      }
    }, { passive: true });

    window.addEventListener('wheel', function () {
      if (!isCurtainFinished) finishCurtainImmediately();
    }, { passive: true, once: true });

    window.addEventListener('touchmove', function () {
      if (!isCurtainFinished) finishCurtainImmediately();
    }, { passive: true, once: true });
  } else {
    finishCurtainImmediately();
  }

  // 2. Scroll Storyboard Reveal Engine (IntersectionObserver)
  var revealNodes = document.querySelectorAll('.stage-scene-reveal');
  if ('IntersectionObserver' in window && !prefersReducedMotion) {
    var sceneObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in-scene');
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealNodes.forEach(function (node) {
      sceneObserver.observe(node);
    });
  } else {
    revealNodes.forEach(function (node) {
      node.classList.add('is-in-scene');
    });
  }

  // 3. Desktop-Only 3D Playbill Card Tilt
  var supportsHover = window.matchMedia && window.matchMedia('(hover: hover)').matches;
  if (supportsHover && !prefersReducedMotion) {
    var cards = document.querySelectorAll('.scream-card');
    cards.forEach(function (card) {
      card.addEventListener('pointermove', function (e) {
        var rect = card.getBoundingClientRect();
        var nx = (e.clientX - rect.left) / rect.width - 0.5;
        var ny = (e.clientY - rect.top) / rect.height - 0.5;
        card.style.setProperty('--tilt-x', (-ny * 5).toFixed(2) + 'deg');
        card.style.setProperty('--tilt-y', (nx * 5).toFixed(2) + 'deg');
      }, { passive: true });

      card.addEventListener('pointerleave', function () {
        card.style.setProperty('--tilt-x', '0deg');
        card.style.setProperty('--tilt-y', '0deg');
      }, { passive: true });
    });
  }

  // 4. Honest Curtain Countdown Timer (Only runs if real future timestamp exists)
  var countdownEl = document.querySelector('[data-next-curtain-iso]');
  if (countdownEl) {
    var targetTime = Date.parse(countdownEl.getAttribute('data-next-curtain-iso'));
    if (!isNaN(targetTime) && targetTime > Date.now()) {
      var daysNode = countdownEl.querySelector('[data-cd-days]');
      var hoursNode = countdownEl.querySelector('[data-cd-hours]');
      var minsNode = countdownEl.querySelector('[data-cd-mins]');
      var updateClock = function () {
        var diffSec = Math.max(0, Math.floor((targetTime - Date.now()) / 1000));
        var d = Math.floor(diffSec / 86400);
        var h = Math.floor((diffSec % 86400) / 3600);
        var m = Math.floor((diffSec % 3600) / 60);
        if (daysNode) daysNode.textContent = String(d).padStart(2, '0') + 'd';
        if (hoursNode) hoursNode.textContent = String(h).padStart(2, '0') + 'h';
        if (minsNode) minsNode.textContent = String(m).padStart(2, '0') + 'm';
      };
      updateClock();
      setInterval(updateClock, 30000);
    }
  }
})();
