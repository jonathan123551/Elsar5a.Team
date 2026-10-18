/*
 * Elsar5a automatic theatre overture and scroll-driven production reveal.
 * Scroll work is passive and paints at most once per animation frame.
 */
(function () {
  'use strict';

  var body = document.body;
  if (!body || !body.classList.contains('homepage')) return;

  var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
  var reducedMotion = !!(motionQuery && motionQuery.matches);
  var curtain = document.getElementById('cinema-curtain');
  var track = document.getElementById('camera-journey');
  var world = document.getElementById('camera-world');
  var auditorium = document.getElementById('camera-auditorium');
  var aisle = document.getElementById('camera-aisle');
  var spotlight = document.getElementById('camera-spotlight');
  var proscenium = document.getElementById('camera-proscenium');
  var production = document.getElementById('stage-production');
  var intermission = document.getElementById('stage-intermission');
  var productionSteps = production ? Array.prototype.slice.call(production.querySelectorAll('[data-production-step]')) : [];
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
    [production, intermission].forEach(function (panel) {
      if (panel) panel.removeAttribute('inert');
    });
  }

  function finish() {
    if (done) return;
    done = true;
    clearTimers();
    body.dataset.introState = 'complete';
    body.classList.remove('cinema-intro-playing');
    if (curtain) curtain.dataset.state = 'open';
    requestCameraUpdate();
  }

  function startOverture() {
    if (window.history && 'scrollRestoration' in window.history) window.history.scrollRestoration = 'manual';
    window.scrollTo(0, 0);

    if (!curtain || reducedMotion) {
      finish();
      releaseReveal();
      return;
    }

    body.classList.add('cinema-intro-playing');
    body.dataset.introState = 'theatre';
    later(220, function () { if (!done) curtain.dataset.state = 'ignite'; });
    later(680, function () { if (!done) curtain.dataset.state = 'draw'; });
    later(2480, function () { if (!done) curtain.dataset.state = 'open'; });
    later(2660, function () { if (!done) body.dataset.introState = 'logo'; });
    later(3060, function () { if (!done) body.dataset.introState = 'name'; });
    later(3440, function () { if (!done) body.dataset.introState = 'manifesto'; });
    later(4260, function () { if (!done) body.dataset.introState = 'push'; });
    later(6100, finish);
  }

  function clamp(value, min, max) { return Math.min(max, Math.max(min, value)); }

  function setStep(step, progress, start, end, fromX) {
    var amount = clamp((progress - start) / (end - start), 0, 1);
    step.style.setProperty('--step-opacity', amount.toFixed(4));
    step.style.setProperty('--step-x', Math.round((1 - amount) * fromX) + 'px');
    step.style.setProperty('--step-y', Math.round((1 - amount) * 14) + 'px');
  }

  function paintCamera() {
    pendingFrame = false;
    if (!track || reducedMotion || !done) return;

    var bounds = track.getBoundingClientRect();
    var travel = Math.max(1, track.offsetHeight - window.innerHeight);
    var progress = clamp(-bounds.top / travel, 0, 1);
    if (Math.abs(progress - lastProgress) < 0.002) return;
    lastProgress = progress;

    var viewport = window.innerHeight;
    var posterProgress = clamp((progress - 0.34) / 0.21, 0, 1);
    var reveal = clamp((progress - 0.34) / 0.21, 0, 1);
    if (world) {
      world.style.setProperty('--world-scale', (1.08 + progress * 0.25).toFixed(4));
      world.style.setProperty('--world-z', (-145 + Math.round(progress * 112)) + 'px');
      world.style.setProperty('--world-y', (-Math.round(progress * viewport * 0.035)) + 'px');
    }
    if (auditorium) {
      auditorium.style.setProperty('--auditorium-scale', (1.01 + progress * 0.18).toFixed(4));
      auditorium.style.setProperty('--auditorium-y', Math.round(progress * viewport * 0.07) + 'px');
    }
    if (aisle) {
      aisle.style.setProperty('--aisle-scale', (1 + progress * 0.31).toFixed(4));
      aisle.style.setProperty('--aisle-y', Math.round(progress * viewport * 0.16) + 'px');
    }
    if (spotlight) {
      spotlight.style.setProperty('--spotlight-opacity', (0.16 + progress * 0.5).toFixed(4));
      spotlight.style.setProperty('--spotlight-scale', (1 + progress * 0.46).toFixed(4));
      spotlight.style.setProperty('--spotlight-y', Math.round(progress * viewport * 0.04) + 'px');
    }
    if (proscenium) {
      proscenium.style.setProperty('--proscenium-scale', (1 + progress * 0.23).toFixed(4));
      proscenium.style.setProperty('--proscenium-y', Math.round(progress * viewport * 0.075) + 'px');
      proscenium.style.setProperty('--proscenium-opacity', clamp(1 - (progress - 0.76) / 0.24, 0, 1).toFixed(4));
    }
    if (production) {
      var titleProgress = clamp((progress - 0.49) / 0.12, 0, 1);
      var actionProgress = clamp((progress - 0.82) / 0.13, 0, 1);
      production.style.setProperty('--production-opacity', reveal.toFixed(4));
      production.style.setProperty('--production-y', Math.round((1 - reveal) * viewport * 0.035) + 'px');
      production.style.setProperty('--production-scale', (0.97 + reveal * 0.03).toFixed(4));
      production.style.setProperty('--poster-opacity', posterProgress.toFixed(4));
      production.style.setProperty('--poster-y', Math.round((1 - posterProgress) * viewport * 0.035) + 'px');
      production.style.setProperty('--poster-z', Math.round((1 - posterProgress) * -520) + 'px');
      production.style.setProperty('--poster-rotate-y', ((1 - posterProgress) * 10) + 'deg');
      production.style.setProperty('--poster-rotate-x', ((1 - posterProgress) * 3) + 'deg');
      production.style.setProperty('--poster-scale', (0.58 + posterProgress * 0.42).toFixed(4));
      production.style.setProperty('--information-opacity', clamp((progress - 0.49) / 0.38, 0, 1).toFixed(4));
      production.style.setProperty('--information-y', Math.round((1 - titleProgress) * 13) + 'px');
      productionSteps.forEach(function (step) {
        var name = step.getAttribute('data-production-step');
        if (name === 'title') setStep(step, progress, 0.49, 0.61, -16);
        else if (name === 'description') setStep(step, progress, 0.56, 0.69, 15);
        else if (name === 'meta') setStep(step, progress, 0.63, 0.76, -15);
        else if (name === 'times') setStep(step, progress, 0.69, 0.79, 15);
        else if (name === 'status') setStep(step, progress, 0.75, 0.86, 14);
        else if (name === 'action') setStep(step, progress, 0.82, 0.95, 0);
      });
      if (actionProgress >= 0.98) production.removeAttribute('inert');
      else production.setAttribute('inert', '');
    }
    if (intermission) {
      intermission.style.setProperty('--intermission-opacity', reveal.toFixed(4));
      intermission.style.setProperty('--intermission-y', Math.round((1 - reveal) * viewport * 0.035) + 'px');
      if (reveal >= 1) intermission.removeAttribute('inert');
      else intermission.setAttribute('inert', '');
    }
    var cue = document.querySelector('.camera-cue');
    if (cue) cue.style.setProperty('--cue-opacity', clamp(1 - progress * 2.5, 0, 1).toFixed(4));
    document.querySelector('.cinema-home').style.setProperty('--camera-progress', progress.toFixed(4));
    if (curtain && progress > 0.97) curtain.classList.add('is-passed');
  }

  function requestCameraUpdate() {
    if (!done || reducedMotion || pendingFrame || !track) return;
    pendingFrame = true;
    window.requestAnimationFrame(paintCamera);
  }

  function setReducedMotionContent() {
    releaseReveal();
    if (production) {
      ['--production-opacity', '--poster-opacity', '--information-opacity'].forEach(function (property) {
        production.style.setProperty(property, '1');
      });
      productionSteps.forEach(function (step) {
        step.style.setProperty('--step-opacity', '1');
        step.style.setProperty('--step-x', '0px');
        step.style.setProperty('--step-y', '0px');
      });
    }
    if (intermission) intermission.style.setProperty('--intermission-opacity', '1');
  }

  if (motionQuery) {
    var onMotionPreference = function (event) {
      if (!event.matches) return;
      reducedMotion = true;
      finish();
      setReducedMotionContent();
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
    setReducedMotionContent();
  }

  startOverture();

  // Existing showtime-selection modal controller.
  if (!window.__elsar5aModalInit) {
    window.__elsar5aModalInit = true;
    document.addEventListener('click', function (event) {
      var trigger = event.target.closest('[data-open-modal]');
      if (trigger) {
        event.preventDefault();
        var modal = document.getElementById(trigger.getAttribute('data-open-modal'));
        if (modal) {
          modal.removeAttribute('hidden');
          document.body.classList.add('modal-open');
          modal._prevFocus = trigger;
          var closeButton = modal.querySelector('.showtime-modal-close');
          if (closeButton) closeButton.focus();
        }
        return;
      }

      var closeTarget = event.target.closest('[data-close-modal]');
      if (closeTarget) {
        event.preventDefault();
        var openModal = closeTarget.closest('.showtime-modal');
        if (openModal) {
          openModal.setAttribute('hidden', '');
          document.body.classList.remove('modal-open');
          if (openModal._prevFocus && typeof openModal._prevFocus.focus === 'function') openModal._prevFocus.focus();
        }
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      var openModal = document.querySelector('.showtime-modal:not([hidden])');
      if (openModal) {
        openModal.setAttribute('hidden', '');
        document.body.classList.remove('modal-open');
        if (openModal._prevFocus && typeof openModal._prevFocus.focus === 'function') openModal._prevFocus.focus();
      }
    });
  }
})();
