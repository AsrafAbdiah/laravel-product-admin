/**
 * Home page hero: entrance choreography, magnetic CTA, and gentle pointer
 * parallax on the shop model and the cards floating around it.
 *
 * Rules this file follows:
 * - Only transform, opacity and clip-path are animated (compositor-only).
 * - Pointer handlers only feed gsap.quickTo() setters: no new tweens per
 *   pointermove, and no layout reads per frame (positions are cached).
 * - gsap.matchMedia() skips motion under prefers-reduced-motion and only
 *   attaches hover effects on devices with a fine pointer.
 * - Everything is reverted cleanly through gsap.context().
 */
import { gsap } from 'gsap';

let context = null;

function init() {
    const hero = document.querySelector('[data-hero]');
    if (!hero) return;

    context = gsap.context(() => {
        const mm = gsap.matchMedia();

        mm.add(
            {
                motion: '(prefers-reduced-motion: no-preference)',
                finePointer: '(hover: hover) and (pointer: fine)',
            },
            ({ conditions }) => {
                if (conditions.motion) {
                    playEntrance(hero);
                } else {
                    // Reduced motion: show everything straight away.
                    gsap.set(hero.querySelectorAll('[data-reveal]'), { autoAlpha: 1 });
                }

                // Runs when a media query stops matching (or on revert).
                return conditions.motion && conditions.finePointer ? attachPointerEffects(hero) : undefined;
            },
        );

        return () => mm.revert();
    }, hero);
}

/**
 * Entrance: masked headline lines slide up, the copy rises in a stagger, the
 * model settles from a slight zoom while a clip-path wipe opens it, and the
 * cards follow.
 */
function playEntrance(hero) {
    const headline = hero.querySelector('h1[data-reveal]');
    const lines = hero.querySelectorAll('[data-hero-line]');
    const fades = hero.querySelectorAll('[data-reveal="fade"]');
    const visual = hero.querySelector('[data-hero-visual]');
    const cards = hero.querySelectorAll('[data-reveal="card"]');

    gsap.set(headline, { autoAlpha: 1 });

    return gsap
        .timeline({ defaults: { ease: 'expo.out' } })
        .from(lines, { yPercent: 110, duration: 1.3, stagger: 0.12 }, 0.1)
        .fromTo(fades, { autoAlpha: 0, y: 16 }, { autoAlpha: 1, y: 0, duration: 1, ease: 'power4.out', stagger: 0.06 }, 0.3)
        .fromTo(
            visual,
            { autoAlpha: 1, scale: 1.06, clipPath: 'inset(12% 12% 12% 12% round 24px)' },
            { scale: 1, clipPath: 'inset(0% 0% 0% 0% round 0px)', duration: 1.5 },
            0.2,
        )
        .fromTo(cards, { autoAlpha: 0, y: 18 }, { autoAlpha: 1, y: 0, duration: 1, stagger: 0.1 }, 0.75)
        // The wipe is done; drop the clip-path so nothing near the edge is ever clipped.
        .set(visual, { clearProps: 'clipPath' });
}

/**
 * Magnetic CTA and pointer parallax on the cards. Only attached on
 * fine-pointer devices with motion allowed. Returns a cleanup function.
 */
function attachPointerEffects(hero) {
    const cleanups = [magnetic(hero), parallax(hero)];

    return () => cleanups.forEach((cleanup) => cleanup());
}

/**
 * Page-relative box of an element, read once and reused. Page (not viewport)
 * coordinates stay valid while the page scrolls, so pointer handlers can use
 * event.pageX / pageY without reading layout again.
 */
function measure(element) {
    const rect = element.getBoundingClientRect();

    return {
        left: rect.left + window.scrollX,
        top: rect.top + window.scrollY,
        width: rect.width,
        height: rect.height,
    };
}

function magnetic(hero) {
    const zone = hero.querySelector('[data-magnetic-zone]');
    const button = hero.querySelector('[data-magnetic]');
    const label = hero.querySelector('[data-magnetic-label]');
    if (!zone || !button) return () => {};

    const settle = { duration: 0.6, ease: 'power3.out' };
    const buttonX = gsap.quickTo(button, 'x', settle);
    const buttonY = gsap.quickTo(button, 'y', settle);
    const labelX = gsap.quickTo(label, 'x', settle);
    const labelY = gsap.quickTo(label, 'y', settle);

    let box = null;

    const onEnter = () => (box = measure(zone));
    const onMove = (event) => {
        box ??= measure(zone);
        // Distance from the centre of the zone, so the pull stays within its small radius.
        const dx = event.pageX - (box.left + box.width / 2);
        const dy = event.pageY - (box.top + box.height / 2);
        buttonX(dx * 0.28);
        buttonY(dy * 0.38);
        labelX(dx * 0.12); // the label drifts a little further, for a sense of depth
        labelY(dy * 0.16);
    };
    const onLeave = () => {
        buttonX(0);
        buttonY(0);
        labelX(0);
        labelY(0);
    };
    const onResize = () => (box = null);

    zone.addEventListener('pointerenter', onEnter);
    zone.addEventListener('pointermove', onMove);
    zone.addEventListener('pointerleave', onLeave);
    window.addEventListener('resize', onResize);

    return () => {
        zone.removeEventListener('pointerenter', onEnter);
        zone.removeEventListener('pointermove', onMove);
        zone.removeEventListener('pointerleave', onLeave);
        window.removeEventListener('resize', onResize);
        gsap.set([button, label], { x: 0, y: 0 });
    };
}

/**
 * The cards (and, a little, the model) shift with the pointer by their
 * data-depth, so the stage reads as layered.
 */
function parallax(hero) {
    const stage = hero.querySelector('[data-hero-stage]');
    if (!stage) return () => {};

    const glide = { duration: 0.9, ease: 'power3.out' };
    const layers = [...stage.querySelectorAll('[data-depth]')].map((layer) => ({
        depth: parseFloat(layer.dataset.depth) || 0,
        x: gsap.quickTo(layer, 'x', glide),
        y: gsap.quickTo(layer, 'y', glide),
    }));

    let box = null;

    const onEnter = () => (box = measure(stage));
    const onMove = (event) => {
        box ??= measure(stage);
        // -0.5 .. 0.5 across the stage, in both directions.
        const nx = (event.pageX - box.left) / box.width - 0.5;
        const ny = (event.pageY - box.top) / box.height - 0.5;
        layers.forEach((layer) => {
            layer.x(nx * layer.depth * 28);
            layer.y(ny * layer.depth * 28);
        });
    };
    const onLeave = () => {
        layers.forEach((layer) => {
            layer.x(0);
            layer.y(0);
        });
    };
    const onResize = () => (box = null);

    stage.addEventListener('pointerenter', onEnter);
    stage.addEventListener('pointermove', onMove);
    stage.addEventListener('pointerleave', onLeave);
    window.addEventListener('resize', onResize);

    return () => {
        stage.removeEventListener('pointerenter', onEnter);
        stage.removeEventListener('pointermove', onMove);
        stage.removeEventListener('pointerleave', onLeave);
        window.removeEventListener('resize', onResize);
    };
}

// Revert everything when the page is put away; set it up again if the browser
// restores the page from its back/forward cache.
window.addEventListener('pagehide', () => context?.revert());
window.addEventListener('pageshow', (event) => {
    if (event.persisted) init();
});

init();
