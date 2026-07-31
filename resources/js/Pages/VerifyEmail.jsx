import { Head, router } from '@inertiajs/react';
import SiteLayout from '../Layouts/SiteLayout';

export default function VerifyEmail({ email, status }) {
    const resend = () => router.post('/email/verification-notification', {}, { preserveScroll: true });

    return (
        <SiteLayout>
            <Head title="Verify your email" />
            <section className="verify-email-shell">
                <div className="verify-email-card">
                    <div className="verify-email-mark"><span>✉</span><i /></div>
                    <p className="eyebrow">One last step</p>
                    <h1>Check your inbox.</h1>
                    <p>We sent a secure verification link to <strong>{email}</strong>. Confirm your email, then generate an activation key to claim early-bird access.</p>
                    {status === 'verification-link-sent' && <div className="verification-sent">A fresh verification link has been sent.</div>}
                    {status?.startsWith('account-created') && <div className="verification-sent">Your account is ready. Verify your email to continue.</div>}
                    <button className="button button-wide" type="button" onClick={resend}>Resend verification email <span>→</span></button>
                    <small>The link is signed and expires automatically. Check your spam folder if it doesn’t arrive.</small>
                </div>
            </section>
        </SiteLayout>
    );
}
