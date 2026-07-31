import { Head, Link } from '@inertiajs/react';
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

export default function Home({ trialOffer, packages = [] }) {
    const freePackages = packages.filter((pkg) => !pkg.is_paid);

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
                <div className="price-copy"><p className="kicker">Limited early-bird offer</p><h2>Join early.<br /><em>Use LidUp free.</em></h2><p>Claim an activation key before the current tier fills. No card, no commitment.</p></div>
                {trialOffer ? <EarlyBirdOfferCard offer={trialOffer} /> : <div className="early-bird-closed">The current early-bird allocation is full. Check back for the next tier.</div>}
                {freePackages.some((pkg) => pkg.id !== trialOffer?.id) && (
                    <div className="upcoming-early-tiers">
                        <span>What comes next</span>
                        {freePackages.filter((pkg) => pkg.id !== trialOffer?.id).map((pkg) => (
                            <article key={pkg.id}><b>{pkg.name}</b><strong>{pkg.duration_label} free</strong><small>{pkg.user_limit ? `Up to ${pkg.user_limit} early users` : 'Unlimited places'}</small></article>
                        ))}
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

function EarlyBirdOfferCard({ offer }) {
    const remaining = offer.remaining_spots;
    const claimedPercent = offer.user_limit ? Math.min(100, Math.round((offer.users_count / offer.user_limit) * 100)) : 0;

    return (
        <article className="early-bird-offer-card" aria-label="Current early-bird offer">
            <div className="early-bird-ribbon"><span>Early bird</span><b>100% off</b></div>
            <header>
                <div><span>Available now · {offer.name}</span><h3>Free access.<br />Real activation key.</h3></div>
                <img src="/app-icon.png" alt="" />
            </header>
            <div className="early-bird-price"><strong>€0</strong><del>Paid launch plan</del><span>for {offer.duration_label.toLowerCase()}</span></div>
            <div className="early-bird-capacity">
                <div><strong>{remaining === null ? 'Unlimited' : remaining} {remaining === 1 ? 'spot' : 'spots'} left</strong><span>{offer.users_count} already claimed</span></div>
                {offer.user_limit && <i><b style={{ width: `${claimedPercent}%` }} /></i>}
            </div>
            <Link className="early-bird-cta" href="/register"><span className="apple-mark">●</span> Claim early-bird access <b>→</b></Link>
            <small className="early-bird-no-card">No credit card required · Activation key issued instantly</small>
            <ul>
                <li>Full LidUp access for {offer.duration_label.toLowerCase()}</li>
                <li>One activation key for {offer.device_limit} {offer.device_limit === 1 ? 'Mac' : 'Macs'}</li>
                <li>Every coding-agent integration included</li>
                <li>Automatic task-completion notifications</li>
            </ul>
        </article>
    );
}

function Feature({ icon, title, children }) {
    return <article><div className="feature-icon">{icon}</div><h3>{title}</h3><p>{children}</p></article>;
}

function Setting({ title, enabled }) {
    return <div className="setting-row"><span>{title}</span><i className={enabled ? 'toggle-on' : ''}><b /></i></div>;
}
