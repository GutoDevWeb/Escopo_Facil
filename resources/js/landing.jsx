import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { animate, motion, MotionConfig, useMotionValueEvent, useScroll, useSpring, useTransform, useReducedMotion } from 'motion/react';

// The landing explicitly opts into its full animation experience.
function useSiteReducedMotion() {
    const prefersReduced = useReducedMotion();
    return prefersReduced && document.documentElement.dataset.motion !== 'full';
}

// Parameters read from the published reference, rather than guessed durations.
const dashboardSpring = { type: 'spring', stiffness: 400, damping: 100, mass: 1, restDelta: .0001 };
const imageSpring = { type: 'spring', stiffness: 150, damping: 37, mass: 1, restDelta: .0001 };
const slideSpring = { type: 'spring', bounce: 0, duration: .6 };

function ScrollClouds({ selector, offset }) {
    const reduced = useSiteReducedMotion();
    const section = useRef(document.querySelector(selector));
    const { scrollYProgress } = useScroll({ target: section, offset });
    const position = useSpring(scrollYProgress, imageSpring);

    function paint(value) {
        // Each cloud exits through its own edge and retraces its path on scroll up.
        // Percentages keep the travel proportional to its responsive image size.
        const progress = reduced ? 0 : Math.max(0, Math.min(1, value / .75));
        const travel = window.matchMedia('(max-width: 809px)').matches ? 45 : 110;
        section.current.querySelectorAll(':scope > .cloud').forEach(cloud => {
            const direction = cloud.classList.contains('left') ? -1 : 1;
            cloud.style.transform = `translate3d(${direction * progress * travel}%, ${-16 * progress}px, 0)`;
        });
    }

    useMotionValueEvent(position, 'change', paint);
    useEffect(() => {
        paint(position.get());
        return () => section.current.querySelectorAll(':scope > .cloud').forEach(cloud => cloud.style.removeProperty('transform'));
    }, []);

    return null;
}

