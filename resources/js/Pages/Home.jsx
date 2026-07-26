import { Head, Link, useForm, usePage } from '@inertiajs/react';
import ProductStage from '../Components/ProductStage';
import SiteLayout from '../Layouts/SiteLayout';

export default function Home() {
    const { flash } = usePage().props;
    const form = useForm({ email: '' });

    const subscribe = (event) => {
        event.preventDefault();
        form.post('/updates', { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <SiteLayout>
            <Head title="Keep your Mac working" />
            <section className="hero">
                <div className="hero-copy reveal">
                    <div className="availability"><span /> Now in private beta for macOS</div>
                    <h1>Close your Mac.<br /><span>Keep work running.</span></h1>
                    <p className="hero-lede">LidUp keeps builds, coding agents, and long-running tasks moving—even when the lid is closed.</p>
                    <div className="hero-actions">
                        <Link className="button" href="/download"><span className="apple-mark">●</span> Download for Mac</Link>
                        <a className="secondary-button" href="#how-it-works">See how it works</a>
                    </div>
                    <p className="microcopy">Requires macOS 14 or later · 14 days free</p>
                </div>
                <ProductStage />
            </section>

            <section className="proof-strip">
                <p>Works quietly with the tools you already use</p>
                <div><span>⌘</span> Claude Code</div><div><span>◉</span> Codex</div><div><span>✦</span> Xcode</div><div><span>›_</span> Terminal</div>
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
                <div className="pricing-cards">
                    <PlanCard name="Personal" price="4" devices="1 Mac" href="/register?plan=personal" />
                    <PlanCard name="Pro" price="8" devices="Up to 3 Macs" href="/register?plan=pro" featured />
                </div>
            </section>

            <section className="updates-section">
                <div><p className="kicker">Stay in the loop</p><h2>Good updates only.</h2><p>Occasional notes about beta access and meaningful new features.</p></div>
                <form onSubmit={subscribe} className="updates-form">
                    <label htmlFor="updates-email">Email address</label>
                    <div><input id="updates-email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} placeholder="you@example.com" required /><button className="button" type="submit" disabled={form.processing}>Keep me posted</button></div>
                    {form.errors.email && <p className="form-error">{form.errors.email}</p>}
                    {flash.subscribed && <p className="form-success">{flash.subscribed}</p>}
                </form>
            </section>
        </SiteLayout>
    );
}

function Feature({ icon, title, children }) {
    return <article><div className="feature-icon">{icon}</div><h3>{title}</h3><p>{children}</p></article>;
}

function Setting({ title, enabled }) {
    return <div className="setting-row"><span>{title}</span><i className={enabled ? 'toggle-on' : ''}><b /></i></div>;
}

function PlanCard({ name, price, devices, href, featured = false }) {
    return (
        <div className={`price-card${featured ? ' featured' : ''}`}>
            {featured && <span className="best-plan">Most popular</span>}
            <div className="price-card-head"><img src="/app-icon.png" alt="" /><div><b>{name}</b><span>{devices}</span></div></div>
            <div className="price"><strong>${price}</strong><span>per month</span></div>
            <ul><li>Unlimited protected sessions</li><li>Automatic process rules</li><li>Battery safety controls</li><li>All future updates</li></ul>
            <Link className="button button-wide" href={href}>Start 14-day free trial</Link>
            <small>No card required. Cancel anytime.</small>
        </div>
    );
}
