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
                <Link href="/" aria-label="LidUp home"><Brand /></Link>
                <nav aria-label="Main navigation">
                    <Link href="/download">Download</Link>
                    <Link href="/#how-it-works">How it works</Link>
                    <Link href="/#pricing">Pricing</Link>
                    <Link href="/faqs">FAQs</Link>
                    <button className="theme-toggle" type="button" onClick={toggleTheme} aria-label={`Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`} aria-pressed={theme === 'dark'}>
                        <span className="theme-toggle-track" aria-hidden="true"><i className="theme-sun">☀</i><i className="theme-moon">☾</i><b /></span>
                    </button>
                    {auth.user ? (
                        <>
                            <Link href={auth.user.email_verified_at ? '/dashboard' : '/email/verify'}>{auth.user.email_verified_at ? 'Dashboard' : 'Verify email'}</Link>
                            <button className="nav-button" type="button" onClick={logout}>Log out</button>
                        </>
                    ) : (
                        <>
                            <Link href="/login">Log in</Link>
                            <Link className="button button-small" href="/register">Get early access</Link>
                        </>
                    )}
                </nav>
            </header>

            <main id="content">{children}</main>

            <footer className="site-footer">
                <Brand />
                <p>Lidup your Mac.</p>
                <div className="footer-links"><Link href="/faqs">FAQs</Link><p className="mono">© {new Date().getFullYear()} LidUp</p></div>
            </footer>
        </>
    );
}
