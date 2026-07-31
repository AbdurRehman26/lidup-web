import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ClaudeIcon from '@lobehub/icons/es/Claude/components/Color.js';
import CodexIcon from '@lobehub/icons/es/Codex/components/Color.js';
import CursorIcon from '@lobehub/icons/es/Cursor/components/Mono.js';
import GeminiIcon from '@lobehub/icons/es/Gemini/components/Color.js';
import GithubCopilotIcon from '@lobehub/icons/es/GithubCopilot/components/Mono.js';
import OpenCodeIcon from '@lobehub/icons/es/OpenCode/components/Mono.js';
import ProductStage from '../Components/ProductStage';
import SiteLayout from '../Layouts/SiteLayout';

const supportedAgents = [
    { name: 'Codex', maker: 'OpenAI', icon: CodexIcon, className: 'codex' },
    { name: 'Claude AI', maker: 'Anthropic', icon: ClaudeIcon, className: 'claude' },
    { name: 'OpenCode', maker: 'Open source', icon: OpenCodeIcon, className: 'opencode' },
    { name: 'Cursor', maker: 'Anysphere', icon: CursorIcon, className: 'cursor' },
    { name: 'Gemini', maker: 'Google', icon: GeminiIcon, className: 'gemini' },
    { name: 'GitHub Copilot', maker: 'GitHub', icon: GithubCopilotIcon, className: 'copilot' },
];

export default function Home({ trialOffer, packages = [], paidPlans = {} }) {
    const freePackages = packages.filter((pkg) => !pkg.is_paid);
    const [billingInterval, setBillingInterval] = useState('month');
    const visiblePaidPlans = Object.values(paidPlans).filter((plan) => plan.billing_interval === billingInterval);

    return (
        <SiteLayout>
            <Head title="Lidup your Mac" />
            <section className="hero">
                <div className="hero-copy reveal">
                    <div className="availability"><span /> Now in private beta for macOS</div>
                    <h1>Lidup your Mac.<br /><span>Keep work running.</span></h1>
                    <p className="hero-lede">LidUp keeps builds, coding agents, and long-running tasks moving—even when the lid is closed.</p>
                    <div className="hero-actions">
                        <Link className="button" href="/download"><span className="apple-mark">●</span> Download for Mac</Link>
                        <a className="secondary-button" href="#how-it-works">See how it works</a>
                    </div>
                    <p className="microcopy">
                        Requires macOS 14 or later · {trialOffer ? `${trialOffer.duration_label} free` : 'Free download'}
                    </p>
                </div>
                <ProductStage />
            </section>

            <section className="story-section">
                <div className="section-copy">
                    <p className="kicker">Built for unfinished work</p>
                    <h2>Walk away without<br />starting over.</h2>
                    <p>Long tasks shouldn’t depend on you keeping a laptop open. LidUp watches the work you choose, prevents interrupted sessions, and restores normal sleep the moment they finish.</p>
                </div>
                <div className="notification-stack">
                    <div className="mac-notification notification-back"><img src="/app-icon.png" alt="" /><div><small>LIDUP</small><strong>Protection started</strong><span>Codex is working in the background.</span></div><time>now</time></div>
                    <div className="mac-notification"><img src="/app-icon.png" alt="" /><div><small>LIDUP</small><strong>Your work is finished</strong><span>Normal sleep settings have been restored.</span></div><time>9:42</time></div>
                </div>
            </section>

            <section id="how-it-works" className="workflow-section">
                <div className="section-heading"><p className="kicker">Effortless by design</p><h2>Three steps. Then forget about it.</h2></div>
                <div className="agent-support">
                    <div className="agent-support-head">
                        <div><span className="support-light" />Supported AI agents</div>
                        <p>Keep the coding agent you already use running while your Mac is closed.</p>
                    </div>
                    <div className="agent-grid">
                        {supportedAgents.map(({ name, maker, icon: Icon, className }) => (
                            <article className={`agent-card ${className}`} key={name}>
                                <span className="agent-icon"><Icon size={30} /></span>
                                <span><b>{name}</b><small>{maker}</small></span>
                                <i aria-label="Supported" title="Supported">✓</i>
                            </article>
                        ))}
                    </div>
                </div>
                <div className="workflow-grid">
                    <Feature icon="⌁" title="Choose what matters">Pick an active process or create a rule for the tools you trust.</Feature>
                    <Feature icon="▱" title="Close the lid">LidUp keeps your selected work active while the display stays off.</Feature>
                    <Feature icon="✓" title="Come back to done">Get one quiet notification, then let your Mac sleep normally again.</Feature>
                </div>
            </section>

            <section className="feature-showcase">
                <div className="feature-window">
                    <div className="window-bar"><span><i /><i /><i /></span><b>LidUp Settings</b></div>
                    <div className="settings-body">
                        <aside><img src="/app-icon.png" alt="" /><b>LidUp</b><span className="selected">General</span><span>Processes</span><span>Battery</span><span>Updates</span></aside>
                        <div className="settings-panel">
                            <h3>General</h3>
                            <Setting title="Launch LidUp at login" enabled />
                            <Setting title="Show time remaining in menu bar" enabled />
                            <Setting title="Notify me when work finishes" enabled />
                            <div className="settings-note"><span>◒</span><div><b>Battery protection</b><p>LidUp pauses automatically below 20%.</p></div></div>
                        </div>
                    </div>
                </div>
                <div className="section-copy">
                    <p className="kicker">Native where it matters</p>
                    <h2>Made to feel like it came with your Mac.</h2>
                    <p>No dashboard to manage. No cloud account watching your processes. Just a focused menu bar utility with familiar controls and clear status.</p>
                    <ul><li>Private, on-device process monitoring</li><li>Battery-aware safeguards</li><li>Automatic rules for repeat workflows</li></ul>
                </div>
            </section>

            <section id="pricing" className="pricing-section">
                <div className="price-copy"><p className="kicker">Simple pricing</p><h2>One small app.<br />One simple plan.</h2><p>Everything you need to keep your Mac working while you move.</p></div>
                <BillingToggle value={billingInterval} onChange={setBillingInterval} />
                <div className="pricing-cards">
                    {visiblePaidPlans.map((plan) => (
                        <PlanCard packagePlan={plan} href={`/register?plan=${plan.slug}`} trialOffer={trialOffer} featured={plan.plan === 'pro'} key={plan.id} />
                    ))}
                </div>
                {freePackages.length > 0 && (
                    <div className="early-access-ladder">
                        <header><span>Early-access packages</span><small>New accounts move through active tiers automatically.</small></header>
                        <div>
                            {freePackages.map((pkg) => (
                                <article className={pkg.id === trialOffer?.id ? 'is-current' : ''} key={pkg.id}>
                                    <span>{pkg.id === trialOffer?.id ? 'Open now' : pkg.is_paid ? 'Paid' : 'Upcoming'}</span>
                                    <b>{pkg.name}</b>
                                    <strong>{pkg.is_paid ? `${pkg.currency} ${pkg.price}` : pkg.duration_label}</strong>
                                    <small>{pkg.user_limit ? `${pkg.users_count} of ${pkg.user_limit} assigned` : 'No user limit'}</small>
                                </article>
                            ))}
                        </div>
                    </div>
                )}
            </section>

            <section className="faq-invite">
                <div><p className="kicker">A few things worth knowing</p><h2>Questions,<br />answered clearly.</h2><p>From closed-lid behavior and battery safeguards to licenses, privacy, and updates.</p></div>
                <Link className="faq-invite-link" href="/faqs">
                    <span><b>Browse frequently asked questions</b><small>Practical answers about LidUp</small></span>
                    <i aria-hidden="true">→</i>
                </Link>
            </section>
        </SiteLayout>
    );
}

