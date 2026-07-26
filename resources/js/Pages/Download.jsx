import { Head, Link } from '@inertiajs/react';
import SiteLayout from '../Layouts/SiteLayout';

export default function Download({ release }) {
    return (
        <SiteLayout>
            <Head title="Download LidUp" />
            <section className="download-shell">
                <div className="download-heading">
                    <img src="/app-icon.png" alt="LidUp" />
                    <p className="kicker">LidUp for macOS</p>
                    <h1>Download LidUp</h1>
                    <p>Start free. Choose a plan only when you’re ready to keep using it.</p>
                </div>

                <div className="download-card">
                    <div className="release-info">
                        <div className="release-platform"><span className="apple-logo">●</span><div><strong>macOS universal</strong><small>Apple silicon and Intel</small></div></div>
                        <div><strong>Version {release.version}</strong><small>{release.size}</small></div>
                    </div>
                    {release.available ? (
                        <a className="button button-wide" href={release.url}>Download free <span>↓</span></a>
                    ) : (
                        <button className="button button-wide" type="button" disabled>Free beta download coming soon</button>
                    )}
                    <p className="download-note">{release.available ? 'No account or credit card required.' : 'The signed installer will appear here as soon as the first beta build is ready. No login will be required.'}</p>
                </div>

                <p className="optional-account">An account is optional and only needed to manage a paid plan. <Link href="/register">Create an account</Link></p>

                <div className="requirements-grid">
                    <article><span>◫</span><div><b>{release.minimum_os} or later</b><p>Designed for current macOS security and power-management APIs.</p></div></article>
                    <article><span>⌘</span><div><b>Universal binary</b><p>Runs natively on Apple silicon and Intel-based Macs.</p></div></article>
                    <article><span>↻</span><div><b>Automatic updates</b><p>Signed updates arrive quietly through the app.</p></div></article>
                </div>

                <div className="install-steps">
                    <h2>Install in under a minute</h2>
                    <ol><li><b>1</b><span>Download and open the LidUp disk image.</span></li><li><b>2</b><span>Drag LidUp into your Applications folder.</span></li><li><b>3</b><span>Open LidUp and allow the requested macOS permission.</span></li></ol>
                </div>
            </section>
        </SiteLayout>
    );
}
