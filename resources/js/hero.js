/**
 * Home page hero: entrance choreography, magnetic CTA, pointer parallax and
 * the Stock / Team / Audit preview tabs.
 *
 * Rules this file follows:
 * - Only transform, opacity and clip-path are animated (compositor-only).
 * - Pointer handlers only feed gsap.quickTo() setters: no new tweens per
 *   mousemove, and no layout reads per frame (rects are cached).
 * - gsap.matchMedia() skips motion under prefers-reduced-motion and only
 *   attaches pointer listeners on devices with a fine pointer and hover.
 * - Everything runs inside gsap.context(), so it can be reverted cleanly.
 */
import { gsap } from 'gsap';

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

let context = null;

function init() {
    const hero = document.querySelector('[data-hero]');
    if (!hero) return;

    context = gsap.context(() => {
        const tabs = setUpTabs(hero);
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

                const stopAutoplay = conditions.motion ? tabs.startAutoplay() : () => {};
                const stopPointer = conditions.motion && conditions.finePointer ? attachPointerEffects(hero) : () => {};

                // Runs when a media query stops matching (or on revert).
                return () => {
                    stopAutoplay();
                    stopPointer();
                };
            },
        );

        return () => {
            tabs.destroy();
            mm.revert();
        };
    }, hero);
}

/**
 * Entrance: masked headline lines slide up, the copy rises in a stagger and
 * the preview settles from a slight zoom while a clip-path wipe opens it.
 */
function playEntrance(hero) {
    const headline = hero.querySelector('h1[data-reveal]');
    const lines = hero.querySelectorAll('[data-hero-line]');
    const fades = hero.querySelectorAll('[data-reveal="fade"]');
    const visual = hero.querySelector('[data-hero-visual]');
    const firstCards = hero.querySelectorAll('[data-panel]:not([hidden]) [data-card]');

    gsap.set(headline, { autoAlpha: 1 });

    gsap.timeline({ defaults: { ease: 'expo.out' } })
        .from(lines, { yPercent: 110, duration: 1.3, stagger: 0.12 }, 0.1)
        .fromTo(fades, { autoAlpha: 0, y: 16 }, { autoAlpha: 1, y: 0, duration: 1, ease: 'power4.out', stagger: 0.06 }, 0.3)
        .fromTo(
            visual,
            { autoAlpha: 1, scale: 1.06, clipPath: 'inset(10% 10% 10% 10% round 20px)' },
            { scale: 1, clipPath: 'inset(0% 0% 0% 0% round 20px)', duration: 1.5 },
            0.15,
        )
        .fromTo(firstCards, { autoAlpha: 0, y: 18 }, { autoAlpha: 1, y: 0, duration: 1, stagger: 0.1 }, 0.8)
        // Drop the clip-path once open, so the tab bar under the frame is never clipped.
        .set(visual, { clearProps: 'clipPath' });
}

/**
 * The Stock / Team / Audit tabs: ARIA tablist with arrow-key navigation, a
 * sliding indicator and a cross-fade between the card sets. Optional
 * autoplay pauses on hover or focus and stops once the visitor picks a tab.
 */
