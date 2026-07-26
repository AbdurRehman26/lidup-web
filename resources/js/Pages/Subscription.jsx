import { Head, router, useForm, usePage } from '@inertiajs/react';
import SiteLayout from '../Layouts/SiteLayout';

export default function Subscription({ subscription, plans }) {
    const { flash } = usePage().props;
    const form = useForm({ plan: subscription?.plan ?? 'personal' });

    const save = (event) => {
        event.preventDefault();
        form.patch('/subscription', { preserveScroll: true });
    };

    const cancel = () => {
        if (window.confirm('Cancel your LidUp subscription now?')) {
            router.delete('/subscription', { preserveScroll: true });
        }
    };

    return (
        <SiteLayout>
            <Head title="Manage subscription" />
            <section className="subscription-shell">
                <div className="subscription-heading"><p className="kicker">Billing & access</p><h1>Manage subscription</h1><p>Choose the plan that fits your Macs. Payment checkout can be connected to Stripe or Paddle when production billing is enabled.</p></div>
                {flash.subscription_updated && <div className="success-banner">{flash.subscription_updated}</div>}

                <form onSubmit={save} className="subscription-panel">
                    <div className="current-plan">
                        <div><span className={`status-dot ${subscription?.status === 'canceled' ? 'canceled' : ''}`} /><div><small>Current status</small><b>{subscription?.status ?? 'inactive'}</b></div></div>
                        <span>{subscription?.trial_ends_at ? `Trial ends ${formatDate(subscription.trial_ends_at)}` : 'No active trial'}</span>
                    </div>
                    <div className="subscription-options">
                        {Object.entries(plans).map(([key, plan]) => (
                            <label className={form.data.plan === key ? 'selected' : ''} key={key}>
                                <input type="radio" name="plan" value={key} checked={form.data.plan === key} onChange={(event) => form.setData('plan', event.target.value)} />
                                <span><b>{plan.name}</b><small>{plan.devices} {plan.devices === 1 ? 'Mac' : 'Macs'}</small></span>
                                <strong>${plan.price}<small>/mo</small></strong>
                            </label>
                        ))}
                    </div>
                    <button className="button" type="submit" disabled={form.processing}>Save plan</button>
                    {form.errors.plan && <p className="form-error">{form.errors.plan}</p>}
                </form>

                <div className="cancel-panel"><div><b>Cancel subscription</b><p>Your access ends immediately during the beta. You can rejoin at any time.</p></div><button type="button" onClick={cancel}>Cancel plan</button></div>
            </section>
        </SiteLayout>
    );
}

const formatDate = (value) => new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
