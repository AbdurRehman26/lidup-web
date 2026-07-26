import { Head, Link, useForm } from '@inertiajs/react';
import SiteLayout from '../Layouts/SiteLayout';

export default function Register({ plans, selectedPlan }) {
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
                <div className="auth-copy"><p className="eyebrow">Private beta</p><h1>Close the lid.<br /><em>Come back to done.</em></h1><p>Create your account and start a 14-day early-access trial. No credit card required.</p></div>
                <form onSubmit={submit} className="auth-card">
                    <h2>Create your account</h2>
                    <div className="plan-picker" aria-label="Choose a plan">
                        {Object.entries(plans).map(([key, plan]) => (
                            <button className={form.data.plan === key ? 'selected' : ''} type="button" key={key} onClick={() => form.setData('plan', key)}>
                                <span>{plan.name}</span><b>${plan.price}<small>/mo</small></b>
                            </button>
                        ))}
                    </div>
                    <Field label="Name" type="text" value={form.data.name} onChange={(value) => form.setData('name', value)} autoComplete="name" autoFocus />
                    <Field label="Email" type="email" value={form.data.email} onChange={(value) => form.setData('email', value)} autoComplete="email" />
                    <Field label="Password" type="password" value={form.data.password} onChange={(value) => form.setData('password', value)} autoComplete="new-password" />
                    <Field label="Confirm password" type="password" value={form.data.password_confirmation} onChange={(value) => form.setData('password_confirmation', value)} autoComplete="new-password" />
                    {firstError && <p className="form-error">{firstError}</p>}
                    <button className="button button-wide" type="submit" disabled={form.processing}>Start my trial <span>→</span></button>
                    <p>Already have an account? <Link href="/login">Log in</Link></p>
                </form>
            </section>
        </SiteLayout>
    );
}

function Field({ label, value, onChange, ...props }) {
    return <label>{label}<input {...props} value={value} onChange={(event) => onChange(event.target.value)} required /></label>;
}