function setUpTabs(hero) {
    const tablist = hero.querySelector('[role="tablist"]');
    const tabs = [...hero.querySelectorAll('[role="tab"]')];
    const panels = tabs.map((tab) => document.getElementById(tab.getAttribute('aria-controls')));
    const indicator = hero.querySelector('[data-tab-indicator]');
    const allCards = hero.querySelectorAll('[data-card]');

    let current = 0;
    let timer = null;
    let paused = false;
    let userChose = false;

    function select(index, { focus = false } = {}) {
        if (index === current) return;

        const instant = reducedMotion.matches;
        const outgoing = panels[current];
        const incoming = panels[index];
        current = index;

        tabs.forEach((tab, i) => {
            tab.setAttribute('aria-selected', String(i === index));
            tab.tabIndex = i === index ? 0 : -1;
        });
        if (focus) tabs[index].focus();

        gsap.to(indicator, { xPercent: 100 * index, duration: instant ? 0 : 0.7, ease: 'expo.out', overwrite: true });

        // A quick click sequence shouldn't leave half-finished fades behind.
        gsap.killTweensOf(allCards);
        panels.forEach((panel) => {
            if (panel !== outgoing && panel !== incoming) panel.hidden = true;
        });

        incoming.hidden = false;
        const incomingCards = incoming.querySelectorAll('[data-card]');

        if (instant) {
            outgoing.hidden = true;
            gsap.set(incomingCards, { autoAlpha: 1, y: 0 });
            return;
        }

        gsap.to(outgoing.querySelectorAll('[data-card]'), {
            autoAlpha: 0,
            y: -12,
            duration: 0.35,
            ease: 'power2.in',
            stagger: 0.04,
            onComplete: () => {
                // Only hide it if the visitor hasn't switched back meanwhile.
                if (panels[current] !== outgoing) outgoing.hidden = true;
            },
        });
        gsap.fromTo(incomingCards, { autoAlpha: 0, y: 18 }, { autoAlpha: 1, y: 0, duration: 0.9, ease: 'expo.out', stagger: 0.08, delay: 0.2 });
    }

    function onClick(event) {
        const tab = event.target.closest('[role="tab"]');
        if (!tab) return;
        userChose = true;
        select(tabs.indexOf(tab));
    }

    function onKeydown(event) {
        const keys = { ArrowRight: current + 1, ArrowLeft: current - 1, Home: 0, End: tabs.length - 1 };
        if (!(event.key in keys)) return;
        event.preventDefault();
        userChose = true;
        select((keys[event.key] + tabs.length) % tabs.length, { focus: true });
    }

    const pause = () => (paused = true);
    const resume = () => (paused = false);

    tablist.addEventListener('click', onClick);
    tablist.addEventListener('keydown', onKeydown);
    hero.addEventListener('pointerenter', pause);
    hero.addEventListener('pointerleave', resume);
    hero.addEventListener('focusin', pause);
    hero.addEventListener('focusout', resume);

    return {
        // Cycle through the tabs every 6s until the visitor takes over.
        startAutoplay() {
            timer = window.setInterval(() => {
                if (!paused && !userChose && !document.hidden) select((current + 1) % tabs.length);
            }, 6000);

            return () => window.clearInterval(timer);
        },

        destroy() {
            window.clearInterval(timer);
            tablist.removeEventListener('click', onClick);
            tablist.removeEventListener('keydown', onKeydown);
            hero.removeEventListener('pointerenter', pause);
            hero.removeEventListener('pointerleave', resume);
            hero.removeEventListener('focusin', pause);
            hero.removeEventListener('focusout', resume);
        },
    };
}

/**
 * Magnetic CTA and parallax tilt. Only attached on fine-pointer devices with
 * motion allowed. Returns a cleanup function that removes every listener.
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

function parallax(hero) {
    const visual = hero.querySelector('[data-hero-visual]');
    const frame = hero.querySelector('[data-tilt]');
    if (!visual || !frame) return () => {};

    gsap.set(frame, { transformPerspective: 1100 });

    const tiltEase = { duration: 0.9, ease: 'power3.out' };
    const rotateX = gsap.quickTo(frame, 'rotationX', tiltEase);
    const rotateY = gsap.quickTo(frame, 'rotationY', tiltEase);

    // Each layer moves by its data-depth: the shop barely, the cards more.
    const layers = [...visual.querySelectorAll('[data-depth]')].map((layer) => ({
        depth: parseFloat(layer.dataset.depth) || 0,
        x: gsap.quickTo(layer, 'x', tiltEase),
        y: gsap.quickTo(layer, 'y', tiltEase),
    }));

    let box = null;

    const onEnter = () => (box = measure(visual));
    const onMove = (event) => {
        box ??= measure(visual);
        // -0.5 .. 0.5 across the visual, in both directions.
        const nx = (event.pageX - box.left) / box.width - 0.5;
        const ny = (event.pageY - box.top) / box.height - 0.5;

        rotateY(nx * 7);
        rotateX(ny * -7);
        layers.forEach((layer) => {
            layer.x(nx * layer.depth * 26);
            layer.y(ny * layer.depth * 26);
        });
    };
    const onLeave = () => {
        rotateX(0);
        rotateY(0);
        layers.forEach((layer) => {
            layer.x(0);
            layer.y(0);
        });
    };
    const onResize = () => (box = null);

    visual.addEventListener('pointerenter', onEnter);
    visual.addEventListener('pointermove', onMove);
    visual.addEventListener('pointerleave', onLeave);
    window.addEventListener('resize', onResize);

    return () => {
        visual.removeEventListener('pointerenter', onEnter);
        visual.removeEventListener('pointermove', onMove);
        visual.removeEventListener('pointerleave', onLeave);
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
