import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import SiteLayout from '../Layouts/SiteLayout';

const types = [
    { value: 'feature', label: 'Request a feature', short: 'Feature', hint: 'What would make LidUp better?' },
    { value: 'problem', label: 'Report a problem', short: 'Problem', hint: 'Tell us what is getting in your way.' },
    { value: 'review', label: 'Add a review', short: 'Review', hint: 'Share your experience with LidUp.' },
];

const statuses = { submitted: 'Pending approval', in_review: 'In review', planned: 'Planned', completed: 'Completed', declined: 'Declined' };

export default function Roadmap({ items = [], reviews = [], filters = {} }) {
    const { auth, flash } = usePage().props;
    const params = useMemo(() => new URLSearchParams(window.location.search), []);
    const initialType = types.some(({ value }) => value === params.get('compose')) ? params.get('compose') : 'feature';
    const [view, setView] = useState(params.get('view') === 'reviews' ? 'reviews' : 'roadmap');
    const form = useForm({ type: initialType, title: '', description: '', rating: 5, submitter_name: '', submitter_email: '' });

    useEffect(() => {
        if (params.get('compose')) document.getElementById('feedback-composer')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, [params]);

    const submit = (event) => {
        event.preventDefault();
        form.post('/roadmap', {
            preserveScroll: true,
            onSuccess: () => form.reset('title', 'description', 'submitter_name', 'submitter_email'),
        });
    };

    const vote = (item) => {
        if (!auth.user) return router.visit('/login');
        router.post(`/roadmap/${item.id}/vote`, {}, { preserveScroll: true });
    };

    return (
        <SiteLayout>
            <Head title="Roadmap & feedback" />
            <div className="roadmap-page">
                <header className="roadmap-hero">
                    <div>
                        <p className="eyebrow">Built with you</p>
                        <h1>Your signal.<br /><em>Our roadmap.</em></h1>
                    </div>
                    <p>Vote on what matters, request what is missing, or tell us where LidUp can do better.</p>
                </header>

                <div className="roadmap-tabs" role="tablist" aria-label="Feedback views">
                    <button className={view === 'roadmap' ? 'is-active' : ''} onClick={() => setView('roadmap')} type="button">Roadmap <span>{items.length}</span></button>
                    <button className={view === 'reviews' ? 'is-active' : ''} onClick={() => setView('reviews')} type="button">Reviews <span>{reviews.length}</span></button>
                </div>

                <div className="roadmap-layout">
                    <section className="roadmap-board" aria-live="polite">
                        {view === 'roadmap' ? (
                            <>
                                <header className="roadmap-board-tools">
                                    <div>
                                        <Link className={!filters.status ? 'is-active' : ''} href="/roadmap">All</Link>
                                        {['in_review', 'planned', 'completed'].map((status) => <Link className={filters.status === status ? 'is-active' : ''} href={`/roadmap?status=${status}&sort=${filters.sort}`} key={status}>{statuses[status]}</Link>)}
                                    </div>
                                    <div><Link className={filters.sort !== 'new' ? 'is-active' : ''} href={`/roadmap?sort=top${filters.status ? `&status=${filters.status}` : ''}`}>Top</Link><Link className={filters.sort === 'new' ? 'is-active' : ''} href={`/roadmap?sort=new${filters.status ? `&status=${filters.status}` : ''}`}>New</Link></div>
                                </header>
                                <div className="roadmap-items">
                                    {items.length ? items.map((item) => (
                                        <article className="roadmap-item" key={item.id}>
                                            <button className={item.has_voted ? 'roadmap-vote is-voted' : 'roadmap-vote'} onClick={() => vote(item)} type="button" aria-label={`${item.has_voted ? 'Remove vote from' : 'Vote for'} ${item.title}`}><span>⌃</span><b>{item.votes_count}</b></button>
                                            <div>
                                                <div className="roadmap-item-meta"><span className={`feedback-type is-${item.type}`}>{item.type === 'problem' ? 'Problem' : 'Feature'}</span><span className={`roadmap-status is-${item.status}`}>{statuses[item.status] ?? item.status}</span>{item.is_own && !item.is_public && <span className="roadmap-private">Only visible to you</span>}<time>{relativeDate(item.created_at)}</time></div>
                                                <h2>{item.title}</h2>
                                                <p>{item.description}</p>
                                                {item.admin_response && <blockquote><b>LidUp team</b>{item.admin_response}</blockquote>}
                                            </div>
                                        </article>
                                    )) : <EmptyState label="No public requests in this lane yet." />}
                                </div>
                            </>
                        ) : (
                            <div className="review-grid">
                                {reviews.length ? reviews.map((review) => (
                                    <article className="review-card" key={review.id}><div aria-label={`${review.rating} out of 5 stars`}>{'★'.repeat(review.rating)}</div>{review.is_own && !review.is_public && <span className="roadmap-private">Pending approval · only visible to you</span>}<h2>{review.title}</h2><p>{review.description}</p><footer><span>{review.submitter_name || 'LidUp user'}</span><time>{relativeDate(review.created_at)}</time></footer></article>
                                )) : <EmptyState label="Be the first to share a LidUp review." />}
                            </div>
                        )}
                    </section>

                    <aside className="feedback-composer" id="feedback-composer">
                        <div className="composer-orbit" aria-hidden="true"><span>+</span></div>
                        <p className="eyebrow">Send a signal</p>
                        <h2>{types.find(({ value }) => value === form.data.type)?.label}</h2>
                        <p>{types.find(({ value }) => value === form.data.type)?.hint}</p>
                        {flash.feedback_message && <div className="feedback-success">{flash.feedback_message}</div>}
                        <form onSubmit={submit}>
                            <div className="feedback-type-picker">
                                {types.map((type) => <button className={form.data.type === type.value ? 'is-active' : ''} onClick={() => form.setData('type', type.value)} type="button" key={type.value}>{type.short}</button>)}
                            </div>
                            {form.data.type === 'review' && <label>Rating<select value={form.data.rating} onChange={(event) => form.setData('rating', Number(event.target.value))}>{[5, 4, 3, 2, 1].map((rating) => <option value={rating} key={rating}>{rating} stars</option>)}</select></label>}
                            <label>Title<input value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} placeholder={form.data.type === 'review' ? 'A short headline' : 'Describe the idea briefly'} maxLength="160" required /></label>
                            {form.errors.title && <p className="form-error">{form.errors.title}</p>}
                            <label>Details<textarea value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} placeholder="Give us enough detail to understand the signal…" rows="6" required /></label>
                            {form.errors.description && <p className="form-error">{form.errors.description}</p>}
                            {!auth.user && <div className="guest-fields"><label>Name<input value={form.data.submitter_name} onChange={(event) => form.setData('submitter_name', event.target.value)} required /></label><label>Email<input type="email" value={form.data.submitter_email} onChange={(event) => form.setData('submitter_email', event.target.value)} required /></label></div>}
                            {(form.errors.submitter_name || form.errors.submitter_email) && <p className="form-error">Please provide a valid name and email.</p>}
                            <button className="button feedback-submit" disabled={form.processing} type="submit">{form.processing ? 'Sending…' : 'Send to LidUp'} <span>↗</span></button>
                            <small>Submissions are reviewed before appearing publicly.</small>
                        </form>
                    </aside>
                </div>
            </div>
        </SiteLayout>
    );
}

function EmptyState({ label }) { return <div className="roadmap-empty"><span>✦</span><p>{label}</p></div>; }
function relativeDate(value) { const days = Math.floor((Date.now() - new Date(value).getTime()) / 86400000); return days < 1 ? 'Today' : days === 1 ? 'Yesterday' : `${days} days ago`; }
