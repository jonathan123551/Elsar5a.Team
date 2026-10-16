/**
 * Elsar5a homepage overture and forward theatre camera.
 * No external dependencies. Scroll reads are passive and DOM updates are
 * coalesced into one animation frame; reduced-motion skips both sequences.
 */
(function () {
  'use strict';

  var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
  var reducedMotion = !!(motionQuery && motionQuery.matches);
  var curtain = document.getElementById('elsar5a-curtain-overlay');
  var skipButton = document.getElementById('elsar5a-curtain-skip');
  var journey = document.querySelector('.camera-journey');
  var playbill = document.querySelector('.journey-playbill');
  var finished = false;
  var timers = [];
  var framePending = false;
  var lastProgress = -1;

  function clearTimers() {
    timers.forEach(function (timer) { window.clearTimeout(timer); });
    timers = [];
  }

  function finish(skip) {
    if (finished) return;
    finished = true;
    clearTimers();
    document.body.classList.remove('curtain-is-closed', 'curtain-is-opening');
    document.body.classList.add('curtain-is-open');

    if (curtain) {
      if (skip) curtain.classList.add('is-skipped');
      curtain.setAttribute('data-curtain-state', 'open');
    }
    if (skipButton) skipButton.classList.add('is-hidden');
  }

  function startOverture() {
    if (!curtain || reducedMotion) {
      finish(false);
      return;
    }

    document.body.classList.add('curtain-is-closed');
    timers.push(window.setTimeout(function () {
      if (!finished) curtain.setAttribute('data-curtain-state', 'igniting');
    }, 140));
    timers.push(window.setTimeout(function () {
      if (finished) return;
      curtain.setAttribute('data-curtain-state', 'opening');
      document.body.classList.remove('curtain-is-closed');
      document.body.classList.add('curtain-is-opening');
    }, 560));
    timers.push(window.setTimeout(function () {
      if (finished) return;
      curtain.setAttribute('data-curtain-state', 'open');
      document.body.classList.remove('curtain-is-opening');
      document.body.classList.add('curtain-is-open');
    }, 2660));
    // Hold on the revealed identity before the scroll hint appears.
    timers.push(window.setTimeout(function () { finish(false); }, 4650));

    if (skipButton) skipButton.addEventListener('click', function () { finish(true); });

    // The first intentional gesture continues the intro and keeps its native
    // scroll/touch behavior. Nothing is trapped or prevented.
    window.addEventListener('wheel', function () { finish(true); }, { passive: true, once: true });
    window.addEventListener('touchmove', function () { finish(true); }, { passive: true, once: true });
    window.addEventListener('keydown', function (event) {
      if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey) return;
      var target = event.target;
      if (target && target.closest && target.closest('a,button,input,textarea,select,[contenteditable="true"]')) return;
      if (event.key === 'Escape' || event.key === 'ArrowDown' || event.key === ' ') finish(true);
    });
  }

  function clamp(value, min, max) { return Math.min(max, Math.max(min, value)); }

  function updateJourney() {
    framePending = false;
    if (!journey || reducedMotion) return;

    var rect = journey.getBoundingClientRect();
    var travel = Math.max(1, journey.offsetHeight - window.innerHeight);
    var progress = clamp(-rect.top / travel, 0, 1);
    if (Math.abs(progress - lastProgress) < 0.002) return;
    lastProgress = progress;
    journey.style.setProperty('--journey-progress', progress.toFixed(4));
    var viewportHeight = window.innerHeight;
    var world = journey.querySelector('.journey-world');
    var identity = journey.querySelector('.journey-identity');
    if (world) {
      world.style.setProperty('--journey-scale', (0.88 + progress * 0.68).toFixed(4));
      world.style.setProperty('--journey-y', (-Math.round(progress * viewportHeight * 0.03)) + 'px');
    }
    if (identity) {
      identity.style.setProperty('--identity-y', (-Math.round(progress * viewportHeight * 0.18)) + 'px');
      identity.style.setProperty('--identity-opacity', clamp(1 - progress * 3.2, 0, 1).toFixed(4));
    }

    if (playbill) {
      var posterProgress = clamp((progress - 0.43) / 0.34, 0, 1);
      playbill.style.setProperty('--poster-progress', posterProgress.toFixed(4));
      playbill.style.setProperty('--poster-y', Math.round((1 - posterProgress) * viewportHeight * 0.17) + 'px');
      playbill.style.setProperty('--poster-scale', (0.82 + posterProgress * 0.18).toFixed(4));
      playbill.style.visibility = posterProgress > 0.04 ? 'visible' : 'hidden';
      if (posterProgress > 0.04) {
        playbill.removeAttribute('inert');
      } else {
        playbill.setAttribute('inert', '');
      }
    }
  }

  function requestJourneyUpdate() {
    if (framePending || reducedMotion || !journey) return;
    framePending = true;
    window.requestAnimationFrame(updateJourney);
  }

  if (motionQuery) {
    var motionPreferenceChanged = function (event) {
      if (!event.matches) return;
      reducedMotion = true;
      finish(false);
      if (playbill) {
        playbill.removeAttribute('inert');
        playbill.style.visibility = 'visible';
      }
    };
    if (motionQuery.addEventListener) {
      motionQuery.addEventListener('change', motionPreferenceChanged);
    } else if (motionQuery.addListener) {
      motionQuery.addListener(motionPreferenceChanged);
    }
  }

  startOverture();
  if (journey && !reducedMotion) {
    if (playbill) playbill.setAttribute('inert', '');
    window.addEventListener('scroll', requestJourneyUpdate, { passive: true });
    window.addEventListener('resize', requestJourneyUpdate, { passive: true });
    requestJourneyUpdate();
  }
})();