function BillingToggle({ value, onChange }) {
    return (
        <div className="billing-toggle" aria-label="Billing interval">
            <button className={value === 'month' ? 'is-selected' : ''} type="button" onClick={() => onChange('month')}>Monthly</button>
            <button className={value === 'year' ? 'is-selected' : ''} type="button" onClick={() => onChange('year')}>Yearly <span>2 months free</span></button>
        </div>
    );
}

function Feature({ icon, title, children }) {
    return <article><div className="feature-icon">{icon}</div><h3>{title}</h3><p>{children}</p></article>;
}

function Setting({ title, enabled }) {
    return <div className="setting-row"><span>{title}</span><i className={enabled ? 'toggle-on' : ''}><b /></i></div>;
}

function PlanCard({ packagePlan, href, trialOffer, featured = false }) {
    const deviceLabel = packagePlan.devices === 1 ? '1 Mac' : `Up to ${packagePlan.devices} Macs`;

    return (
        <div className={`price-card${featured ? ' featured' : ''}`}>
            {featured && <span className="best-plan">Most popular</span>}
            <div className="price-card-head"><img src="/app-icon.png" alt="" /><div><b>{packagePlan.name}</b><span>{deviceLabel}</span></div></div>
            <div className="price"><strong>{formatMoney(packagePlan.price, packagePlan.currency)}</strong><span>per {packagePlan.interval}</span></div>
            <ul><li>Unlimited protected sessions</li><li>Automatic process rules</li><li>Battery safety controls</li><li>All future updates</li></ul>
            <Link className="button button-wide" href={href}>{trialOffer ? `Start ${trialOffer.duration_label.toLowerCase()} free` : `Choose ${packagePlan.name}`}</Link>
            <small>{trialOffer ? `${trialOffer.name} is currently open. No card required.` : 'Secure checkout powered by Paddle.'}</small>
        </div>
    );
}

const formatMoney = (price, currency) => new Intl.NumberFormat('en', {
    style: 'currency', currency, maximumFractionDigits: Number(price) % 1 === 0 ? 0 : 2,
}).format(price);
