/**
 * ELSAR5A THEATRE — TWO-ACT SCROLL CHOREOGRAPHY
 * Act I (0.00 -> 0.42): Approved Curtain Tie-Back Opening & Team Intro
 * Act II (0.34 -> 1.00): Direct Current Production Playbill Reveal
 */
(function () {
    'use strict';

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function invLerp(start, end, value) {
        if (start === end) return 0;
        return clamp((value - start) / (end - start), 0, 1);
    }

    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    function easeInOutCubic(t) {
        return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    function initStageJourney() {
        var track = document.getElementById('stage-scroll-track');
        if (!track) return;

        var introScene = document.getElementById('stage-intro-scene');
        var showScene = document.getElementById('stage-show-scene');
        var scrollPrompt = document.getElementById('scroll-prompt');
        var spotlight = document.getElementById('stage-spotlight');
        var stageFloor = document.getElementById('stage-floor');
        var pathR = document.getElementById('curtain-path-r');
        var trimR = document.getElementById('curtain-trim-r');
        var pathL = document.getElementById('curtain-path-l');
        var trimL = document.getElementById('curtain-trim-l');

        var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        /**
         * Approved Parabolic SVG Curtain Tie-Back Geometry
         * Preserves the exact visual drape opening from the approved intro.
         */
        function updateCurtainGeometry(openProgress) {
            var c = easeInOutCubic(clamp(openProgress, 0, 1));

            // Right curtain panel (pulls right toward x=100)
            var topEdgeR = c * 74;
            var pinchXR = c * 88;
            var pinchYR = 36 - c * 8;
            var hemXR = c * 95;
            var ctrl2YR = 58 + c * 14;

            var dPathR = 'M 100,0 L ' + topEdgeR.toFixed(2) + ',0 ' +
                'C ' + pinchXR.toFixed(2) + ',' + pinchYR.toFixed(2) + ' ' +
                hemXR.toFixed(2) + ',' + ctrl2YR.toFixed(2) + ' ' +
                hemXR.toFixed(2) + ',100 L 100,100 Z';

            var dTrimR = 'M ' + topEdgeR.toFixed(2) + ',0 ' +
                'C ' + pinchXR.toFixed(2) + ',' + pinchYR.toFixed(2) + ' ' +
                hemXR.toFixed(2) + ',' + ctrl2YR.toFixed(2) + ' ' +
                hemXR.toFixed(2) + ',100';

            if (pathR) pathR.setAttribute('d', dPathR);
            if (trimR) trimR.setAttribute('d', dTrimR);

            // Left curtain panel (mirror toward x=0)
            var topEdgeL = 100 - topEdgeR;
            var pinchXL = 100 - pinchXR;
            var hemXL = 100 - hemXR;

            var dPathL = 'M 0,0 L ' + topEdgeL.toFixed(2) + ',0 ' +
                'C ' + pinchXL.toFixed(2) + ',' + pinchYR.toFixed(2) + ' ' +
                hemXL.toFixed(2) + ',' + ctrl2YR.toFixed(2) + ' ' +
                hemXL.toFixed(2) + ',100 L 0,100 Z';

            var dTrimL = 'M ' + topEdgeL.toFixed(2) + ',0 ' +
                'C ' + pinchXL.toFixed(2) + ',' + pinchYR.toFixed(2) + ' ' +
                hemXL.toFixed(2) + ',' + ctrl2YR.toFixed(2) + ' ' +
                hemXL.toFixed(2) + ',100';

            if (pathL) pathL.setAttribute('d', dPathL);
            if (trimL) trimL.setAttribute('d', dTrimL);
        }

        function renderProgress(p) {
            // 1. Curtain tie-back opening: starts at 0.14 open so the emblem glows through,
            // fully parts by p = 0.42.
            var curtainProgress = 0.14 + invLerp(0.0, 0.42, p) * 0.86;
            updateCurtainGeometry(curtainProgress);

            // 2. Scroll prompt fades out immediately as user begins scrolling
            if (scrollPrompt) {
                var promptAlpha = 1 - invLerp(0.01, 0.14, p);
                scrollPrompt.style.opacity = promptAlpha.toFixed(3);
            }

            // 3. ACT I: Intro Scene holds during initial curtain part, then recedes cleanly (0.24 -> 0.42)
            if (introScene) {
                var introExit = easeInOutCubic(invLerp(0.24, 0.42, p));
                var introAlpha = 1 - introExit;
                var introY = -36 * introExit;
                var introScale = 1 - 0.08 * introExit;

                introScene.style.opacity = introAlpha.toFixed(3);
                introScene.style.transform = 'translate3d(0, ' + introY.toFixed(2) + 'px, 0) scale(' + introScale.toFixed(3) + ')';
                introScene.style.pointerEvents = introAlpha > 0.35 ? 'auto' : 'none';
            }

            // 4. ACT II: Current Production Playbill Reveal rises directly into spotlight (0.34 -> 0.56)
            if (showScene) {
                var showEnter = easeOutCubic(invLerp(0.34, 0.56, p));
                var showY = (1 - showEnter) * 42;
                var showScale = 0.94 + showEnter * 0.06;

                showScene.style.opacity = showEnter.toFixed(3);
                showScene.style.transform = 'translate3d(0, ' + showY.toFixed(2) + 'px, 0) scale(' + showScale.toFixed(3) + ')';

                if (showEnter >= 0.45) {
                    showScene.classList.add('is-interactive');
                } else {
                    showScene.classList.remove('is-interactive');
                }
            }

            // 5. Stage spotlight intensifies as the Playbill takes center stage
            if (spotlight) {
                var spotProgress = easeOutCubic(invLerp(0.0, 0.55, p));
                var spotOpacity = 0.42 + spotProgress * 0.48;
                var spotScale = 0.92 + spotProgress * 0.16;

                spotlight.style.opacity = spotOpacity.toFixed(3);
                spotlight.style.transform = 'translateX(-50%) scale(' + spotScale.toFixed(3) + ')';
            }

            if (stageFloor) {
                var floorScale = 1 + invLerp(0.0, 0.65, p) * 0.12;
                stageFloor.style.transform = 'scaleY(' + floorScale.toFixed(3) + ')';
            }
        }

        if (prefersReducedMotion) {
            updateCurtainGeometry(1);
            if (showScene) {
                showScene.classList.add('is-interactive');
            }
            return;
        }

        var ticking = false;

        function computeScrollProgress() {
            var rect = track.getBoundingClientRect();
            var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 1;
            var totalScrollable = Math.max(1, track.offsetHeight - viewportHeight);
            var scrolled = clamp(-rect.top, 0, totalScrollable);
            return scrolled / totalScrollable;
        }

        function onScrollOrResize() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () {
                ticking = false;
                renderProgress(computeScrollProgress());
            });
        }

        window.addEventListener('scroll', onScrollOrResize, { passive: true });
        window.addEventListener('resize', onScrollOrResize, { passive: true });

        // Initial paint
        renderProgress(computeScrollProgress());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStageJourney);
    } else {
        initStageJourney();
    }
})();
