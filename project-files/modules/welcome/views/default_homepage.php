<!--
    Under-construction homepage.
    Self-contained styling so it works regardless of the project's theme.

    Optional constants (define in config/site_owner.php):
      OUR_EMAIL_ADDRESS - contact email shown on the page
      OUR_NAME          - company/site name shown in the footer
    Both fall back to placeholder values if not defined.
-->

<style>
    /* Scoped to the construction page via the .uc-page wrapper */
    .uc-page {
        --uc-bg: #0e1116;
        --uc-bg-soft: #161b22;
        --uc-line: rgba(255, 255, 255, 0.06);
        --uc-text: #e6e1d8;
        --uc-text-dim: #8a8f98;
        --uc-accent: #e0a458;
        --uc-accent-soft: rgba(224, 164, 88, 0.12);

        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--uc-bg);
        color: var(--uc-text);
        overflow: hidden;
        font-family: ui-sans-serif, "Helvetica Neue", Helvetica, sans-serif;
    }

    /* Import distinctive fonts */
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600&family=Archivo:wght@400;500;600&display=swap');

    .uc-page .uc-display {
        font-family: "Fraunces", Georgia, serif;
    }

    .uc-page .uc-body-font {
        font-family: "Archivo", ui-sans-serif, sans-serif;
    }

    /* Animated blueprint grid background */
    .uc-grid {
        position: absolute;
        inset: -2px;
        background-image:
            linear-gradient(var(--uc-line) 1px, transparent 1px),
            linear-gradient(90deg, var(--uc-line) 1px, transparent 1px);
        background-size: 44px 44px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 45%, #000 30%, transparent 80%);
        -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 45%, #000 30%, transparent 80%);
        animation: uc-grid-drift 22s linear infinite;
    }

    @keyframes uc-grid-drift {
        from { transform: translate(0, 0); }
        to   { transform: translate(44px, 44px); }
    }

    /* Soft glow behind the content */
    .uc-glow {
        position: absolute;
        top: 38%;
        left: 50%;
        width: 640px;
        height: 640px;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle, var(--uc-accent-soft) 0%, transparent 60%);
        pointer-events: none;
    }

    .uc-content {
        position: relative;
        z-index: 2;
        text-align: center;
        padding: 2rem;
        max-width: 640px;
    }

    /* The animated build mark */
    .uc-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        margin-bottom: 2rem;
        border: 1px solid var(--uc-accent);
        border-radius: 14px;
        position: relative;
        opacity: 0;
        animation: uc-fade-up 0.7s ease forwards;
    }

    .uc-mark::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 14px;
        background: var(--uc-accent-soft);
        animation: uc-pulse 2.8s ease-in-out infinite;
    }

    .uc-mark svg {
        width: 30px;
        height: 30px;
        stroke: var(--uc-accent);
        position: relative;
        z-index: 1;
    }

    @keyframes uc-pulse {
        0%, 100% { opacity: 0.4; transform: scale(1); }
        50%      { opacity: 0.9; transform: scale(1.08); }
    }

    .uc-eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.28em;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--uc-accent);
        margin: 0 0 1.25rem 0;
        opacity: 0;
        animation: uc-fade-up 0.7s ease 0.1s forwards;
    }

    .uc-title {
        font-size: clamp(2.4rem, 6vw, 4rem);
        line-height: 1.05;
        font-weight: 600;
        margin: 0 0 1.4rem 0;
        letter-spacing: -0.02em;
        opacity: 0;
        animation: uc-fade-up 0.7s ease 0.2s forwards;
    }

    .uc-title em {
        font-style: italic;
        color: var(--uc-accent);
    }

    .uc-sub {
        font-size: 1.075rem;
        line-height: 1.7;
        color: var(--uc-text-dim);
        margin: 0 auto 2.5rem auto;
        max-width: 460px;
        opacity: 0;
        animation: uc-fade-up 0.7s ease 0.3s forwards;
    }

    .uc-divider {
        width: 48px;
        height: 1px;
        background: var(--uc-line);
        margin: 0 auto 2.5rem auto;
        opacity: 0;
        animation: uc-fade-up 0.7s ease 0.35s forwards;
    }

    .uc-contact {
        opacity: 0;
        animation: uc-fade-up 0.7s ease 0.45s forwards;
    }

    .uc-contact-label {
        display: block;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: var(--uc-text-dim);
        margin-bottom: 0.85rem;
    }

    .uc-contact a {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.85rem 1.6rem;
        border: 1px solid var(--uc-accent);
        border-radius: 10px;
        color: var(--uc-text);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        transition: background 0.25s ease, transform 0.25s ease;
    }

    .uc-contact a:hover {
        background: var(--uc-accent);
        color: #0e1116;
        transform: translateY(-2px);
    }

    .uc-contact a svg {
        width: 17px;
        height: 17px;
    }

    .uc-footer {
        position: absolute;
        bottom: 1.75rem;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 0.78rem;
        color: var(--uc-text-dim);
        z-index: 2;
        opacity: 0;
        animation: uc-fade-up 0.7s ease 0.6s forwards;
    }

    @keyframes uc-fade-up {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 550px) {
        .uc-content { padding: 1.5rem; }
    }
</style>

<?php
    // Pull site owner details from constants. These may be defined but empty
    // in a default setup, so fall back whenever the value is blank.
    $uc_email = (defined('OUR_EMAIL_ADDRESS') && OUR_EMAIL_ADDRESS !== '') ? OUR_EMAIL_ADDRESS : 'hello@example.com';
    $uc_name  = (defined('OUR_NAME') && OUR_NAME !== '') ? OUR_NAME : 'Your Company';
?>

<div class="uc-page uc-body-font">
    <div class="uc-grid"></div>
    <div class="uc-glow"></div>

    <div class="uc-content">
        <div class="uc-mark">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 21h18" />
                <path d="M5 21V7l8-4v18" />
                <path d="M19 21V11l-6-4" />
                <path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01" />
            </svg>
        </div>

        <p class="uc-eyebrow">Coming Soon</p>

        <h1 class="uc-title uc-display">Something great is <em>under construction</em></h1>

        <p class="uc-sub">We are putting the finishing touches on our new home. Check back shortly, or reach out if you would like to be the first to know when we launch.</p>

        <div class="uc-divider"></div>

        <div class="uc-contact">
            <span class="uc-contact-label">Get in touch</span>
            <a href="mailto:<?= $uc_email ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2" />
                    <path d="m22 7-10 5L2 7" />
                </svg>
                <?= $uc_email ?>
            </a>
        </div>
    </div>

    <div class="uc-footer">
        &copy; <?= date('Y') ?> <?= $uc_name ?>. All rights reserved.
    </div>
</div>