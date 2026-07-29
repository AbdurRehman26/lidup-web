import { Head, Link } from '@inertiajs/react';
import SiteLayout from '../Layouts/SiteLayout';

const groups = [
    {
        label: 'Using LidUp',
        questions: [
            ['What does LidUp do?', 'LidUp keeps the work you choose running when you step away from your Mac. It is designed for builds, coding agents, exports, and other long-running tasks that should not be interrupted by sleep.'],
            ['Can I close my MacBook lid?', 'Yes. Start protection for the process you care about, then close the lid. LidUp shows its current state in the menu bar and restores normal sleep behavior when the protected work finishes.'],
            ['What happens when the task finishes?', 'LidUp stops protecting the session, restores your normal sleep settings, and can send a quiet macOS notification so you know the work is done.'],
            ['Which versions of macOS are supported?', 'The current LidUp release requires macOS 14 Sonoma or later. Each download also shows its minimum supported version before installation.'],
        ],
    },
    {
        label: 'Privacy & battery',
        questions: [
            ['Does LidUp upload my processes or work?', 'No. Process monitoring happens on your Mac. LidUp only contacts the service for account, license activation, subscription, and update checks.'],
            ['Will it drain my battery?', 'Keeping work active uses more energy than sleep, so LidUp includes battery-aware safeguards. You remain in control of which work is protected and can stop protection at any time.'],
            ['Does LidUp permanently change system settings?', 'No. LidUp applies protection only while it is needed, then returns your Mac to its normal behavior.'],
        ],
    },
    {
        label: 'Plans & licenses',
        questions: [
            ['Can I download LidUp without an account?', 'Yes. The latest macOS build is a free public download. An account and activation key are only needed when you activate paid features.'],
            ['How many Macs can I activate?', 'The Personal plan supports one active Mac. The Pro plan supports up to three active Macs. You can remove an old device from your account dashboard.'],
            ['Where do I find my activation key?', 'Sign in and open your dashboard. For security, a full key is shown only when it is first created or regenerated.'],
            ['How do I change or cancel my plan?', 'Open Manage subscription from your dashboard. Plan changes and payments are handled securely by Paddle. A cancellation keeps access available through the end of the paid billing period.'],
        ],
    },
];

export default function Faqs() {
    return (
        <SiteLayout>
            <Head title="Frequently asked questions" />
            <div className="faq-page">
                <header className="faq-hero reveal">
                    <div className="faq-orbit" aria-hidden="true"><img src="/app-icon.png" alt="" /><span /></div>
                    <p className="kicker">Frequently asked questions</p>
                    <h1>Lidup your Mac.<br /><em>We’ll explain the rest.</em></h1>
                    <p>Clear answers about keeping work alive, protecting your battery, and managing your LidUp license.</p>
                </header>

                <div className="faq-groups">
                    {groups.map((group) => (
                        <section className="faq-group" key={group.label}>
                            <div className="faq-group-label"><span />{group.label}</div>
                            <div className="faq-list">
                                {group.questions.map(([question, answer]) => (
                                    <details key={question}>
                                        <summary><span>{question}</span><i aria-hidden="true" /></summary>
                                        <p>{answer}</p>
                                    </details>
                                ))}
                            </div>
                        </section>
                    ))}
                </div>

                <section className="faq-support">
                    <div><p className="kicker">Still curious?</p><h2>Try LidUp on your Mac.</h2><p>The download is free and does not require an account.</p></div>
                    <Link className="button" href="/download">Download for Mac</Link>
                </section>
            </div>
        </SiteLayout>
    );
}
