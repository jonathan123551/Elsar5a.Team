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
    if (!track || reducedMotion || !done) return;

    var bounds = track.getBoundingClientRect();
    var travel = Math.max(1, track.offsetHeight - window.innerHeight);
    var progress = clamp(-bounds.top / travel, 0, 1);
    if (Math.abs(progress - lastProgress) < 0.002) return;
    lastProgress = progress;

    var viewport = window.innerHeight;
    var reveal = clamp((progress - 0.16) / 0.29, 0, 1);
    if (world) {
      world.style.setProperty('--world-scale', (1 + progress * 0.48).toFixed(4));
      world.style.setProperty('--world-y', (-Math.round(progress * viewport * 0.055)) + 'px');
    }
    if (identity) {
      identity.style.setProperty('--identity-opacity', clamp(1 - progress * 3.15, 0, 1).toFixed(4));
      identity.style.setProperty('--identity-y', (-Math.round(progress * viewport * 0.16)) + 'px');
    }
    if (production) {
      var posterProgress = clamp((progress - 0.31) / 0.3, 0, 1);
      var informationProgress = clamp((progress - 0.53) / 0.27, 0, 1);
      production.style.setProperty('--production-opacity', reveal.toFixed(4));
      production.style.setProperty('--production-y', Math.round((1 - reveal) * viewport * 0.035) + 'px');
      production.style.setProperty('--production-scale', (0.97 + reveal * 0.03).toFixed(4));
      production.style.setProperty('--poster-opacity', posterProgress.toFixed(4));
      production.style.setProperty('--poster-y', Math.round((1 - posterProgress) * viewport * 0.09) + 'px');
      production.style.setProperty('--poster-scale', (0.92 + posterProgress * 0.08).toFixed(4));
      production.style.setProperty('--information-opacity', informationProgress.toFixed(4));
      production.style.setProperty('--information-y', Math.round((1 - informationProgress) * 16) + 'px');
      if (informationProgress > 0.04) production.removeAttribute('inert');
      else production.setAttribute('inert', '');
    }
    if (intermission) {
      intermission.style.setProperty('--intermission-opacity', reveal.toFixed(4));
      intermission.style.setProperty('--intermission-y', Math.round((1 - reveal) * viewport * 0.035) + 'px');
      if (reveal > 0.04) intermission.removeAttribute('inert');
      else intermission.setAttribute('inert', '');
    }
    var cue = document.querySelector('.camera-cue');
    if (cue) cue.style.setProperty('--cue-opacity', clamp(1 - progress * 2.5, 0, 1).toFixed(4));
    document.querySelector('.cinema-home').style.setProperty('--camera-progress', progress.toFixed(4));
  }

  function requestCameraUpdate() {
    if (!done || reducedMotion || pendingFrame || !track) return;
    pendingFrame = true;
    window.requestAnimationFrame(paintCamera);
  }

  function skipAndContinue() { finish(true); }

  if (skip) skip.addEventListener('click', skipAndContinue);
  // Gesture and keyboard continuation preserve the browser's native scroll.
  window.addEventListener('wheel', skipAndContinue, { passive: true, once: true });
  window.addEventListener('touchmove', skipAndContinue, { passive: true, once: true });
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
      [production, intermission].forEach(function (panel) {
        if (!panel) return;
        panel.style.setProperty('--production-opacity', '1');
        panel.style.setProperty('--poster-opacity', '1');
        panel.style.setProperty('--information-opacity', '1');
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
})();
