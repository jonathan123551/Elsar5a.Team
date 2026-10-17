/*
 * Elsar5a photographic curtain overture and forward camera journey.
 * Scroll handling is passive and paints at most once per animation frame.
 */
(function () {
  'use strict';

  var body = document.body;
  if (!body || !body.classList.contains('homepage')) return;

  var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
  var reducedMotion = !!(motionQuery && motionQuery.matches);
  var curtain = document.getElementById('cinema-curtain');
  var skip = document.getElementById('cinema-skip');
  var track = document.getElementById('camera-journey');
  var world = document.getElementById('camera-world');
  var auditorium = document.getElementById('camera-auditorium');
  var aisle = document.getElementById('camera-aisle');
  var spotlight = document.getElementById('camera-spotlight');
  var proscenium = document.getElementById('camera-proscenium');
  var spaceLayer = document.getElementById('camera-space-layer');
  var identity = document.getElementById('camera-identity');
  var production = document.getElementById('stage-production');
  var intermission = document.getElementById('stage-intermission');
  var timers = [];
  var done = false;
  var pendingFrame = false;
  var lastProgress = -1;

  function later(delay, callback) {
    timers.push(window.setTimeout(callback, delay));
  }

  function clearTimers() {
    timers.forEach(function (timer) { window.clearTimeout(timer); });
    timers = [];
  }

  function releaseReveal() {
    var panel = production || intermission;
    if (panel) panel.removeAttribute('inert');
  }

  function finish(skipAnimation) {
    if (done) return;
    done = true;
    clearTimers();
    body.dataset.introState = 'complete';
    body.classList.remove('cinema-intro-playing');
    if (curtain) {
      if (skipAnimation) curtain.classList.add('is-skipped');
      curtain.dataset.state = 'open';
    }
    if (skip) skip.classList.add('is-hidden');
    requestCameraUpdate();
  }

  function startOverture() {
    if (!curtain || reducedMotion) {
      finish(false);
      return;
    }

    body.classList.add('cinema-intro-playing');
    body.dataset.introState = 'theatre';
    later(220, function () { if (!done) curtain.dataset.state = 'ignite'; });
    later(680, function () { if (!done) curtain.dataset.state = 'draw'; });
    // The stage receives its moment before any team identity appears.
    later(2480, function () { if (!done) curtain.dataset.state = 'open'; });
    later(2740, function () { if (!done) body.dataset.introState = 'logo'; });
    later(3380, function () { if (!done) body.dataset.introState = 'name'; });
    later(4020, function () { if (!done) body.dataset.introState = 'manifesto'; });
    later(4820, function () { if (!done) body.dataset.introState = 'continue'; });
    later(5600, function () { finish(false); });
  }

  function clamp(value, min, max) { return Math.min(max, Math.max(min, value)); }

  function paintCamera() {
    pendingFrame = false;
    if (!track || reducedMotion) return;
    if (!done) {
      finish(true);
    }

    var bounds = track.getBoundingClientRect();
    var travel = Math.max(1, track.offsetHeight - window.innerHeight);
    var progress = clamp(-bounds.top / travel, 0, 1);
    if (Math.abs(progress - lastProgress) < 0.001) return;
    lastProgress = progress;

    var viewport = window.innerHeight;

    // 0. Curtains dissolve immediately from the start of scrolling (0.00 -> 0.12):
    var curtainScrollOpacity = clamp(1 - (progress / 0.12), 0, 1);
    var curtainScrollShift = (progress * 60).toFixed(2);
    if (curtain) {
      curtain.style.setProperty('--curtain-scroll-opacity', curtainScrollOpacity.toFixed(4));
      curtain.style.setProperty('--curtain-scroll-shift', curtainScrollShift + 'vw');
      if (curtainScrollOpacity <= 0.001) {
        curtain.style.display = 'none';
      } else {
        curtain.style.display = '';
      }
      curtain.classList.toggle('is-passed', progress > 0.12);
    }

    // 0b. Opening Copy fades out and floats upward as user scrolls (0.00 -> 0.10):
    var openingCopy = document.getElementById('opening-copy');
    if (openingCopy) {
      var openingOpacity = clamp(1 - (progress / 0.10), 0, 1);
      var openingScale = (1 + progress * 0.8).toFixed(4);
      var openingY = (-progress * 220).toFixed(1) + 'px';
      openingCopy.style.setProperty('--opening-opacity', openingOpacity.toFixed(4));
      openingCopy.style.setProperty('--opening-scale', openingScale);
      openingCopy.style.setProperty('--opening-y', openingY);
      if (openingOpacity <= 0.001) {
        openingCopy.style.display = 'none';
      } else {
        openingCopy.style.display = 'flex';
      }
    }

    // 1. Camera forward journey through the theatre into website space:
    var theatreDissolve = clamp(1 - (progress - 0.02) / 0.24, 0, 1);
    var spaceGradient = clamp((progress - 0.02) / 0.22, 0, 1);

    if (world) {
      world.style.setProperty('--world-scale', (1.10 + progress * 0.65).toFixed(4));
      world.style.setProperty('--world-z', (-120 + Math.round(progress * 260)) + 'px');
      world.style.setProperty('--world-y', (-Math.round(progress * viewport * 0.04)) + 'px');
      world.style.setProperty('--world-opacity', theatreDissolve.toFixed(4));
    }
    if (auditorium) {
      auditorium.style.setProperty('--auditorium-scale', (1.02 + progress * 0.40).toFixed(4));
      auditorium.style.setProperty('--auditorium-y', Math.round(progress * viewport * 0.08) + 'px');
      auditorium.style.setProperty('--auditorium-opacity', theatreDissolve.toFixed(4));
    }
    if (aisle) {
      aisle.style.setProperty('--aisle-scale', (1.00 + progress * 0.65).toFixed(4));
      aisle.style.setProperty('--aisle-y', Math.round(progress * viewport * 0.18) + 'px');
      aisle.style.setProperty('--aisle-opacity', theatreDissolve.toFixed(4));
    }
    if (spotlight) {
      spotlight.style.setProperty('--spotlight-scale', (1 + progress * 0.85).toFixed(4));
      spotlight.style.setProperty('--spotlight-opacity', (0.16 + (1 - theatreDissolve) * 0.45).toFixed(4));
    }
    if (proscenium) {
      proscenium.style.setProperty('--proscenium-scale', (1 + progress * 0.48).toFixed(4));
      proscenium.style.setProperty('--proscenium-opacity', clamp(1 - (progress - 0.04) / 0.20, 0, 1).toFixed(4));
    }

    if (spaceLayer) {
      spaceLayer.style.setProperty('--space-opacity', spaceGradient.toFixed(4));
    }

    // 2. Floating 3D Production Poster Approach (0.04 -> 0.36):
    if (production) {
      var posterProgress = clamp((progress - 0.04) / 0.32, 0, 1);
      var informationProgress = clamp((progress - 0.24) / 0.20, 0, 1);
      var reveal = clamp((progress - 0.04) / 0.14, 0, 1);

      // Natural ease-out curve for theatrical perspective
      var posterEase = Math.sin(posterProgress * Math.PI / 2);

      var posterScale = (0.55 + posterEase * 0.45).toFixed(4);
      var posterZ = Math.round((1 - posterEase) * -340);
      var posterY = Math.round((1 - posterEase) * 45);
      var posterTilt = ((1 - posterEase) * 8.0).toFixed(2);
      var posterOpacity = reveal.toFixed(4);

      production.style.setProperty('--production-opacity', reveal.toFixed(4));
      production.style.setProperty('--production-y', '0px');
      production.style.setProperty('--production-scale', '1');

      production.style.setProperty('--poster-opacity', posterOpacity);
      production.style.setProperty('--poster-scale', posterScale);
      production.style.setProperty('--poster-z', posterZ + 'px');
      production.style.setProperty('--poster-y', posterY + 'px');
      production.style.setProperty('--poster-tilt', posterTilt + 'deg');

      production.style.setProperty('--information-opacity', informationProgress.toFixed(4));
      production.style.setProperty('--information-y', Math.round((1 - informationProgress) * 20) + 'px');

      if (informationProgress > 0.05) {
        production.removeAttribute('inert');
      } else {
        production.setAttribute('inert', '');
      }
    }

    if (intermission) {
      var interReveal = clamp((progress - 0.10) / 0.25, 0, 1);
      intermission.style.setProperty('--intermission-opacity', interReveal.toFixed(4));
      intermission.style.setProperty('--intermission-y', Math.round((1 - interReveal) * viewport * 0.03) + 'px');
      if (interReveal > 0.05) intermission.removeAttribute('inert');
      else intermission.setAttribute('inert', '');
    }

    var cinemaHome = document.querySelector('.cinema-home');
    if (cinemaHome) cinemaHome.style.setProperty('--camera-progress', progress.toFixed(4));
  }

  function requestCameraUpdate() {
    if (reducedMotion || pendingFrame || !track) return;
    if (!done) {
      finish(true);
    }
    pendingFrame = true;
    window.requestAnimationFrame(paintCamera);
  }

  function skipAndContinue() { finish(true); }

  if (skip) skip.addEventListener('click', skipAndContinue);
  // Gesture, scroll, and keyboard continuation preserve the browser's native scroll.
  window.addEventListener('wheel', skipAndContinue, { passive: true, once: true });
  window.addEventListener('touchmove', skipAndContinue, { passive: true, once: true });
  window.addEventListener('scroll', skipAndContinue, { passive: true, once: true });
  window.addEventListener('keydown', function (event) {
    if (done || event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey) return;
    var target = event.target;
    if (target && target.closest && target.closest('a,button,input,textarea,select,[contenteditable="true"]')) return;
    if (event.key === 'Escape' || event.key === 'ArrowDown' || event.key === 'PageDown' || event.key === ' ') finish(true);
  });

  if (motionQuery) {
    var onMotionPreference = function (event) {
      if (!event.matches) return;
      reducedMotion = true;
      finish(false);
      releaseReveal();
      if (curtain) curtain.style.display = 'none';
      [production, intermission].forEach(function (panel) {
        if (!panel) return;
        panel.style.setProperty('--production-opacity', '1');
        panel.style.setProperty('--poster-opacity', '1');
        panel.style.setProperty('--poster-scale', '1');
        panel.style.setProperty('--poster-z', '0px');
        panel.style.setProperty('--poster-y', '0px');
        panel.style.setProperty('--poster-tilt', '0deg');
        panel.style.setProperty('--information-opacity', '1');
        panel.style.setProperty('--information-y', '0px');
        panel.style.setProperty('--intermission-opacity', '1');
      });
    };
    if (motionQuery.addEventListener) motionQuery.addEventListener('change', onMotionPreference);
    else if (motionQuery.addListener) motionQuery.addListener(onMotionPreference);
  }

  if (production || intermission) (production || intermission).setAttribute('inert', '');
  if (!reducedMotion) {
    window.addEventListener('scroll', requestCameraUpdate, { passive: true });
    window.addEventListener('resize', requestCameraUpdate, { passive: true });
    window.addEventListener('orientationchange', requestCameraUpdate, { passive: true });
  } else {
    releaseReveal();
  }

  startOverture();

  // Showtime Selection Modal Controller
  if (!window.__elsar5aModalInit) {
    window.__elsar5aModalInit = true;
    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('[data-open-modal]');
      if (trigger) {
        e.preventDefault();
        var modalId = trigger.getAttribute('data-open-modal');
        var modal = document.getElementById(modalId);
        if (modal) {
          modal.removeAttribute('hidden');
          document.body.classList.add('modal-open');
          modal._prevFocus = trigger;
          var closeBtn = modal.querySelector('.showtime-modal-close');
          if (closeBtn) closeBtn.focus();
        }
        return;
      }

      var closeBtn = e.target.closest('[data-close-modal]');
      if (closeBtn) {
        e.preventDefault();
        var modal = closeBtn.closest('.showtime-modal');
        if (modal) {
          modal.setAttribute('hidden', '');
          document.body.classList.remove('modal-open');
          if (modal._prevFocus && typeof modal._prevFocus.focus === 'function') {
            modal._prevFocus.focus();
          }
        }
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        var openModal = document.querySelector('.showtime-modal:not([hidden])');
        if (openModal) {
          openModal.setAttribute('hidden', '');
          document.body.classList.remove('modal-open');
          if (openModal._prevFocus && typeof openModal._prevFocus.focus === 'function') {
            openModal._prevFocus.focus();
          }
        }
      }
    });
  }
})();
