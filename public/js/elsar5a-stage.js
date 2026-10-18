/*
 * Elsar5a automatic theatre overture.
 * One GSAP playhead owns the full transition; scroll never advances it.
 */
(function () {
  'use strict';

  var body = document.body;
  if (!body || !body.classList.contains('homepage')) return;

  var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
  var reducedMotion = !!(motionQuery && motionQuery.matches);
  var curtain = document.getElementById('cinema-curtain');
  var opening = document.getElementById('opening');
  var track = document.getElementById('camera-journey');
  var plate = opening ? opening.querySelector('.cinema-plate') : null;
  var openingScrim = opening ? opening.querySelector('.opening-scrim') : null;
  var blackout = document.getElementById('opening-blackout');
  var openingCopy = opening ? opening.querySelector('.opening-copy') : null;
  var logo = opening ? opening.querySelector('.logo-reveal') : null;
  var openingArchive = opening ? opening.querySelector('.opening-archive') : null;
  var openingLines = opening ? Array.prototype.slice.call(opening.querySelectorAll('[data-opening-phrase]')) : [];
  var world = document.getElementById('camera-world');
  var websiteWorld = document.getElementById('website-world');
  var auditorium = document.getElementById('camera-auditorium');
  var aisle = document.getElementById('camera-aisle');
  var spotlight = document.getElementById('camera-spotlight');
  var proscenium = document.getElementById('camera-proscenium');
  var siteHeader = document.querySelector('#elsar5a-stage-world > header');
  var production = document.getElementById('stage-production');
  var productionInformation = production ? production.querySelector('.production-information') : null;
  var intermission = document.getElementById('stage-intermission');
  var productionSteps = production ? Array.prototype.slice.call(production.querySelectorAll('[data-production-step]')).filter(function (step) {
    return step.getAttribute('data-production-step') !== 'showtimes';
  }) : [];
  var showtimeCards = production ? Array.prototype.slice.call(production.querySelectorAll('[data-showtime-card]')) : [];
  var cinematicShowtimes = production ? production.querySelector('.cinematic-showtimes') : null;
  var master = null;
  var done = false;
  var blockedKeys = { ArrowDown: true, ArrowUp: true, PageDown: true, PageUp: true, Home: true, End: true, Space: true };

  function lockScroll(event) { event.preventDefault(); }
  function lockKeys(event) { if (blockedKeys[event.code] || blockedKeys[event.key]) event.preventDefault(); }
  function lockIntroScroll() {
    window.addEventListener('wheel', lockScroll, { passive: false });
    window.addEventListener('touchmove', lockScroll, { passive: false });
    window.addEventListener('keydown', lockKeys);
  }
  function unlockIntroScroll() {
    window.removeEventListener('wheel', lockScroll);
    window.removeEventListener('touchmove', lockScroll);
    window.removeEventListener('keydown', lockKeys);
  }
  function releaseExperience() {
    if (done) return;
    done = true;
    body.dataset.introState = 'complete';
    body.classList.remove('cinema-intro-playing');
    body.classList.add('cinema-complete');
    var posterEl = document.querySelector('.production-poster-wrap, .production-poster-missing');
    if (posterEl) {
      posterEl.classList.remove('is-flying');
      posterEl.classList.add('is-settled');
    }
    if (opening) opening.setAttribute('aria-hidden', 'true');
    if (track) track.removeAttribute('inert');
    if (production) production.removeAttribute('inert');
    if (intermission) intermission.removeAttribute('inert');
    if (siteHeader) siteHeader.removeAttribute('inert');
    unlockIntroScroll();
  }
  function showReducedMotionContent() {
    body.dataset.introState = 'complete';
    body.classList.remove('cinema-intro-playing');
    body.classList.add('cinema-complete', 'cinema-static-fallback');
    if (curtain) curtain.dataset.state = 'open';
    if (opening) opening.setAttribute('aria-hidden', 'true');
    if (track) track.removeAttribute('inert');
    if (production) production.removeAttribute('inert');
    if (intermission) intermission.removeAttribute('inert');
    if (siteHeader) siteHeader.removeAttribute('inert');
    unlockIntroScroll();
  }
  function buildTimeline() {
    var gsap = window.gsap;
    if (!gsap || !opening || !track) {
      showReducedMotionContent();
      return;
    }
    if (window.history && 'scrollRestoration' in window.history) window.history.scrollRestoration = 'manual';
    window.scrollTo(0, 0);
    lockIntroScroll();
    body.classList.add('cinema-intro-playing');
    body.dataset.introState = 'theatre';
    opening.setAttribute('inert', '');
    if (openingArchive) openingArchive.setAttribute('inert', '');
    if (siteHeader) siteHeader.setAttribute('inert', '');

    gsap.set(track, { autoAlpha: 0 });
    gsap.set(websiteWorld, { autoAlpha: 0 });
    gsap.set(world, { autoAlpha: 1, scale: 1.08, transformPerspective: 1200 });
    gsap.set(auditorium, { autoAlpha: 1, x: 0, y: 0, z: -72, scale: 1.04, transformPerspective: 1200 });
    gsap.set(aisle, { autoAlpha: 1, x: 0, y: 0, z: -34, scale: 1.02, transformPerspective: 1200 });
    gsap.set(spotlight, { autoAlpha: .2, x: 0, y: 0, z: -12, scale: 1, transformPerspective: 1200 });
    gsap.set(proscenium, { autoAlpha: 1, x: 0, y: 0, z: 18, scale: 1, transformPerspective: 1200 });
    gsap.set(blackout, { autoAlpha: 0, scale: .82 });
    gsap.set(openingCopy, { autoAlpha: 1, y: 0, z: 0, scale: 1, transformPerspective: 1200 });
    gsap.set(logo, { autoAlpha: 0, y: 18, scale: .88 });
    gsap.set(openingLines, { autoAlpha: 0, y: 18, scale: .97, filter: 'blur(3px)' });
    if (production) {
      gsap.set(production, { autoAlpha: 0, y: 20 });
      gsap.set(production.querySelectorAll('.production-poster-wrap, .production-poster-missing'), { autoAlpha: 0, z: -1500, y: 22, scale: .28, rotationY: -64, rotationX: 13, transformPerspective: 1600 });
      gsap.set(productionInformation, { autoAlpha: 0, y: 0 });
      gsap.set(productionSteps, { autoAlpha: 0, y: 14 });
      if (productionSteps[0]) gsap.set(productionSteps[0], { scale: .985 });
      gsap.set(cinematicShowtimes, { autoAlpha: 0, y: 0 });
      gsap.set(showtimeCards, { autoAlpha: 0, y: 18 });
    }
    if (intermission) gsap.set(intermission, { autoAlpha: 0, y: 16 });

    master = gsap.timeline({ defaults: { ease: 'power3.out' } });
    master
      .addLabel('CURTAIN')
      .call(function () { if (curtain) curtain.dataset.state = 'ignite'; }, null, '+=0.20')
      .call(function () { if (curtain) curtain.dataset.state = 'draw'; }, null, '+=0.45')
      .call(function () { if (curtain) curtain.dataset.state = 'open'; }, null, '+=1.25')
      .addLabel('IDENTITY')
      .to(logo, { autoAlpha: 1, y: 0, scale: 1, duration: .95, ease: 'power2.out' }, '+=.3')
      .addLabel('LOGO_HOLD')
      .to({}, { duration: 2.15 })
      .addLabel('TEXT_SEQUENCE')
      .to(logo, { autoAlpha: 0, y: -8, scale: .9, duration: .6, ease: 'power2.inOut' }, '+=.15')
      .to(openingLines[0], { autoAlpha: 1, y: 0, scale: 1, filter: 'blur(0px)', duration: .72, ease: 'power2.out' })
      .to({}, { duration: .8 })
      .to(openingLines[0], { autoAlpha: 0, y: -10, filter: 'blur(3px)', duration: .32, ease: 'power2.in' })
      .to(openingLines[1], { autoAlpha: 1, y: 0, scale: 1, filter: 'blur(0px)', duration: .68, ease: 'power2.out' })
      .to({}, { duration: .65 })
      .to(openingLines[1], { autoAlpha: 0, y: -10, filter: 'blur(3px)', duration: .32, ease: 'power2.in' })
      .to(openingLines[2], { autoAlpha: 1, y: 0, scale: 1, filter: 'blur(0px)', duration: .68, ease: 'power2.out' })
      .to({}, { duration: .65 })
      .to(openingLines[2], { autoAlpha: 0, y: -10, filter: 'blur(3px)', duration: .32, ease: 'power2.in' })
      .to(openingLines[3], { autoAlpha: 1, y: 0, scale: 1, filter: 'blur(0px)', duration: .82, ease: 'power2.out' })
      .to({}, { duration: 1.2 })
      .addLabel('IDENTITY_EXIT')
      .addLabel('CAMERA_PUSH')
      .to(track, { autoAlpha: 1, duration: .45, ease: 'power2.out' })
      .to(openingCopy, { autoAlpha: 0, y: -10, z: 620, scale: 2.85, rotationX: -4, filter: 'blur(5px)', duration: 2.45, ease: 'power3.in' }, '<')
      .to(plate, { autoAlpha: 0, scale: 2.15, z: 620, duration: 2.35, ease: 'power3.in' }, '<')
      .to(openingScrim, { autoAlpha: 0, duration: .95, ease: 'power2.in' }, '<')
      // Different apparent travel rates make the established theatre planes read as depth.
      .to(world, { scale: 1.36, y: -18, z: 92, duration: 2.25, ease: 'power3.inOut' }, '<')
      .to(auditorium, { scale: 1.48, y: 48, z: 28, duration: 2.25, ease: 'power3.inOut' }, '<')
      .to(aisle, { scale: 2.02, y: 118, z: 134, duration: 2.25, ease: 'power3.inOut' }, '<')
      .to(spotlight, { autoAlpha: .62, scale: 1.38, y: -16, duration: 2.1, ease: 'power2.out' }, '<')
      .to(proscenium, { autoAlpha: .14, scale: 1.32, z: 170, duration: 2.05, ease: 'power3.in' }, '<')
      .to(blackout, { autoAlpha: 1, scale: 1.08, duration: .82, ease: 'power2.in' }, '-=.82')
      .to(curtain, { autoAlpha: 0, duration: .42, ease: 'power2.in' }, '<')
      .addLabel('BLACK')
      .to({}, { duration: .6 })
      .set(opening, { zIndex: 52 })
      .set(track, { autoAlpha: 1 })
      .call(function () { body.classList.add('cinema-website-stage'); })
      .to(websiteWorld, { autoAlpha: 1, duration: .85, ease: 'power2.out' })
      .to([world, auditorium, aisle, spotlight, proscenium], { autoAlpha: 0, duration: .72, ease: 'power2.inOut' }, '<')
      .addLabel('WEBSITE_ENVIRONMENT');

    if (production) {
      var poster = production.querySelector('.production-poster-wrap, .production-poster-missing');
      master
        .set(production, { autoAlpha: 1, y: 0 })
        .addLabel('POSTER_REVEAL')
        .to(poster, {
          keyframes: {
            autoAlpha: [0, 1, 1, 1],
            z: [-1500, -760, -250, 28],
            y: [22, 10, -2, 0],
            scale: [.28, .48, .78, 1.035],
            rotationY: [-64, -40, -13, 4],
            rotationX: [13, 8, 4, -1.5],
            easeEach: 'none'
          },
          duration: 2.95,
          ease: 'power2.inOut'
        })
        .to(poster, { z: 0, y: 0, scale: 1, rotationY: 0, rotationX: 0, duration: .46, ease: 'power1.out' })
        .addLabel('POSTER_SETTLED')
        .addLabel('PRODUCTION_INFO')
        .to(productionInformation, { autoAlpha: 1, duration: .34, ease: 'power2.out' }, '+=.75')
        .to(productionSteps[0], { autoAlpha: 1, y: 0, scale: 1, duration: .66, ease: 'power2.out' }, '+=.12')
        .to(productionSteps.slice(1), { autoAlpha: 1, y: 0, duration: .44, stagger: .18, ease: 'power2.out' }, '+=.12')
        .to(cinematicShowtimes, { autoAlpha: 1, duration: .38, ease: 'power2.out' }, '+=.2')
        .to(showtimeCards, { autoAlpha: 1, y: 0, rotationX: 0, duration: .5, stagger: .14, ease: 'power2.out' }, '<+.04')
        .addLabel('SHOWTIMES');
    } else if (intermission) {
      master.to(intermission, { autoAlpha: 1, y: 0, duration: .55, ease: 'power2.out' }, '+=.5');
    }
    master.addLabel('INTERACTIVE').call(releaseExperience);
  }

  if (motionQuery) {
    var onMotionPreference = function (event) {
      if (!event.matches) return;
      reducedMotion = true;
      if (master) master.kill();
      unlockIntroScroll();
      showReducedMotionContent();
    };
    if (motionQuery.addEventListener) motionQuery.addEventListener('change', onMotionPreference);
    else if (motionQuery.addListener) motionQuery.addListener(onMotionPreference);
  }

  if (reducedMotion) showReducedMotionContent();
  else buildTimeline();

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

  // Prevent accidental double-tap zoom on iOS Safari interactive elements
  var lastTouchEnd = 0;
  document.addEventListener('touchend', function (event) {
    var now = Date.now();
    if (now - lastTouchEnd <= 300) {
      var interactive = event.target.closest('a, button, [role="button"], .cinematic-showtime-card, .theatre-location-btn');
      if (interactive) {
        event.preventDefault();
        interactive.click();
      }
    }
    lastTouchEnd = now;
  }, { passive: false });
})();
