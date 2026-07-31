import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import SiteLayout from '../Layouts/SiteLayout';

export default function Dashboard({ subscription, trial, earlyBirdPackages = [], apiKey, activations, latestRelease, updates }) {
    const { auth, flash } = usePage().props;
    const [copied, setCopied] = useState(false);
    const user = auth.user;
    const visibleKey = flash.plain_api_key;
    const hasApiKey = apiKey.exists;
    const currentPlan = subscription?.plan ?? (trial?.active ? trial.plan : null);
    const active = (Boolean(subscription?.paddle_id) && ['active', 'trialing'].includes(subscription?.status))
        || trial?.active;
    const accessStatus = subscription?.status ?? trial?.status ?? 'inactive';

    const copyKey = async () => {
        if (!visibleKey) return;
        await navigator.clipboard.writeText(visibleKey);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 1800);
    };

    const rotateKey = () => {
        if (window.confirm('Generate a new key? Your current key and connected devices will stop working.')) {
            router.put('/api-key', {}, { preserveScroll: true });
        }
    };

    const generateKey = () => router.post('/api-key', {}, { preserveScroll: true });

    const removeDevice = (activation) => {
        if (window.confirm(`Deactivate ${activation.device_name || 'this Mac'}?`)) {
            router.delete(`/devices/${activation.id}`, { preserveScroll: true });
        }
    };

    return (
        <SiteLayout>
            <Head title="Your LidUp account" />
            <div className="account-dashboard">
                <header className="account-dashboard-head">
                    <div>
                        <p className="eyebrow">Your LidUp account</p>
                        <h1>Welcome back, {user.name.split(' ')[0]}.</h1>
                    </div>
                    <p>Choose your plan, manage your activation, and keep every Mac up to date.</p>
                </header>

                {trial?.package && (
                    <section className="assigned-package" aria-label="Assigned subscription package">
                        <div className="assigned-package-mark" aria-hidden="true">
                            {trial.package.duration === 'Unlimited' ? '∞' : <CheckIcon />}
                        </div>
                        <div className="assigned-package-copy">
                            <span>Assigned to your account</span>
                            <h2>{trial.package.name}</h2>
                            <p>{trial.package.description || `${trial.package.duration} access to LidUp.`}</p>
                        </div>
                        <dl>
                            <div>
                                <dt>Access</dt>
                                <dd>{trial.package.duration}</dd>
                            </div>
                            <div>
                                <dt>Expires</dt>
                                <dd>{trial.ends_at ? formatDate(trial.ends_at) : 'Never'}</dd>
                            </div>
                            <div>
                                <dt>Macs</dt>
                                <dd>{trial.package.device_limit}</dd>
                            </div>
                        </dl>
                        <div className="assigned-package-badges">
                            <span>{trial.package.is_paid ? 'Paid package' : 'Included'}</span>
                            {!trial.package.is_visible && <span>Private package</span>}
                            {!trial.package.is_active && <span>Reserved access</span>}
                        </div>
                    </section>
                )}

                <section className="dashboard-early-bird-tiers" aria-label="Early-bird tiers">
                    <header><div><p className="eyebrow">Early-bird access</p><h2>Access tiers</h2></div><span>Paid packages are not open yet</span></header>
                    <div>
                        {earlyBirdPackages.map((tier) => (
                            <article className={trial?.package?.id === tier.id ? 'is-current' : ''} key={tier.id}>
                                <span>{trial?.package?.id === tier.id ? 'Your tier' : tier.id === earlyBirdPackages.find((item) => item.remaining_spots === null || item.remaining_spots > 0)?.id ? 'Open now' : 'Upcoming'}</span>
                                <h3>{tier.name}</h3>
                                <strong>{tier.duration_label} free</strong>
                                <small>{tier.remaining_spots === null ? 'Unlimited places' : `${tier.remaining_spots} of ${tier.user_limit} places remaining`}</small>
                            </article>
                        ))}
                    </div>
                </section>

                <section className="license-wallet">
                    <header className="license-wallet-head">
                        <div>
                            <div className="license-title-row">
                                <h2>Activation key</h2>
                                <span className={`license-state ${active ? 'is-active' : ''}`}><CheckIcon />{active ? 'Active' : 'Inactive'}</span>
                                {trial?.active && trial.package && !trial.package.is_paid && (
                                    <span className="early-bird-key-badge">Early bird · {trial.package.name}</span>
                                )}
                            </div>
                            <p>{subscription?.created_at
                                ? `Plan started ${formatDate(subscription.created_at)}`
                                : trial?.active
                                    ? `${trial.package?.name ?? 'Free trial'} · ${trial.ends_at ? `ends ${formatDate(trial.ends_at)}` : 'unlimited access'}`
                                    : 'Created for your LidUp account'}</p>
                        </div>
                        <span className="early-access-only">Early-bird access</span>
                    </header>

                    {(flash.api_key_message || flash.device_message) && (
                        <div className="wallet-message">{flash.api_key_message || flash.device_message}</div>
                    )}

                    <div className="license-key-block">
                        {trial?.active && trial.package && !trial.package.is_paid && (
                            <div className="key-early-bird-note">
                                <span>Early-bird member {trial.cohort_position ? `#${trial.cohort_position}` : ''}</span>
                                <strong>{trial.package.duration} access is active with this key</strong>
                                {trial.package.user_limit && <small>{trial.package.users_count} of {trial.package.user_limit} early-bird places claimed</small>}
                            </div>
                        )}
                        {!hasApiKey && !trial?.active && earlyBirdPackages.length > 0 && (
                            <div className="key-early-bird-note is-unclaimed">
                                <span>Early-bird access available</span>
                                <strong>Generate your activation key to claim the current tier</strong>
                                <small>{earlyBirdPackages[0].remaining_spots ?? 'Unlimited'} places remaining</small>
                            </div>
                        )}
                        <label>Activation key</label>
                        <div className="license-key-field">
                            <code>{visibleKey ?? (hasApiKey ? `${apiKey.prefix}${'•'.repeat(22)}` : 'No activation key generated')}</code>
                            <button type="button" onClick={visibleKey ? copyKey : hasApiKey ? rotateKey : generateKey} aria-label={visibleKey ? 'Copy activation key' : hasApiKey ? 'Generate a new activation key' : 'Generate activation key and claim early-bird access'}>
                                {visibleKey ? (copied ? 'Copied' : <CopyIcon />) : hasApiKey ? 'Generate new key' : 'Generate key'}
                            </button>
                        </div>
                        {!visibleKey && <p>{hasApiKey ? 'For security, the full key is shown only when it is first generated.' : 'Generating a key claims your early-bird place and starts its access period.'}</p>}
                    </div>

                    <dl className="license-facts">
                        <div><dt>Plan</dt><dd>{titleCase(currentPlan ?? 'Free')}</dd></div>
                        <div><dt>Status</dt><dd>{titleCase(accessStatus)}</dd></div>
                        <div><dt>Devices</dt><dd>{activations.length} of {trial?.package?.device_limit ?? 0} active</dd></div>
                        <div><dt>Updates</dt><dd>{active ? 'Included with your plan' : 'Free downloads available'}</dd></div>
                    </dl>

                    <div className="wallet-section">
                        <div className="wallet-section-title">
                            <h3>Active Macs</h3>
                            <span>{activations.length} connected</span>
                        </div>
                        <div className="wallet-list">
                            {activations.length ? activations.map((activation) => (
                                <article className="wallet-device" key={activation.id}>
                                    <span className="wallet-device-icon"><LaptopIcon /></span>
                                    <div>
                                        <strong>{activation.device_name || 'Unnamed Mac'}</strong>
                                        <small>macOS · LidUp {activation.app_version || 'version unknown'} · Activated {formatDate(activation.activated_at)}</small>
                                    </div>
                                    <button type="button" onClick={() => removeDevice(activation)} aria-label={`Deactivate ${activation.device_name || 'Mac'}`}><TrashIcon /></button>
                                </article>
                            )) : (
                                <div className="wallet-empty">No Macs activated yet. Paste your activation key into LidUp to connect one.</div>
                            )}
                        </div>
                    </div>

                    <div className="wallet-section">
                        <div className="wallet-section-title"><h3>Latest download</h3></div>
                        <div className="wallet-download">
                            <div>
                                <strong>{latestRelease ? `LidUp ${latestRelease.version}` : 'LidUp for macOS'}</strong>
                                <small>{latestRelease ? `Released ${formatDate(latestRelease.published_at)}` : 'The first signed build is coming soon'}</small>
                            </div>
                            {latestRelease?.available
                                ? <a className="wallet-download-button" href="/download/latest"><DownloadIcon />Download</a>
                                : <Link className="wallet-download-button is-disabled" href="/download">View download</Link>}
                        </div>
                    </div>
                </section>

                {updates.length > 0 && (
                    <section className="account-update-strip">
                        <div><p className="eyebrow">Latest update</p><h2>{updates[0].title}</h2><p>{updates[0].summary}</p></div>
                        <time>{formatDate(updates[0].published_at)}</time>
                    </section>
                )}
            </div>
        </SiteLayout>
    );
}

function CheckIcon() {
    return <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 12 3 3 7-7" /><circle cx="12" cy="12" r="9" /></svg>;
}
function CopyIcon() {
    return <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="2" /><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" /></svg>;
}
function LaptopIcon() {
    return <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="11" rx="2" /><path d="M3 19h18M7 15l-1 4m11-4 1 4" /></svg>;
}
function TrashIcon() {
    return <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m-9 0 1 13h10l1-13M10 11v5m4-5v5" /></svg>;
}
function DownloadIcon() {
    return <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 18v3h14v-3" /></svg>;
}

const formatDate = (value) => new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
const titleCase = (value) => value.charAt(0).toUpperCase() + value.slice(1).replace('_', ' ');
