/**
 * Drag-to-spin 360° viewer for a sprite sheet. Plain JavaScript, no libraries.
 *
 * Markup (see resources/views/marketing/home.blade.php):
 *   <div data-spinner data-src="sheet.webp" data-frames="5" data-columns="4" data-rows="2">
 *     <img data-spinner-poster ...>      shown until the sheet has loaded, and without JavaScript
 *     <canvas data-spinner-canvas></canvas>
 *   </div>
 *
 * How the sheet is sliced: it is a grid of equally sized frames, in rotation
 * order, read left to right and top to bottom. For frame i:
 *   col = i % columns,  row = Math.floor(i / columns)
 *   frameWidth = sheetWidth / columns,  frameHeight = sheetHeight / rows
 *   ctx.drawImage(sheet, col * frameWidth, row * frameHeight, frameWidth, frameHeight, 0, 0, canvas.width, canvas.height)
 * So one image is downloaded, and each frame is cut out of it while drawing.
 *
 * Rotation is a continuous "position" counted in frames: 2.4 means 40% of the
 * way from frame 2 to frame 3. In-between positions cross-fade the two
 * neighbouring frames, so a drag reads as smooth even with few frames.
 */

const ease = {
    outCubic: (t) => 1 - (1 - t) ** 3,
    inOutCubic: (t) => (t < 0.5 ? 4 * t ** 3 : 1 - (-2 * t + 2) ** 3 / 2),
};

/**
 * @param {HTMLElement} root
 * @param {{ reducedMotion: MediaQueryList, onChange?: (state: { position: number, angle: number, frame: number }) => void }} options
 */
