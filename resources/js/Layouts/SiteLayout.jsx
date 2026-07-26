import { Link, router, usePage } from '@inertiajs/react';
import Brand from '../Components/Brand';

export default function SiteLayout({ children }) {
    const { auth } = usePage().props;

    const logout = () => router.post('/logout');

    return (
        <>
            <a className="skip-link" href="#content">Skip to content</a>
            <header className="site-header">
                <Link href="/" aria-label="LidUp home"><Brand /></Link>
                <nav aria-label="Main navigation">
                    <Link href="/download">Download</Link>
                    <Link href="/#how-it-works">How it works</Link>
                    <Link href="/#pricing">Pricing</Link>
                    {auth.user ? (
                        <>
                            <Link href="/dashboard">Dashboard</Link>
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
                <p>Made for work that takes longer than your attention span.</p>
                <p className="mono">© {new Date().getFullYear()} LidUp</p>
            </footer>
        </>
    );
}
