import { useState } from 'react';

export default function ProductStage() {
    const [paused, setPaused] = useState(false);

    return (
        <div className="product-stage reveal reveal-delay" aria-label="LidUp product preview">
            <div className="hero-glow" />
            <img className="hero-app-icon" src="/app-icon.png" alt="LidUp app icon" />
            <div className="menu-popover">
                <div className="popover-title">
                    <span><img src="/app-icon.png" alt="" /> LidUp</span>
                    <span className={`status-pill${paused ? ' paused' : ''}`}>{paused ? 'Paused' : 'Active'}</span>
                </div>
                <div className="session-card">
                    <div className="session-orb"><span /></div>
                    <div className="session-copy">
                        <strong>{paused ? 'Protection paused' : 'Your Mac can keep working'}</strong>
                        <span>{paused ? 'Resume before closing the lid' : '2 active processes · 42 min'}</span>
                    </div>
                </div>
                <div className="process-list">
                    <TaskRow icon="⌘" title="Codex" detail="Running" />
                    <TaskRow icon="›_" title="Release build" detail="18 min" />
                </div>
                <button className="protection-toggle" type="button" onClick={() => setPaused((value) => !value)}>
                    {paused ? 'Resume protection' : 'Pause protection'}
                </button>
                <div className="popover-foot"><span>Launch at login</span><span>⌘,</span></div>
            </div>
            <div className="menu-bar-chip"><img src="/app-icon.png" alt="" /><span>Protected</span><b>42m</b></div>
        </div>
    );
}

function TaskRow({ icon, title, detail }) {
    return (
        <div className="task-row">
            <span className="task-icon">{icon}</span>
            <b>{title}</b>
            <span className="task-state">{detail}</span>
        </div>
    );
}
