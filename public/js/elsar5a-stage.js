(function () {
    'use strict';

    var track = document.getElementById('stage-scroll-track');
    var leftPath = document.getElementById('curtainLeftPath');
    var rightPath = document.getElementById('curtainRightPath');
    var leftWing = document.getElementById('curtain-left');
    var rightWing = document.getElementById('curtain-right');
    var seamBeam = document.getElementById('curtain-seam');
    var scrollPrompt = document.getElementById('curtain-scroll-prompt');
    var closedCurtainsLayer = document.getElementById('layer-closed-curtains');

    var openStageLayer = document.getElementById('layer-open-stage');
    var logoScene = document.getElementById('stage-logo-scene');
    var showScene = document.getElementById('stage-show-scene');
    var posterCard = document.getElementById('emerging-poster-card');
    var boxOfficeCard = document.getElementById('emerging-boxoffice-card');

    var currentProgress = 0;
    var animFrameId = null;
    var prefersReducedMotion = false;

    function clamp(val, min, max) {
        return Math.max(min, Math.min(max, val));
    }

    function invLerp(a, b, v) {
        return clamp((v - a) / (b - a), 0, 1);
    }

    function easeInOutCubic(t) {
        return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    // -------------------------------------------------------------------------
    // TWO-ACT STAGE CHOREOGRAPHY
    // Act I  (p: 0.00 -> 0.42): Approved Curtain Opening + Logo & Motto Intro
    // Act II (p: 0.34 -> 1.00): Logo Recedes -> Real Production Emerges -> Book
    // -------------------------------------------------------------------------
    function renderStageAtProgress(p) {
        // 1. Parabolic Curtain Parting & Tie-Back Gathering (p: 0.01 -> 0.30)
        var cProg = easeInOutCubic(invLerp(0.01, 0.30, p));

        var topX = 0.505 - cProg * 0.36;
        var waistX = 0.505 - Math.pow(cProg, 0.82) * 0.49;
        var hemX = 0.505 - cProg * 0.45;

        if (leftPath && rightPath) {
            var lD = 'M 0,0 L ' + topX.toFixed(3) + ',0 C ' +
                     (topX - cProg * 0.08).toFixed(3) + ',0.30 ' +
                     waistX.toFixed(3) + ',0.56 ' +
                     waistX.toFixed(3) + ',0.68 C ' +
                     (waistX + cProg * 0.04).toFixed(3) + ',0.84 ' +
                     hemX.toFixed(3) + ',0.94 ' +
                     hemX.toFixed(3) + ',1 L 0,1 Z';

            var rTopX = 1 - topX;
            var rWaistX = 1 - waistX;
            var rHemX = 1 - hemX;

            var rD = 'M 1,0 L ' + rTopX.toFixed(3) + ',0 C ' +
                     (rTopX + cProg * 0.08).toFixed(3) + ',0.30 ' +
                     rWaistX.toFixed(3) + ',0.56 ' +
                     rWaistX.toFixed(3) + ',0.68 C ' +
                     (rWaistX - cProg * 0.04).toFixed(3) + ',0.84 ' +
                     rHemX.toFixed(3) + ',0.94 ' +
                     rHemX.toFixed(3) + ',1 L 1,1 Z';

            leftPath.setAttribute('d', lD);
            rightPath.setAttribute('d', rD);
        }

        if (leftWing && rightWing) {
            var wingScaleX = 1 - cProg * 0.14;
            var wingRot = cProg * 4.2;
            leftWing.style.transform = 'scaleX(' + wingScaleX.toFixed(3) + ') rotateZ(' + wingRot.toFixed(2) + 'deg)';
            rightWing.style.transform = 'scaleX(' + wingScaleX.toFixed(3) + ') rotateZ(-' + wingRot.toFixed(2) + 'deg)';
        }

        if (seamBeam) {
            var seamOpacity = p < 0.04 ? 1 : (1 - invLerp(0.04, 0.16, p));
            seamBeam.style.opacity = seamOpacity.toFixed(3);
            seamBeam.style.transform = 'translateX(-50%) scaleX(' + (1 + invLerp(0, 0.14, p) * 3.2).toFixed(2) + ')';
        }

        if (scrollPrompt) {
            var promptOpacity = 1 - invLerp(0.02, 0.16, p);
            scrollPrompt.style.opacity = promptOpacity.toFixed(3);
            scrollPrompt.style.pointerEvents = promptOpacity > 0.2 ? 'auto' : 'none';
        }

        if (closedCurtainsLayer) {
            var curtainLayerOpacity = 1 - invLerp(0.24, 0.36, p);
            closedCurtainsLayer.style.opacity = curtainLayerOpacity.toFixed(3);
        }

        // 2. Act I Logo & Identity Intro (Visible as curtain parts, recedes at 0.26 -> 0.46)
        if (logoScene) {
            var logoIn = easeInOutCubic(invLerp(0.04, 0.20, p));
            var logoOut = easeInOutCubic(invLerp(0.26, 0.46, p));
            var logoOpacity = logoIn * (1 - logoOut);
            var logoScale = 0.92 + logoIn * 0.08 + logoOut * 0.32;
            logoScene.style.opacity = logoOpacity.toFixed(3);
            logoScene.style.transform = 'scale(' + logoScale.toFixed(3) + ')';
        }

        // 3. Camera Push into the Open Wooden Stage
        if (openStageLayer) {
            var stageZoom = 0.96 + easeInOutCubic(invLerp(0.05, 0.82, p)) * 0.22;
            openStageLayer.style.transform = 'scale(' + stageZoom.toFixed(3) + ')';
        }

        // 4. Act II Direct Current Production Playbill Reveal (p: 0.34 -> 0.72)
        if (showScene) {
            var showIn = easeInOutCubic(invLerp(0.34, 0.70, p));
            showScene.style.opacity = showIn.toFixed(3);
            showScene.style.pointerEvents = showIn > 0.45 ? 'auto' : 'none';

            if (boxOfficeCard) {
                var cardY = (1 - showIn) * 36;
                var cardScale = 0.90 + showIn * 0.10;
                boxOfficeCard.style.transform = 'translateY(' + cardY.toFixed(1) + 'px) scale(' + cardScale.toFixed(3) + ')';
            }

            if (posterCard) {
                var posterY = (1 - showIn) * 24;
                posterCard.style.transform = 'translateY(' + posterY.toFixed(1) + 'px)';
            }
        }
    }

    function animateStageTo(targetP, durationMs) {
        if (!track) return;
        if (animFrameId) {
            cancelAnimationFrame(animFrameId);
            animFrameId = null;
        }

        var startP = currentProgress;
        var diff = clamp(targetP, 0, 1) - startP;
        var startTime = performance.now();
        var dur = durationMs || 950;

        function step(now) {
            var t = clamp((now - startTime) / dur, 0, 1);
            var eased = easeInOutCubic(t);
            var nextP = startP + diff * eased;
            currentProgress = nextP;
            renderStageAtProgress(nextP);

            var scrollable = track.offsetHeight - window.innerHeight;
            if (scrollable > 0) {
                window.scrollTo({ top: track.offsetTop + scrollable * nextP, behavior: 'auto' });
            }

            if (t < 1) {
                animFrameId = requestAnimationFrame(step);
            } else {
                animFrameId = null;
            }
        }

        animFrameId = requestAnimationFrame(step);
    }

    function onScroll() {
        if (!track || prefersReducedMotion) return;
        var rect = track.getBoundingClientRect();
        var scrollable = track.offsetHeight - window.innerHeight;
        var p = scrollable > 0 ? clamp(-rect.top / scrollable, 0, 1) : 0;
        currentProgress = p;
        renderStageAtProgress(p);
    }

    function initNavigationDrawer() {
        var menuBtn = document.getElementById('menu-drawer-btn');
        var closeBtn = document.getElementById('close-menu-drawer-btn');
        var backdrop = document.getElementById('drawer-backdrop');
        var drawer = document.getElementById('theatre-menu-drawer');

        function setDrawerOpen(isOpen) {
            if (!drawer || !menuBtn) return;
            drawer.classList.toggle('is-open', isOpen);
            drawer.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        if (menuBtn) {
            menuBtn.addEventListener('click', function () {
                setDrawerOpen(true);
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                setDrawerOpen(false);
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                setDrawerOpen(false);
            });
        }
    }

    window.addEventListener('DOMContentLoaded', function () {
        initNavigationDrawer();

        prefersReducedMotion = Boolean(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        if (prefersReducedMotion) {
            currentProgress = 0.88;
            renderStageAtProgress(0.88);
        } else {
            onScroll();
            // Gentle opening overture on load so the curtain parts into the approved intro
            setTimeout(function () {
                if (currentProgress < 0.05) {
                    renderStageAtProgress(0.22);
                }
            }, 350);
        }

        if (scrollPrompt) {
            scrollPrompt.addEventListener('click', function () {
                if (prefersReducedMotion) {
                    renderStageAtProgress(0.88);
                } else {
                    animateStageTo(0.88, 1100);
                }
            });
        }
    });

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
})();
