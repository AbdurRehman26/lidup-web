import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import SiteLayout from '../Layouts/SiteLayout';

export default function Register({ plans, selectedPlan, trialOffer }) {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '', plan: selectedPlan });
    const [billingInterval, setBillingInterval] = useState(plans[selectedPlan]?.billing_interval ?? 'month');
    const visiblePlans = Object.entries(plans).filter(([, plan]) => plan.billing_interval === billingInterval);

    const changeBilling = (interval) => {
        setBillingInterval(interval);
        const currentTier = plans[form.data.plan]?.plan;
        const replacement = Object.entries(plans).find(([, plan]) => plan.billing_interval === interval && plan.plan === currentTier)
            ?? Object.entries(plans).find(([, plan]) => plan.billing_interval === interval);
        if (replacement) form.setData('plan', replacement[0]);
    };

    const submit = (event) => {
        event.preventDefault();
        form.post('/register', {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    const firstError = Object.values(form.errors)[0];

    return (
        <SiteLayout>
            <Head title="Join the private beta" />
            <section className="auth-shell">
                <div className="auth-copy"><p className="eyebrow">{trialOffer?.name ?? 'Private beta'}</p><h1>Close the lid.<br /><em>Come back to done.</em></h1><p>{trialOffer ? `Create your account and receive ${trialOffer.duration_label.toLowerCase()} of early access. No credit card required.` : 'Create your account and choose the plan that fits your Macs.'}</p></div>
                <form onSubmit={submit} className="auth-card">
                    <h2>Create your account</h2>
                    <BillingToggle value={billingInterval} onChange={changeBilling} />
                    <div className="plan-picker" aria-label="Choose a plan">
                        {visiblePlans.map(([key, plan]) => (
                            <button className={form.data.plan === key ? 'selected' : ''} type="button" key={key} onClick={() => form.setData('plan', key)}>
                                <span>{plan.name}</span><b>{formatMoney(plan.price, plan.currency)}<small>/{plan.interval}</small></b>
                            </button>
                        ))}
                    </div>
                    <Field label="Name" type="text" value={form.data.name} onChange={(value) => form.setData('name', value)} autoComplete="name" autoFocus />
                    <Field label="Email" type="email" value={form.data.email} onChange={(value) => form.setData('email', value)} autoComplete="email" />
                    <Field label="Password" type="password" value={form.data.password} onChange={(value) => form.setData('password', value)} autoComplete="new-password" />
                    <Field label="Confirm password" type="password" value={form.data.password_confirmation} onChange={(value) => form.setData('password_confirmation', value)} autoComplete="new-password" />
                    {firstError && <p className="form-error">{firstError}</p>}
                    <button className="button button-wide" type="submit" disabled={form.processing}>{trialOffer ? 'Start my trial' : 'Create my account'} <span>→</span></button>
                    <p>Already have an account? <Link href="/login">Log in</Link></p>
                </form>
            </section>
        </SiteLayout>
    );
}

function BillingToggle({ value, onChange }) {
    return <div className="billing-toggle is-compact"><button className={value === 'month' ? 'is-selected' : ''} type="button" onClick={() => onChange('month')}>Monthly</button><button className={value === 'year' ? 'is-selected' : ''} type="button" onClick={() => onChange('year')}>Yearly</button></div>;
}

const formatMoney = (price, currency) => new Intl.NumberFormat('en', {
    style: 'currency', currency, maximumFractionDigits: Number(price) % 1 === 0 ? 0 : 2,
}).format(price);

function Field({ label, value, onChange, ...props }) {
    return <label>{label}<input {...props} value={value} onChange={(event) => onChange(event.target.value)} required /></label>;
}
