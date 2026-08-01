import { Head, Link, useForm } from '@inertiajs/react';
import SiteLayout from '../Layouts/SiteLayout';

export default function Register({ selectedPlan, trialOffer }) {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '', plan: selectedPlan });

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
                    {trialOffer && (
                        <div className="signup-early-bird">
                            <span><i />Early-bird access is available</span>
                            <strong>{trialOffer.remaining_spots === null ? 'Unlimited' : trialOffer.remaining_spots} {trialOffer.remaining_spots === 1 ? 'spot remains' : 'spots remain'}</strong>
                            <small>{trialOffer.duration_label} free · Verify your email, then generate a key to claim your place</small>
                        </div>
                    )}
                    <Field label="Name" type="text" value={form.data.name} onChange={(value) => form.setData('name', value)} autoComplete="name" autoFocus />
                    <Field label="Email" type="email" value={form.data.email} onChange={(value) => form.setData('email', value)} autoComplete="email" />
                    <Field label="Password" type="password" value={form.data.password} onChange={(value) => form.setData('password', value)} autoComplete="new-password" />
                    <Field label="Confirm password" type="password" value={form.data.password_confirmation} onChange={(value) => form.setData('password_confirmation', value)} autoComplete="new-password" />
                    {firstError && <p className="form-error">{firstError}</p>}
                    <button className="button button-wide auth-submit" type="submit" disabled={form.processing}>{trialOffer ? 'Start my trial' : 'Create my account'} <span>→</span></button>
                    <p>Already have an account? <Link href="/login">Log in</Link></p>
                </form>
            </section>
        </SiteLayout>
    );
}

function Field({ label, value, onChange, ...props }) {
    return <label>{label}<input {...props} value={value} onChange={(event) => onChange(event.target.value)} required /></label>;
}
