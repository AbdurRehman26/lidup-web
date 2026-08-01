import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Brand from '../Components/Brand';

export default function SiteLayout({ children }) {
    const { auth } = usePage().props;
    const [theme, setTheme] = useState(() => document.documentElement.dataset.theme || 'light');

    const logout = () => router.post('/logout');
    const toggleTheme = () => setTheme((current) => current === 'dark' ? 'light' : 'dark');

    useEffect(() => {
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('lidup-theme', theme);
        document.querySelector('meta[name="theme-color"]')
            ?.setAttribute('content', theme === 'dark' ? '#100d17' : '#f8f6ff');
    }, [theme]);

    return (
        <>
            <a className="skip-link" href="#content">Skip to content</a>
            <header className="site-header">
                <div className="site-header-left">
                    <Link href="/" aria-label="LidUp home"><Brand /></Link>
                    <nav className="site-nav-left" aria-label="Main navigation">
                        <Link href="/download">Download</Link>
                        <Link href="/#how-it-works">How it works</Link>
                        <Link href="/#pricing">Pricing</Link>
                        <Link href="/roadmap">Roadmap</Link>
                    </nav>
                </div>
                <nav className="site-nav-right" aria-label="Account navigation">
                    <button className="theme-toggle" type="button" onClick={toggleTheme} aria-label={`Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`} aria-pressed={theme === 'dark'}>
                        <span className="theme-toggle-track" aria-hidden="true"><i className="theme-sun">☀</i><i className="theme-moon">☾</i><b /></span>
                    </button>
                    {auth.user ? (
                        <>
                            <Link href={auth.user.email_verified_at ? '/dashboard' : '/email/verify'}>{auth.user.email_verified_at ? 'Dashboard' : 'Verify email'}</Link>
                            <button className="nav-button" type="button" onClick={logout}>Log out</button>
                        </>
                    ) : (
                        <Link href="/login">Log in</Link>
                    )}
                </nav>
            </header>

            <main id="content">{children}</main>

            <footer className="site-footer">
                <div className="footer-brand"><Brand /><p>Lidup your Mac.</p></div>
                <div className="footer-links" aria-label="Support links">
                    <Link href="/roadmap?compose=review">Add a review</Link>
                    <Link href="/roadmap?compose=feature">Request a feature</Link>
                    <Link href="/roadmap?compose=problem">Report a problem</Link>
                    <Link href="/faqs">FAQs</Link>
                </div>
                <p className="mono">© {new Date().getFullYear()} LidUp</p>
            </footer>
        </>
    );
}