export function createSpinner(root, { reducedMotion, onChange = () => {} }) {
    const canvas = root.querySelector('[data-spinner-canvas]');
    const ctx = canvas.getContext('2d');
    const frames = Number(root.dataset.frames);
    const columns = Number(root.dataset.columns);
    const rows = Number(root.dataset.rows);

    const sheet = new Image();
    let frameWidth = 0;
    let frameHeight = 0;
    let ready = false;

    let position = 0; // in frames; unbounded, wrapped when drawn
    let velocity = 0; // frames per millisecond, for the glide after a drag
    let tween = null; // { from, to, start, duration, easing } for snaps, arrow keys and autoplay
    let dragging = null; // { pointerId, lastX, lastTime }
    let rafId = 0;
    let lastTick = 0;

    let autoplayTimer = 0;
    let autoplayEnabled = false;
    let hovered = false;
    let focused = false;
    let visible = true;
    let resumeAt = 0; // autoplay waits a few seconds after any interaction

    // ---- Drawing --------------------------------------------------------------------

    function wrap(value) {
        return ((value % frames) + frames) % frames;
    }

    function drawFrame(index, alpha) {
        const col = index % columns;
        const row = Math.floor(index / columns);
        ctx.globalAlpha = alpha;
        ctx.drawImage(sheet, col * frameWidth, row * frameHeight, frameWidth, frameHeight, 0, 0, canvas.width, canvas.height);
    }

    function draw() {
        if (!ready) return;

        const p = wrap(position);
        const a = Math.floor(p);
        const b = (a + 1) % frames;
        // Stay on a crisp frame for most of each step; blend only around the middle.
        const t = Math.min(1, Math.max(0, (p - a - 0.25) / 0.5));

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        // The frame we're closer to is drawn fully opaque and the other fades
        // over it, so the model never turns see-through mid-blend.
        if (t < 0.5) {
            drawFrame(a, 1);
            if (t > 0) drawFrame(b, t);
        } else {
            drawFrame(b, 1);
            drawFrame(a, 1 - t);
        }
        ctx.globalAlpha = 1;

        const angle = Math.round(p * (360 / frames)) % 360;
        root.setAttribute('aria-valuenow', String(angle));
        root.setAttribute('aria-valuetext', `${angle} degrees, view ${Math.round(p) % frames + 1} of ${frames}`);
        onChange({ position: p, angle, frame: Math.round(p) % frames });
    }

    // Match the canvas to its displayed size × device pixel ratio, so it stays sharp.
    const resizeObserver = new ResizeObserver(([entry]) => {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.round(entry.contentRect.width * dpr);
        canvas.height = Math.round(entry.contentRect.height * dpr);
        draw();
    });

    // ---- Animation loop: only runs while something is moving -------------------------

    function requestTick() {
        if (!rafId) rafId = requestAnimationFrame(tick);
    }

    function tick(now) {
        rafId = 0;
        const dt = lastTick ? Math.min(now - lastTick, 64) : 16;
        lastTick = now;
        let moving = false;

        if (tween) {
            const t = Math.min(1, (now - tween.start) / tween.duration);
            position = tween.from + (tween.to - tween.from) * tween.easing(t);
            if (t < 1) moving = true;
            else tween = null;
        } else if (!dragging && Math.abs(velocity) > 0.0004) {
            // Glide after release, slowing down smoothly. A glide travels about
            // velocity × 300 ms in total, so the fastest flick coasts ~2.4 views.
            position += velocity * dt;
            velocity *= Math.exp(-dt / 300);
            moving = true;
        } else if (!dragging && velocity !== 0) {
            velocity = 0;
            snap();
            moving = true;
        }

        draw();
        if (moving || dragging) requestTick();
        else lastTick = 0;
    }

    function animateTo(target, duration, easing = ease.outCubic) {
        if (reducedMotion.matches) {
            position = target;
            tween = null;
            draw();

            return;
        }
        tween = { from: position, to: target, start: performance.now(), duration, easing };
        requestTick();
    }

    // Come to rest on a whole frame, never halfway through a blend.
    function snap() {
        animateTo(Math.round(position), 320);
    }

    // ---- Dragging (mouse, pen and touch via Pointer Events) --------------------------

    // Pixels of drag per frame: one full turn is a drag of about the viewer's width.
    const pixelsPerFrame = () => (root.clientWidth * 1.1) / frames;

    function onPointerDown(event) {
        if (!ready || (event.pointerType === 'mouse' && event.button !== 0)) return;

        dragging = { pointerId: event.pointerId, lastX: event.clientX, lastTime: event.timeStamp };
        tween = null;
        velocity = 0;
        root.setPointerCapture(event.pointerId);
        root.dataset.dragging = '';
        interacted();
        requestTick();
    }

    function onPointerMove(event) {
        if (!dragging || event.pointerId !== dragging.pointerId) return;

        const dx = event.clientX - dragging.lastX;
        const dt = Math.max(1, event.timeStamp - dragging.lastTime);
        // Dragging left turns the shop the same way the hand moves.
        const deltaFrames = -dx / pixelsPerFrame();

        position += deltaFrames;
        // Smoothed so one jittery event doesn't decide the glide; capped so a flick can't spin wildly.
        velocity = Math.max(-0.008, Math.min(0.008, velocity * 0.5 + (deltaFrames / dt) * 0.5));
        dragging.lastX = event.clientX;
        dragging.lastTime = event.timeStamp;
    }

    function onPointerUp(event) {
        if (!dragging || event.pointerId !== dragging.pointerId) return;

        // A pause before letting go means "stop here", not "glide".
        if (event.timeStamp - dragging.lastTime > 80) velocity = 0;
        dragging = null;
        delete root.dataset.dragging;

        if (reducedMotion.matches || velocity === 0) {
            velocity = 0;
            snap();
        }
        requestTick();
    }

    // ---- Keyboard: it's a slider for assistive technology ----------------------------

    function onKeydown(event) {
        const step = { ArrowRight: 1, ArrowUp: 1, ArrowLeft: -1, ArrowDown: -1 }[event.key];
        if (step === undefined && event.key !== 'Home') return;

        event.preventDefault();
        interacted();
        velocity = 0;
        const base = tween ? tween.to : Math.round(position);
        animateTo(event.key === 'Home' ? Math.round(position / frames) * frames : base + step, 420);
    }

    // ---- Autoplay: a slow turn when idle, to show the model can be rotated -----------

    function interacted() {
        resumeAt = performance.now() + 4000;
        root.dataset.touched = '';
    }

    function autoplayStep() {
        const idle = !dragging && !tween && Math.abs(velocity) < 0.0004;
        if (autoplayEnabled && idle && !hovered && !focused && visible && !document.hidden && performance.now() > resumeAt) {
            animateTo(Math.round(position) + 1, 1100, ease.inOutCubic);
        }
    }

    // Only spin while the viewer is on screen.
    const intersectionObserver = new IntersectionObserver(([entry]) => (visible = entry.isIntersecting));

    const onEnter = () => (hovered = true);
    const onLeave = () => (hovered = false);
    const onFocus = () => (focused = true);
    const onBlur = () => (focused = false);

    // ---- Set up ----------------------------------------------------------------------

    sheet.src = root.dataset.src;
    sheet
        .decode()
        .then(() => {
            frameWidth = sheet.naturalWidth / columns;
            frameHeight = sheet.naturalHeight / rows;
            ready = true;

            // Becomes an interactive slider only once it can actually turn.
            root.setAttribute('role', 'slider');
            root.setAttribute('tabindex', '0');
            root.setAttribute('aria-label', 'Shop model. Drag, or use the arrow keys, to rotate it.');
            root.setAttribute('aria-valuemin', '0');
            root.setAttribute('aria-valuemax', '359');
            root.dataset.ready = '';
            draw();
        })
        .catch(() => {
            // The sheet failed to load: the poster image simply stays.
        });

    resizeObserver.observe(canvas);
    intersectionObserver.observe(root);
    root.addEventListener('pointerdown', onPointerDown);
    root.addEventListener('pointermove', onPointerMove);
    root.addEventListener('pointerup', onPointerUp);
    root.addEventListener('pointercancel', onPointerUp);
    root.addEventListener('keydown', onKeydown);
    root.addEventListener('pointerenter', onEnter);
    root.addEventListener('pointerleave', onLeave);
    root.addEventListener('focus', onFocus);
    root.addEventListener('blur', onBlur);

    return {
        /** Turn one frame every few seconds while nobody is interacting. */
        startAutoplay() {
            if (reducedMotion.matches) return;
            autoplayEnabled = true;
            window.clearInterval(autoplayTimer);
            autoplayTimer = window.setInterval(autoplayStep, 2800);
        },

        stopAutoplay() {
            autoplayEnabled = false;
            window.clearInterval(autoplayTimer);
        },

        destroy() {
            this.stopAutoplay();
            cancelAnimationFrame(rafId);
            resizeObserver.disconnect();
            intersectionObserver.disconnect();
            root.removeEventListener('pointerdown', onPointerDown);
            root.removeEventListener('pointermove', onPointerMove);
            root.removeEventListener('pointerup', onPointerUp);
            root.removeEventListener('pointercancel', onPointerUp);
            root.removeEventListener('keydown', onKeydown);
            root.removeEventListener('pointerenter', onEnter);
            root.removeEventListener('pointerleave', onLeave);
            root.removeEventListener('focus', onFocus);
            root.removeEventListener('blur', onBlur);
        },
    };
}
