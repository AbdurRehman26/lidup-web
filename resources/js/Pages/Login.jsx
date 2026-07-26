import { Head, Link, useForm } from '@inertiajs/react';
import SiteLayout from '../Layouts/SiteLayout';

export default function Login() {
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (event) => {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    };

    return (
        <SiteLayout>
            <Head title="Log in" />
            <section className="auth-shell">
                <div className="auth-copy"><p className="eyebrow">Welcome back</p><h1>Pick up where<br /><em>you left off.</em></h1><p>Manage your plan, read product updates, and download the latest LidUp build.</p></div>
                <form onSubmit={submit} className="auth-card">
                    <h2>Log in</h2>
                    <label>Email<input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} autoComplete="email" required autoFocus /></label>
                    <label>Password<input type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} autoComplete="current-password" required /></label>
                    <label className="checkbox"><input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} /> Keep me logged in</label>
                    {form.errors.email && <p className="form-error">{form.errors.email}</p>}
                    <button className="button button-wide" type="submit" disabled={form.processing}>Log in <span>→</span></button>
                    <p>New here? <Link href="/register">Create an account</Link></p>
                </form>
            </section>
        </SiteLayout>
    );
}
