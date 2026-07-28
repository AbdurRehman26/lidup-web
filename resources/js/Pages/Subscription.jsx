import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import SiteLayout from '../Layouts/SiteLayout';

export default function Subscription({ subscription, plans, billingConfigured }) {
    const { flash } = usePage().props;
    const queryPlan = new URLSearchParams(window.location.search).get('plan');
    const initialPlan = plans[queryPlan] ? queryPlan : (subscription?.plan ?? 'personal');
    const form = useForm({ plan: initialPlan });
    const [checkoutLoading, setCheckoutLoading] = useState(false);
    const [checkoutError, setCheckoutError] = useState('');
    const hasSubscription = Boolean(subscription?.paddle_id)
        && ['active', 'trialing', 'past_due'].includes(subscription?.status);

    const save = async (event) => {
        event.preventDefault();

        if (hasSubscription) {
            form.patch('/subscription', { preserveScroll: true });
            return;
        }

        setCheckoutLoading(true);
        setCheckoutError('');

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const response = await fetch('/subscription/checkout', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ plan: form.data.plan }),
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || payload.errors?.plan?.[0] || 'Unable to start checkout.');
            }

            if (!window.Paddle) {
                throw new Error('Paddle checkout could not be loaded. Please refresh and try again.');
            }

            window.Paddle.Checkout.open(payload.checkout);
        } catch (error) {
            setCheckoutError(error.message);
        } finally {
            setCheckoutLoading(false);
        }
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
                <div className="subscription-heading"><p className="kicker">Billing & access</p><h1>Manage subscription</h1><p>Choose your plan and complete payment securely with Paddle. Your access updates automatically after checkout.</p></div>
                {flash.subscription_updated && <div className="success-banner">{flash.subscription_updated}</div>}
                {!billingConfigured && <div className="wallet-message">Paddle needs a client token and price IDs before checkout can open.</div>}

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
                    <button className="button" type="submit" disabled={form.processing || checkoutLoading || !billingConfigured}>
                        {checkoutLoading ? 'Opening secure checkout…' : hasSubscription ? 'Update plan' : 'Continue to secure checkout'}
                    </button>
                    {(form.errors.plan || checkoutError) && <p className="form-error">{form.errors.plan || checkoutError}</p>}
                </form>

                {hasSubscription && (
                    <div className="cancel-panel"><div><b>Cancel subscription</b><p>Your access continues until the end of the current billing period.</p></div><button type="button" onClick={cancel}>Cancel plan</button></div>
                )}
            </section>
        </SiteLayout>
    );
}

const formatDate = (value) => new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