function HeroWords({ lines, delay = 0 }) {
    const reduced = useSiteReducedMotion();
    let index = 0;
    return lines.map((line, lineIndex) => <React.Fragment key={lineIndex}>
        {lineIndex > 0 && <br />}
        {line.trim().split(/\s+/).map((word, wordIndex) => {
            const order = index++;
            return <React.Fragment key={wordIndex}>
                {wordIndex > 0 && ' '}
                <motion.span className="hero-word" initial={reduced ? false : { opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={reduced ? { duration: 0 } : { duration: .6, ease: [.22, 1, .36, 1], delay: delay + order * .035 }}>{word}</motion.span>
            </React.Fragment>;
        })}
    </React.Fragment>);
}

// A single observer and entrance pattern for all content blocks. Re-arm only
// after a block leaves the viewport; scrolling within a visible block never fades it.
function SectionReveals() {
    const reduced = useSiteReducedMotion();
    useEffect(() => {
        const elements = [...document.querySelectorAll('.feature-visual, .feature-copy, .resources-section > .eyebrow, .resources-section > h2, .resource-card, .resource-highlights > article, .benefits > .eyebrow, .benefits > h2, .mobile-scene, .pricing > .eyebrow, .pricing > h2, .plans > .plan, .contact-section > .eyebrow, .contact-section > h2, .contact-description, .contact-form, .contact-support-copy, .contact-support-visual, .site-footer-inner')];
        if (reduced) return;
        const revealFocus = event => event.currentTarget.classList.add('is-visible');
        const observer = new IntersectionObserver(entries => entries.forEach(entry => {
            const element = entry.target;
            if (entry.isIntersecting || element.contains(document.activeElement)) element.classList.add('is-visible');
            else if (entry.boundingClientRect.bottom <= 0 || entry.boundingClientRect.top >= innerHeight) element.classList.remove('is-visible');
        }), { threshold: 0, rootMargin: '0px 0px -24px 0px' });
        elements.forEach(element => {
            element.classList.add('motion-reveal');
            if (element.getBoundingClientRect().top < innerHeight && element.getBoundingClientRect().bottom > 0) element.classList.add('is-visible');
            observer.observe(element);
            element.addEventListener('focusin', revealFocus);
        });
        return () => {
            observer.disconnect();
            elements.forEach(element => {
                element.classList.remove('motion-reveal', 'is-visible');
                element.removeEventListener('focusin', revealFocus);
            });
        };
    }, [reduced]);
    return null;
}

function LandingMotion({ host, mobileImage, webImage }) {
    const reduced = useSiteReducedMotion();
    const [device, setDevice] = useState('mobile');
    const [compact, setCompact] = useState(() => window.matchMedia('(max-width: 809px)').matches);
    const dashboardRef = useRef(document.querySelector('.dashboard-stage'));
    const deviceRef = useRef(host);
    const { scrollY } = useScroll();

    // Start when the top meets the viewport bottom; finish when its bottom does.
    // At 1440 × 900 this reproduces ~.892 scale at y=0 and full scale at y≈380.
    // On a phone the whole image may already fit at page load. Keep the
    // animation range open while it crosses the viewport instead of ending immediately.
    const { scrollYProgress: dashboardProgress } = useScroll({ target: dashboardRef, offset: compact ? ['start 85%', 'end 15%'] : ['start end', 'end end'] });
    const { scrollYProgress: deviceProgress } = useScroll({ target: deviceRef, offset: ['start end', 'end end'] });
    const dashboardPosition = useSpring(dashboardProgress, dashboardSpring);
    const imagePosition = useSpring(deviceProgress, imageSpring);
    const imageScale = useTransform(imagePosition, [0, 1], [1.3, 1]);

    function paintDashboard(value) {
        const element = dashboardRef.current.querySelector('.workspace');
        if (reduced) { element.style.removeProperty('transform'); return; }
        const p = Math.max(0, Math.min(1, value));
        if (compact) {
            element.style.transform = `scale(${.92 + .08 * p})`;
            return;
        }
        const scale = .8 + .2 * p;
        element.style.transform = `perspective(1000px) translateY(${-80 * (1 - p)}px) scale(${scale}) rotateX(${20 * (1 - p)}deg)`;
    }
    useMotionValueEvent(dashboardPosition, 'change', paintDashboard);
    useMotionValueEvent(scrollY, 'change', value => {
        document.querySelector('header').classList.toggle('is-scrolled', value > 24);
    });

    useEffect(() => {
        document.documentElement.classList.add('react-motion');
        const media = window.matchMedia('(max-width: 809px)');
        const onResize = () => setCompact(media.matches);
        media.addEventListener('change', onResize);
        const header = document.querySelector('header');
        header.classList.toggle('is-scrolled', window.scrollY > 24);
        const actions = document.querySelector('.hero-copy .actions');
        const actionAnimation = reduced ? null : animate(actions, { opacity: [0, 1] }, { duration: .6, delay: .25, ease: [.22, 1, .36, 1] });
        return () => {
            actionAnimation?.stop();
            actions.style.removeProperty('opacity');
            document.documentElement.classList.remove('react-motion');
            media.removeEventListener('change', onResize);
        };
    }, [reduced]);

    useEffect(() => {
        paintDashboard(dashboardPosition.get());
    }, [reduced, compact]);

    const transition = reduced ? { duration: 0 } : slideSpring;
    return <>
        <SectionReveals />
        <ScrollClouds selector=".hero" offset={['start start', 'center start']} />
        <ScrollClouds selector=".pricing" offset={['start center', 'center start']} />
        <motion.div className="device-images" style={{ scale: reduced ? 1 : imageScale }}>
            <motion.img className="mobile-photo device-photo" src={mobileImage} alt="Escopo Fácil no celular" width="1417" height="941" initial={false} animate={{ x: device === 'mobile' ? '0%' : '-100%' }} transition={transition} aria-hidden={device !== 'mobile'} />
            <motion.img className="web-photo device-photo" src={webImage} alt="Escopo Fácil no tablet" width="1672" height="941" initial={false} animate={{ x: device === 'web' ? '0%' : '100%' }} transition={transition} aria-hidden={device !== 'web'} />
        </motion.div>
        <div className="web-cta device-switch" role="group" aria-label="Visualização da plataforma">
            {[['mobile', 'App Mobile'], ['web', 'Versão Web']].map(([value, label]) => <button key={value} type="button" data-device-option={value} aria-pressed={device === value} onClick={() => setDevice(value)} onFocus={() => setDevice(value)} onPointerEnter={event => { if (event.pointerType === 'mouse') setDevice(value); }}>
                {device === value && <motion.span className="device-active-pill" layoutId="device-active-pill" transition={transition} style={{ background: value === 'mobile' ? '#8b0836' : '#292625' }} />}
                <span className="device-option-label">{label}</span>
            </button>)}
        </div>
    </>;
}

const host = document.querySelector('.mobile-scene');
if (host) {
    const mobileImage = host.querySelector('.mobile-photo').src;
    const webImage = host.querySelector('.web-photo').src;
    document.documentElement.classList.add('react-motion');
    for (const [selector, delay] of [['.hero-copy h1', 0], ['.hero-copy > p', .1]]) {
        const element = document.querySelector(selector);
        const lines = [''];
        element.childNodes.forEach(node => {
            if (node.nodeName === 'BR') lines.push('');
            else lines[lines.length - 1] += node.textContent;
        });
        createRoot(element).render(<HeroWords lines={lines} delay={delay} />);
    }
    createRoot(host).render(<MotionConfig reducedMotion={document.documentElement.dataset.motion === 'full' ? 'never' : 'user'}><LandingMotion host={host} mobileImage={mobileImage} webImage={webImage} /></MotionConfig>);
}
