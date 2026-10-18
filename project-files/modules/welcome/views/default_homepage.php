<!--
    Under-construction homepage, in the Click Me Studio neon style.
    Self-contained styling and inline SVG, so it works regardless of the
    project's theme and needs no image files.

    Optional constants (define in config/site_owner.php):
      OUR_EMAIL_ADDRESS - contact email shown on the page
      OUR_NAME          - company/site name; its last word gets the gradient
    Both fall back to placeholder values if not defined.
-->

<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    /* Scoped to the construction page via the .uc-page wrapper */
    .uc-page {
        --uc-bg: #04050d;
        --uc-text: #f4f6ff;
        --uc-dim: rgba(196, 208, 255, 0.78);
        --uc-cyan: #46e6ff;
        --uc-blue: #5b7bff;
        --uc-magenta: #e04cff;
        --uc-grad: linear-gradient(90deg, var(--uc-cyan), var(--uc-blue) 50%, var(--uc-magenta));

        position: fixed;
        inset: 0;
        z-index: 1000;
        overflow: hidden;
        background:
            radial-gradient(ellipse 60% 30% at 50% 72%, rgba(91, 123, 255, 0.28), transparent 70%),
            radial-gradient(ellipse 40% 40% at 88% 70%, rgba(224, 76, 255, 0.22), transparent 70%),
            radial-gradient(ellipse 50% 50% at 50% 0%, rgba(30, 40, 90, 0.55), transparent 70%),
            var(--uc-bg);
        color: var(--uc-text);
        font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    .uc-page *, .uc-page *::before, .uc-page *::after { box-sizing: border-box; }

    .uc-layer { position: absolute; pointer-events: none; }

    /* Faint grid and stars */
    .uc-grid {
        inset: 0;
        background-image:
            linear-gradient(rgba(120, 150, 255, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(120, 150, 255, 0.05) 1px, transparent 1px);
        background-size: 25vw 25vh;
        mask-image: linear-gradient(#000 0%, transparent 70%);
        -webkit-mask-image: linear-gradient(#000 0%, transparent 70%);
    }

    .uc-stars {
        inset: 0;
        background-image:
            radial-gradient(1px 1px at 12% 22%, #fff, transparent),
            radial-gradient(1px 1px at 34% 8%, #cfe, transparent),
            radial-gradient(1px 1px at 62% 14%, #fff, transparent),
            radial-gradient(1.5px 1.5px at 78% 30%, #dcf, transparent),
            radial-gradient(1px 1px at 90% 10%, #fff, transparent),
            radial-gradient(1px 1px at 46% 36%, #bdf, transparent),
            radial-gradient(1px 1px at 22% 48%, #fff, transparent);
        animation: uc-twinkle 5s ease-in-out infinite alternate;
    }

    .uc-cross {
        width: 22px;
        height: 22px;
        background:
            linear-gradient(rgba(200, 215, 255, 0.5), rgba(200, 215, 255, 0.5)) center / 1px 100% no-repeat,
            linear-gradient(rgba(200, 215, 255, 0.5), rgba(200, 215, 255, 0.5)) center / 100% 1px no-repeat;
    }

    /* Neon arc on the right */
    .uc-arc {
        right: 0;
        top: 24vh;
        width: 34vw;
        height: 62vh;
        filter: drop-shadow(0 0 6px rgba(160, 90, 255, 0.9)) drop-shadow(0 0 22px rgba(224, 76, 255, 0.5));
    }

    /* Glass panels */
    .uc-glass {
        border: 1px solid rgba(120, 180, 255, 0.45);
        border-radius: 18px;
        background: linear-gradient(160deg, rgba(90, 150, 255, 0.2), rgba(40, 60, 160, 0.05));
        box-shadow: 0 0 40px rgba(60, 140, 255, 0.22), inset 0 0 30px rgba(120, 180, 255, 0.12);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
    }

    .uc-browser {
        left: -3vw;
        top: 20vh;
        width: min(27vw, 400px);
        height: 46vh;
        padding: 1.4rem;
        transform: perspective(900px) rotateY(32deg) rotateZ(2deg);
        transform-origin: left center;
        animation: uc-float 9s ease-in-out infinite;
    }

    .uc-browser__dots { display: flex; gap: 7px; margin-bottom: 1.1rem; }
    .uc-browser__dots i { width: 9px; height: 9px; border-radius: 50%; background: rgba(150, 200, 255, 0.75); }

    .uc-browser__row { display: flex; gap: 1rem; margin-bottom: 1.1rem; }

    .uc-browser__img {
        flex: 0 0 46%;
        aspect-ratio: 4 / 3;
        border: 1px solid rgba(140, 190, 255, 0.4);
        border-radius: 8px;
        background: linear-gradient(rgba(120, 160, 255, 0.18), rgba(60, 80, 180, 0.08));
    }

    .uc-browser__img svg { width: 100%; height: 100%; }

    .uc-browser__lines { flex: 1; display: grid; gap: 9px; align-content: start; padding-top: 6px; }

    .uc-line { height: 6px; border-radius: 4px; background: rgba(150, 190, 255, 0.4); }

    .uc-browser__code {
        position: absolute;
        right: 1.4rem;
        bottom: 1.4rem;
        padding: 0.45rem 0.6rem;
        border: 1px solid rgba(140, 190, 255, 0.55);
        border-radius: 8px;
        color: rgba(180, 210, 255, 0.9);
        font-size: 0.9rem;
        font-weight: 600;
    }

    .uc-card {
        right: 4vw;
        top: 41vh;
        width: min(14vw, 220px);
        padding: 1.5rem 1.2rem;
        transform: perspective(800px) rotateY(-26deg) rotateZ(-5deg);
        display: grid;
        gap: 0.85rem;
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: 0.3em;
        color: rgba(205, 220, 255, 0.85);
        text-transform: uppercase;
        animation: uc-float 11s ease-in-out -3s infinite;
    }

    /* Glossy orbs */
    .uc-orb {
        border-radius: 50%;
        background: radial-gradient(circle at 35% 30%, rgba(255, 255, 255, 0.35), rgba(40, 60, 140, 0.45) 28%, #05060f 70%);
        box-shadow:
            inset -8px -10px 22px rgba(210, 70, 255, 0.65),
            inset 5px 7px 16px rgba(70, 200, 255, 0.45),
            0 0 30px rgba(140, 80, 255, 0.35);
        animation: uc-float 8s ease-in-out infinite;
    }

    .uc-orb--lg { width: min(12vw, 180px); aspect-ratio: 1; left: 15vw; top: 15vh; }
    .uc-orb--sm { width: min(2.6vw, 40px); aspect-ratio: 1; left: 23vw; top: 31vh; animation-delay: -2s; }
    .uc-orb--md { width: min(3.6vw, 54px); aspect-ratio: 1; right: 18vw; top: 36vh; animation-delay: -5s; }

    /* Landscape: mountains, rocks and water */
    .uc-land {
        left: 0;
        bottom: 0;
        width: 100%;
        height: 58vh;
    }

    .uc-shimmer { animation: uc-shimmer 4s ease-in-out infinite alternate; }

    /* Glowing podium */
    .uc-podium {
        left: 50%;
        bottom: 4vh;
        width: min(56vw, 780px);
        height: min(8vh, 80px);
        transform: translateX(-50%);
    }

    .uc-podium__rim,
    .uc-podium__top,
    .uc-podium__glow { position: absolute; left: 0; right: 0; border-radius: 50%; }

    .uc-podium__glow {
        top: 30%;
        bottom: -40%;
        background: var(--uc-grad);
        filter: blur(26px);
        opacity: 0.55;
        animation: uc-glow 3.5s ease-in-out infinite alternate;
    }

    .uc-podium__rim { top: 8%; bottom: 0; background: var(--uc-grad); }

    .uc-podium__top {
        top: 0;
        bottom: 12%;
        background: radial-gradient(ellipse at 50% 30%, #2a2f55, #0b0d1e 70%);
        box-shadow: inset 0 2px 8px rgba(160, 180, 255, 0.25);
    }

    /* Corner word stacks */
    .uc-stack {
        position: absolute;
        z-index: 3;
        margin: 0;
        display: grid;
        gap: 0.55rem;
        font-size: clamp(0.62rem, 0.55rem + 0.25vw, 0.78rem);
        font-weight: 500;
        letter-spacing: 0.3em;
        text-transform: uppercase;
        color: var(--uc-dim);
    }

    .uc-stack::after {
        content: "";
        width: 26px;
        height: 2px;
        margin-top: 0.5rem;
        background: var(--uc-grad);
        box-shadow: 0 0 8px var(--uc-cyan);
    }

    .uc-stack--tl { left: 4vw; top: 6vh; }
    .uc-stack--tr { right: 4vw; top: 6vh; }
    .uc-stack--bl { left: 4vw; bottom: 26vh; }
    .uc-stack--br { right: 4vw; bottom: 8vh; text-align: right; justify-items: end; }

    /* Centre content */
    .uc-content {
        position: absolute;
        z-index: 4;
        top: 6vh;
        left: 50%;
        width: min(92vw, 900px);
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .uc-logo {
        width: min(13vh, 120px);
        height: min(13vh, 120px);
        filter: drop-shadow(0 0 6px rgba(70, 230, 255, 0.7)) drop-shadow(0 0 18px rgba(224, 76, 255, 0.45));
        opacity: 0;
        animation: uc-fade-up 0.8s ease forwards;
    }

    .uc-logo__spin { transform-origin: 60px 60px; animation: uc-spin 24s linear infinite; }

    .uc-brand {
        margin: 1.2vh 0 0;
        font-size: min(4.6vw, 7.5vh);
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1.05;
        text-shadow: 0 0 18px rgba(255, 255, 255, 0.25);
        opacity: 0;
        animation: uc-fade-up 0.8s ease 0.1s forwards;
    }

    .uc-grad {
        background: var(--uc-grad);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: 500;
    }

    .uc-tagline {
        margin: 1vh 0 0;
        font-size: clamp(0.65rem, 0.55rem + 0.35vw, 0.9rem);
        font-weight: 500;
        letter-spacing: 0.45em;
        text-transform: uppercase;
        color: var(--uc-dim);
        opacity: 0;
        animation: uc-fade-up 0.8s ease 0.2s forwards;
    }

    .uc-title {
        margin: 3.5vh 0 0;
        font-size: min(8.6vw, 13vh);
        font-weight: 700;
        line-height: 0.98;
        letter-spacing: -0.035em;
        opacity: 0;
        animation: uc-fade-up 0.9s ease 0.3s forwards;
    }

    .uc-title__top {
        display: block;
        text-shadow: 0 0 24px rgba(255, 255, 255, 0.35);
    }

    .uc-title__bottom {
        display: block;
        background: var(--uc-grad);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        filter: drop-shadow(0 0 16px rgba(120, 110, 255, 0.55));
    }

    .uc-lead {
        margin: 3vh 0 0;
        font-size: clamp(1rem, 0.8rem + 1vw, 1.75rem);
        font-weight: 500;
        opacity: 0;
        animation: uc-fade-up 0.8s ease 0.45s forwards;
    }

    .uc-sub {
        margin: 1.4vh 0 0;
        font-size: clamp(0.78rem, 0.68rem + 0.35vw, 1rem);
        letter-spacing: 0.32em;
        color: rgba(150, 200, 255, 0.85);
        opacity: 0;
        animation: uc-fade-up 0.8s ease 0.55s forwards;
    }

    .uc-pill {
        position: relative;
        margin-top: 3vh;
        display: inline-flex;
        align-items: center;
        gap: 1.6rem;
        padding: 1.05rem 2.6rem 1.05rem 1.6rem;
        border: 1px solid rgba(140, 170, 255, 0.4);
        border-radius: 999px;
        background: rgba(10, 14, 40, 0.55);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        box-shadow: 0 0 30px rgba(91, 123, 255, 0.18);
        font-size: clamp(0.66rem, 0.6rem + 0.25vw, 0.82rem);
        font-weight: 600;
        letter-spacing: 0.32em;
        text-transform: uppercase;
        overflow: hidden;
        opacity: 0;
        animation: uc-fade-up 0.8s ease 0.65s forwards;
    }

    .uc-pill svg { width: 22px; height: 22px; animation: uc-spin 6s linear infinite; }

    .uc-pill::after {
        content: "";
        position: absolute;
        left: 18%;
        bottom: 0;
        width: 30%;
        height: 2px;
        background: var(--uc-grad);
        box-shadow: 0 0 10px var(--uc-magenta);
        animation: uc-progress 3.2s ease-in-out infinite alternate;
    }

    .uc-mail {
        margin-top: 1.8vh;
        font-size: 0.82rem;
        letter-spacing: 0.12em;
        color: rgba(196, 208, 255, 0.7);
        text-decoration: none;
        opacity: 0;
        animation: uc-fade-up 0.8s ease 0.75s forwards;
        transition: color 0.2s ease;
    }

    .uc-mail:hover { color: var(--uc-cyan); }

    @keyframes uc-fade-up {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes uc-float {
        0%, 100% { translate: 0 0; }
        50%      { translate: 0 -12px; }
    }

    @keyframes uc-spin { to { transform: rotate(360deg); } }
    @keyframes uc-twinkle { from { opacity: 0.5; } to { opacity: 1; } }
    @keyframes uc-glow { from { opacity: 0.4; } to { opacity: 0.75; } }
    @keyframes uc-shimmer { from { opacity: 0.25; } to { opacity: 0.7; } }
    @keyframes uc-progress { from { left: 8%; } to { left: 62%; } }

    @media (max-width: 900px) {
        .uc-browser, .uc-card, .uc-stack--bl, .uc-stack--br, .uc-arc { display: none; }
        .uc-orb--lg { left: 4vw; top: 30vh; width: 70px; }
        .uc-orb--md { right: 6vw; top: 24vh; width: 36px; }
        .uc-orb--sm { display: none; }
    }

    @media (max-width: 560px) {
        .uc-stack { display: none; }
        .uc-content { top: 8vh; }
        .uc-brand { font-size: 2rem; }
        .uc-title { font-size: min(13vw, 11vh); }
        .uc-sub { letter-spacing: 0.18em; }
        .uc-pill { gap: 1rem; padding: 0.9rem 1.6rem 0.9rem 1.2rem; letter-spacing: 0.22em; }
        .uc-podium { width: 90vw; }
    }

    @media (prefers-reduced-motion: reduce) {
        .uc-page *, .uc-page *::before, .uc-page *::after { animation: none !important; opacity: 1 !important; }
    }
</style>

<?php
    // Pull site owner details from constants. These may be defined but empty
    // in a default setup, so fall back whenever the value is blank.
    $uc_email = (defined('OUR_EMAIL_ADDRESS') && OUR_EMAIL_ADDRESS !== '') ? OUR_EMAIL_ADDRESS : 'hello@example.com';
    $uc_name  = (defined('OUR_NAME') && OUR_NAME !== '') ? OUR_NAME : 'Click Me Studio';

    // The last word of the name gets the gradient, as in "Click Me Studio".
    $uc_words = explode(' ', trim($uc_name));
    $uc_last  = array_pop($uc_words);
    $uc_first = implode(' ', $uc_words);
?>

<div class="uc-page">
    <div class="uc-layer uc-grid"></div>
    <div class="uc-layer uc-stars"></div>
    <div class="uc-layer uc-cross" style="right: 17vw; top: 17vh;"></div>
    <div class="uc-layer uc-cross" style="left: 9vw; top: 52vh; opacity: 0.6;"></div>

    <svg class="uc-layer uc-arc" viewBox="0 0 500 700" preserveAspectRatio="xMaxYMin meet" aria-hidden="true">
        <defs>
            <linearGradient id="uc-arc-grad" x1="0" y1="1" x2="1" y2="0">
                <stop offset="0" stop-color="#ff7ad9" />
                <stop offset="0.45" stop-color="#b14cff" />
                <stop offset="1" stop-color="#6aa8ff" />
            </linearGradient>
        </defs>
        <path d="M 40 690 A 560 560 0 0 1 500 120" fill="none" stroke="url(#uc-arc-grad)" stroke-width="3" />
    </svg>

    <div class="uc-layer uc-orb uc-orb--lg"></div>
    <div class="uc-layer uc-orb uc-orb--sm"></div>
    <div class="uc-layer uc-orb uc-orb--md"></div>

    <div class="uc-layer uc-glass uc-browser" aria-hidden="true">
        <div class="uc-browser__dots"><i></i><i></i><i></i></div>
        <div class="uc-browser__row">
            <div class="uc-browser__img">
                <svg viewBox="0 0 100 75">
                    <circle cx="28" cy="22" r="6" fill="rgba(180,210,255,0.6)" />
                    <path d="M0 75 L30 40 L48 58 L66 30 L100 75 Z" fill="rgba(140,180,255,0.45)" />
                </svg>
            </div>
            <div class="uc-browser__lines">
                <div class="uc-line" style="width: 90%;"></div>
                <div class="uc-line" style="width: 75%;"></div>
                <div class="uc-line" style="width: 85%;"></div>
                <div class="uc-line" style="width: 60%;"></div>
            </div>
        </div>
        <div class="uc-browser__lines">
            <div class="uc-line" style="width: 70%;"></div>
            <div class="uc-line" style="width: 82%;"></div>
            <div class="uc-line" style="width: 64%;"></div>
        </div>
        <div class="uc-browser__code">&lt;/&gt;</div>
    </div>

    <div class="uc-layer uc-glass uc-card" aria-hidden="true">
        <span>Modern</span><span>Creative</span><span>Scalable</span><span>Human</span>
    </div>

    <svg class="uc-layer uc-land" viewBox="0 0 1440 520" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
        <defs>
            <radialGradient id="uc-sun" cx="0.5" cy="0.5" r="0.5">
                <stop offset="0" stop-color="#ffd2a8" stop-opacity="0.95" />
                <stop offset="0.25" stop-color="#ff7ad9" stop-opacity="0.55" />
                <stop offset="1" stop-color="#8a3dff" stop-opacity="0" />
            </radialGradient>
            <linearGradient id="uc-water" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#2a2f6e" />
                <stop offset="0.35" stop-color="#11143a" />
                <stop offset="1" stop-color="#04050d" />
            </linearGradient>
            <linearGradient id="uc-far" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#2b3170" />
                <stop offset="1" stop-color="#121538" />
            </linearGradient>
            <linearGradient id="uc-rock" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#151a3a" />
                <stop offset="1" stop-color="#05060f" />
            </linearGradient>
            <linearGradient id="uc-rim-l" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0" stop-color="#46e6ff" stop-opacity="0.2" />
                <stop offset="1" stop-color="#5b7bff" stop-opacity="0.9" />
            </linearGradient>
            <linearGradient id="uc-rim-r" x1="1" y1="0" x2="0" y2="0">
                <stop offset="0" stop-color="#e04cff" stop-opacity="0.35" />
                <stop offset="1" stop-color="#ff7ad9" stop-opacity="1" />
            </linearGradient>
            <filter id="uc-soft" x="-1" y="-1" width="3" height="3"><feGaussianBlur stdDeviation="10" /></filter>
        </defs>

        <!-- Sunset glow and far range -->
        <ellipse cx="1130" cy="372" rx="240" ry="110" fill="url(#uc-sun)" />
        <path d="M300 382 L380 338 L430 350 L520 292 L575 330 L640 356 L700 368 L760 374 L840 368 L900 354 L960 342 L1010 350 L1080 322 L1140 302 L1200 276 L1250 304 L1300 340 L1360 364 L1440 374 L1440 382 Z" fill="url(#uc-far)" />

        <!-- Water and reflections -->
        <rect x="0" y="380" width="1440" height="140" fill="url(#uc-water)" />
        <ellipse class="uc-shimmer" cx="1130" cy="412" rx="34" ry="44" fill="#ff9ad5" opacity="0.35" filter="url(#uc-soft)" />
        <g class="uc-shimmer" stroke="#9fb4ff" stroke-width="1" opacity="0.45">
            <line x1="560" y1="398" x2="880" y2="398" />
            <line x1="620" y1="412" x2="820" y2="412" />
            <line x1="1020" y1="404" x2="1240" y2="404" stroke="#ff9ad5" />
            <line x1="1060" y1="440" x2="1200" y2="440" stroke="#ff9ad5" />
        </g>

        <!-- Left rocks with blue rim light -->
        <path d="M0 250 L50 232 L95 256 L150 214 L215 250 L265 290 L330 330 L395 360 L455 388 L520 410 L575 432 L610 456 L600 478 L470 470 L320 498 L150 490 L0 520 Z" fill="url(#uc-rock)" />
        <path d="M0 250 L50 232 L95 256 L150 214 L215 250 L265 290 L330 330 L395 360 L455 388 L520 410 L575 432 L610 456" fill="none" stroke="url(#uc-rim-l)" stroke-width="2" />

        <!-- Right cliffs with magenta rim light -->
        <path d="M1440 150 L1405 168 L1375 222 L1342 232 L1305 292 L1272 326 L1240 358 L1205 388 L1175 410 L1150 440 L1185 470 L1300 492 L1440 486 Z" fill="url(#uc-rock)" />
        <path d="M1440 150 L1405 168 L1375 222 L1342 232 L1305 292 L1272 326 L1240 358 L1205 388 L1175 410 L1150 440" fill="none" stroke="url(#uc-rim-r)" stroke-width="2" />

        <!-- Foreground stones -->
        <path d="M0 470 L80 452 L170 466 L240 494 L260 520 L0 520 Z" fill="#05060f" />
        <path d="M1440 440 L1380 452 L1330 486 L1310 520 L1440 520 Z" fill="#05060f" />
        <path d="M1380 452 L1330 486" fill="none" stroke="#e04cff" stroke-opacity="0.6" stroke-width="1.5" />
    </svg>

    <div class="uc-layer uc-podium" aria-hidden="true">
        <div class="uc-podium__glow"></div>
        <div class="uc-podium__rim"></div>
        <div class="uc-podium__top"></div>
    </div>

    <p class="uc-stack uc-stack--tl"><span>Design</span><span>Develop</span><span>Create</span><span>Grow</span></p>
    <p class="uc-stack uc-stack--tr"><span>Ideas</span><span>Websites</span><span>Brands</span><span>That matter</span></p>
    <p class="uc-stack uc-stack--bl"><span>Pixels</span><span>Ideas</span><span>People</span><span>Possibilities</span></p>
    <p class="uc-stack uc-stack--br"><span>Same</span><span>Team</span><span>Bigger</span><span>Things</span></p>

    <main class="uc-content">
        <svg class="uc-logo" viewBox="0 0 120 120" fill="none" aria-hidden="true">
            <defs>
                <linearGradient id="uc-logo-grad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#46e6ff" />
                    <stop offset="0.5" stop-color="#5b7bff" />
                    <stop offset="1" stop-color="#e04cff" />
                </linearGradient>
            </defs>
            <g stroke="url(#uc-logo-grad)" stroke-width="5" stroke-linecap="round">
                <circle cx="60" cy="60" r="7" fill="url(#uc-logo-grad)" stroke="none" />
                <circle cx="60" cy="60" r="16" />
                <g class="uc-logo__spin">
                    <circle cx="60" cy="60" r="28" stroke-dasharray="30 14" />
                </g>
                <circle cx="60" cy="13" r="8" />
                <circle cx="107" cy="60" r="8" />
                <circle cx="60" cy="107" r="8" />
                <circle cx="13" cy="60" r="8" />
            </g>
        </svg>

        <p class="uc-brand"><?= out($uc_first) ?> <span class="uc-grad"><?= out($uc_last) ?></span></p>
        <p class="uc-tagline">Creative &bull; Digital &bull; Beyond</p>

        <h1 class="uc-title">
            <span class="uc-title__top">Under</span>
            <span class="uc-title__bottom">Construction</span>
        </h1>

        <p class="uc-lead">We&rsquo;re building something amazing.</p>
        <p class="uc-sub">Our new website is coming soon.</p>

        <div class="uc-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="3" />
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68 1.65 1.65 0 0 0 10 3.17V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
            </svg>
            Great things take time
        </div>

        <a class="uc-mail" href="mailto:<?= out($uc_email, 'attribute') ?>"><?= out($uc_email) ?></a>
    </main>
</div>
