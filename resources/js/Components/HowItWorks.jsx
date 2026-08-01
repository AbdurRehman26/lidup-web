import ClaudeIcon from '@lobehub/icons/es/Claude/components/Color.js';
import CodexIcon from '@lobehub/icons/es/Codex/components/Color.js';
import CursorIcon from '@lobehub/icons/es/Cursor/components/Mono.js';
import GeminiIcon from '@lobehub/icons/es/Gemini/components/Color.js';
import GithubCopilotIcon from '@lobehub/icons/es/GithubCopilot/components/Mono.js';
import OpenCodeIcon from '@lobehub/icons/es/OpenCode/components/Mono.js';

const supportedAgents = [
    { name: 'Codex', maker: 'OpenAI', icon: CodexIcon, className: 'codex' },
    { name: 'Claude AI', maker: 'Anthropic', icon: ClaudeIcon, className: 'claude' },
    { name: 'OpenCode', maker: 'Open source', icon: OpenCodeIcon, className: 'opencode' },
    { name: 'Cursor', maker: 'Anysphere', icon: CursorIcon, className: 'cursor' },
    { name: 'Gemini', maker: 'Google', icon: GeminiIcon, className: 'gemini' },
    { name: 'GitHub Copilot', maker: 'GitHub', icon: GithubCopilotIcon, className: 'copilot' },
];

export default function HowItWorks({ action = null, id = 'how-it-works', className = '', showHeading = true, cardsFirst = false }) {
    const agentSupport = (
        <div className="agent-support">
                <div className="agent-support-head">
                    <div><span className="support-light" />Supported AI agents</div>
                    <p>Keep the coding agent you already use running while your Mac is closed.</p>
                </div>
                <div className="agent-grid">
                    {supportedAgents.map(({ name, maker, icon: Icon, className: agentClassName }) => (
                        <article className={`agent-card ${agentClassName}`} key={name}>
                            <span className="agent-icon"><Icon size={30} /></span>
                            <span><b>{name}</b><small>{maker}</small></span>
                            <i aria-label="Supported" title="Supported">✓</i>
                        </article>
                    ))}
                </div>
        </div>
    );

    const featureCards = (
        <div className="workflow-grid">
            <Feature icon="⌁" title="Choose what matters">Pick an active process or create a rule for the tools you trust.</Feature>
            <Feature icon="▱" title="Close the lid">LidUp keeps your selected work active while the display stays off.</Feature>
            <Feature icon="✓" title="Come back to done">Get one quiet notification, then let your Mac sleep normally again.</Feature>
        </div>
    );

    return (
        <section id={id} className={`workflow-section ${className}`.trim()}>
            {showHeading && (
                <div className="section-heading">
                    <p className="kicker">Effortless by design</p>
                    <h2>Three steps. Then forget about it.</h2>
                </div>
            )}

            {cardsFirst ? featureCards : agentSupport}
            {action && <div className="workflow-action">{action}</div>}
            {cardsFirst ? agentSupport : featureCards}
        </section>
    );
}

function Feature({ icon, title, children }) {
    return <article><div className="feature-icon">{icon}</div><h3>{title}</h3><p>{children}</p></article>;
}
