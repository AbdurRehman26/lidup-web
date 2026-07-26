import { Head, usePage } from '@inertiajs/react';
import SiteLayout from '../Layouts/SiteLayout';

export default function Dashboard({ subscription, updates }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const firstName = user.name.split(' ')[0];
    const status = subscription?.status ?? 'inactive';
    const plan = subscription?.plan === 'early-access' ? 'Early access' : titleCase(subscription?.plan ?? 'No plan');

    return (
        <SiteLayout>
            <Head title="Your dashboard" />
            <section className="dashboard-shell">
                <div className="dashboard-head"><p className="eyebrow">Your LidUp</p><h1>Good to see you,<br /><em>{firstName}.</em></h1></div>
                <div className="dashboard-grid">
                    <article className="status-card">
                        <div><span className="status-dot" /><span className="mono">{status.toUpperCase()}</span></div>
                        <h2>{plan}</h2>
                        <p>{subscription?.trial_ends_at ? `Trial ends ${formatDate(subscription.trial_ends_at)}` : 'Your subscription details will appear here.'}</p>
                        <button className="button" type="button" disabled>Download for macOS <span>↓</span></button>
                        <small>Build coming soon · macOS 14+</small>
                    </article>
                    <article className="account-card">
                        <p className="eyebrow">Account</p>
                        <dl>
                            <div><dt>Name</dt><dd>{user.name}</dd></div>
                            <div><dt>Email</dt><dd>{user.email}</dd></div>
                            <div><dt>Member since</dt><dd>{monthYear(user.created_at)}</dd></div>
                        </dl>
                    </article>
                </div>
                <section className="dashboard-updates">
                    <div><p className="eyebrow">What’s new</p><h2>Product updates</h2></div>
                    {updates.length ? updates.map((update) => (
                        <article key={update.id}><time>{shortDate(update.published_at)}</time><div><h3>{update.title}</h3><p>{update.summary}</p></div></article>
                    )) : (
                        <article className="empty-update"><time>—</time><div><h3>The first build is taking shape</h3><p>Release notes will appear here as soon as the private beta opens.</p></div></article>
                    )}
                </section>
            </section>
        </SiteLayout>
    );
}

const date = (value, options) => new Intl.DateTimeFormat('en-US', options).format(new Date(value));
const formatDate = (value) => date(value, { month: 'short', day: 'numeric', year: 'numeric' });
const monthYear = (value) => date(value, { month: 'short', year: 'numeric' });
const shortDate = (value) => date(value, { month: 'short', day: 'numeric' });
const titleCase = (value) => value.charAt(0).toUpperCase() + value.slice(1);
