<!--
    Under-construction homepage, in the Click Me Studio style.
    Self-contained styling so it works regardless of the project's theme.

    Optional constants (define in config/site_owner.php):
      OUR_EMAIL_ADDRESS - contact email shown on the page
      OUR_NAME          - company/site name shown in the header and footer
    Both fall back to placeholder values if not defined.
-->

<style>
    @import url('https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@1,500&family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap');

    /* Scoped to the construction page via the .uc-page wrapper */
    .uc-page {
        --uc-cream: #f7f4ee;
        --uc-dark: #07151f;
        --uc-dark-2: #123041;
        --uc-text: #0e1e27;
        --uc-muted: rgba(14, 30, 39, 0.66);
        --uc-accent: #00a79d;
        --uc-accent-warm: #ff6b4a;
        --uc-accent-blue: #3157d5;
        --uc-border: rgba(14, 30, 39, 0.12);
        --uc-border-2: rgba(14, 30, 39, 0.22);
        --uc-font-display: 'Skia', 'Space Grotesk', 'Inter', system-ui, sans-serif;
        --uc-font-body: 'Inter', system-ui, sans-serif;

        position: fixed;
        inset: 0;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: clamp(1rem, 3vh, 2.5rem);
        padding: clamp(1.25rem, 3vh, 2rem) 0;
        background:
            linear-gradient(135deg, rgba(0, 167, 157, 0.08), transparent 32%),
            linear-gradient(220deg, rgba(255, 107, 74, 0.09), transparent 30%),
            var(--uc-cream);
        color: var(--uc-text);
        font-family: var(--uc-font-body);
        overflow: hidden auto;
        -webkit-font-smoothing: antialiased;
    }

    .uc-page *, .uc-page *::before, .uc-page *::after { box-sizing: border-box; }

    .uc-container {
        width: min(1100px, calc(100% - 32px));
        margin: 0 auto;
    }

    /* Top bar: brand + status */
    .uc-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        font-family: var(--uc-font-display);
        font-weight: 600;
        opacity: 0;
        animation: uc-fade-up 0.7s ease forwards;
    }

    .uc-brand {
        font-size: 1.1rem;
        letter-spacing: -0.03em;
    }

    .uc-status {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 6px 14px;
        border: 1px solid var(--uc-border);
        border-radius: 999px;
        font-size: 0.8rem;
    }

    .uc-status::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--uc-accent);
        animation: uc-pulse 2.4s ease-in-out infinite;
    }

    /* Giant drifting word rows */
    .uc-row {
        display: flex;
        width: max-content;
        animation: uc-drift 40s linear infinite;
    }

    .uc-row span {
        font-family: var(--uc-font-display);
        font-size: clamp(3.6rem, 13vw, 13rem);
        font-weight: 700;
        line-height: 0.85;
        letter-spacing: -0.075em;
        white-space: nowrap;
        padding-right: clamp(1.1rem, 3vw, 3rem);
        color: transparent;
        -webkit-text-stroke: 1px rgba(14, 30, 39, 0.2);
    }

    .uc-row--bottom {
        animation-direction: reverse;
    }

    .uc-row--bottom span {
        color: var(--uc-text);
        -webkit-text-stroke: 0;
    }

    @keyframes uc-drift {
        from { transform: translate3d(0, 0, 0); }
        to   { transform: translate3d(-50%, 0, 0); }
    }

    /* Middle: copy on the left, headline on the right */
    .uc-middle {
        display: grid;
        grid-template-columns: minmax(240px, 0.8fr) minmax(0, 1.2fr);
        gap: clamp(1.5rem, 6vw, 6rem);
        align-items: end;
    }

    .uc-copy {
        display: grid;
        gap: clamp(1rem, 3vh, 1.75rem);
        opacity: 0;
        animation: uc-fade-up 0.7s ease 0.2s forwards;
    }

    .uc-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin: 0;
        font-family: var(--uc-font-display);
        font-size: clamp(0.72rem, 0.65rem + 0.28vw, 0.9rem);
        font-weight: 600;
        letter-spacing: 0.28em;
        text-transform: uppercase;
        color: rgba(14, 30, 39, 0.62);
    }

    .uc-meta span + span {
        display: inline-flex;
        align-items: center;
        gap: 16px;
    }

    .uc-meta span + span::before {
        content: "";
        width: 36px;
        height: 1px;
        background: rgba(14, 30, 39, 0.28);
    }

    .uc-sub {
        margin: 0;
        max-width: 46ch;
        font-size: clamp(1rem, 0.9rem + 0.4vw, 1.18rem);
        line-height: 1.85;
        letter-spacing: 0.02em;
        color: var(--uc-muted);
    }

    .uc-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .uc-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 13px 26px;
        border: 1.5px solid var(--uc-border-2);
        border-radius: 999px;
        background: transparent;
        color: var(--uc-text);
        font-family: var(--uc-font-display);
        font-size: 0.95rem;
        font-weight: 600;
        text-decoration: none;
        transition: transform 0.18s cubic-bezier(.16, 1, .3, 1), background 0.18s ease, box-shadow 0.18s ease;
    }

    .uc-btn svg { width: 17px; height: 17px; }

    .uc-btn--primary {
        background: linear-gradient(135deg, var(--uc-dark), var(--uc-dark-2));
        border-color: var(--uc-dark);
        color: var(--uc-cream);
        box-shadow: 0 2px 0 rgba(21, 21, 21, 0.18);
    }

    .uc-btn--primary:hover {
        background: linear-gradient(135deg, var(--uc-dark-2), var(--uc-dark));
        box-shadow: 0 10px 30px rgba(7, 21, 31, 0.22);
        transform: translateY(-1px);
    }

    .uc-title {
        margin: 0;
        font-family: var(--uc-font-display);
        font-size: clamp(3rem, 7.5vw, 8rem);
        font-weight: 700;
        line-height: 0.86;
        letter-spacing: -0.07em;
        text-align: right;
        color: var(--uc-text);
        opacity: 0;
        animation: uc-fade-up 0.8s ease 0.1s forwards;
    }

    .uc-title em {
        font-family: 'EB Garamond', Georgia, serif;
        font-style: italic;
        font-weight: 500;
        letter-spacing: -0.02em;
        background: linear-gradient(110deg, var(--uc-accent), var(--uc-accent-blue) 56%, var(--uc-accent-warm));
        background-size: 200% auto;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        animation: uc-grad 8s linear infinite;
    }

    @keyframes uc-grad {
        from { background-position: 0% 50%; }
        to   { background-position: 200% 50%; }
    }

    .uc-footer {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--uc-border);
        font-size: 0.8rem;
        color: rgba(14, 30, 39, 0.55);
    }

    @keyframes uc-fade-up {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes uc-pulse {
        0%, 100% { opacity: 1; }
        50%      { opacity: 0.35; }
    }

    @media (max-width: 760px) {
        .uc-middle { grid-template-columns: 1fr; }
        .uc-title { text-align: left; order: -1; }
        .uc-footer { flex-direction: column; gap: 0.35rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .uc-page *, .uc-page *::before { animation: none !important; opacity: 1 !important; transition: none !important; }
    }
</style>

<?php
    // Pull site owner details from constants. These may be defined but empty
    // in a default setup, so fall back whenever the value is blank.
    $uc_email = (defined('OUR_EMAIL_ADDRESS') && OUR_EMAIL_ADDRESS !== '') ? OUR_EMAIL_ADDRESS : 'hello@example.com';
    $uc_name  = (defined('OUR_NAME') && OUR_NAME !== '') ? OUR_NAME : 'Your Company';
?>

<div class="uc-page">
    <header class="uc-container uc-bar">
        <span class="uc-brand"><?= out($uc_name) ?></span>
        <span class="uc-status">Launching soon</span>
    </header>

    <div class="uc-row" aria-hidden="true">
        <span>Coming soon</span><span>Coming soon</span><span>Coming soon</span><span>Coming soon</span>
    </div>

    <main class="uc-container uc-middle">
        <div class="uc-copy">
            <p class="uc-meta">
                <span><?= out($uc_name) ?></span>
                <span>Coming Soon</span>
            </p>

            <p class="uc-sub">We are putting the finishing touches on our new site. Check back shortly, or get in touch if you would like to hear the moment we launch.</p>

            <div class="uc-actions">
                <a class="uc-btn uc-btn--primary" href="mailto:<?= out($uc_email, 'attribute') ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="2" y="4" width="20" height="16" rx="2" />
                        <path d="m22 7-10 5L2 7" />
                    </svg>
                    <?= out($uc_email) ?>
                </a>
            </div>
        </div>

        <h1 class="uc-title">Something <em>great</em><br>is under<br>construction</h1>
    </main>

    <div class="uc-row uc-row--bottom" aria-hidden="true">
        <span>Under construction</span><span>Under construction</span><span>Under construction</span><span>Under construction</span>
    </div>

    <footer class="uc-container uc-footer">
        <span>&copy; <?= date('Y') ?> <?= out($uc_name) ?>. All rights reserved.</span>
        <span><?= out($uc_email) ?></span>
    </footer>
</div>
