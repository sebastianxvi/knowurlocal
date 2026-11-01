@extends('layouts.admin')

@section('title', 'Service Evaluations')
@section('page-title', 'Service Evaluations')
@section('page-subtitle', 'Review public feedback and respond on behalf of your agency.')

@section('content')
<div class="admin-page evaluation-admin-page">
    <div class="admin-stats-grid">
        <div class="admin-stat-card"><span>Total evaluations</span><strong>{{ number_format($stats->total) }}</strong></div>
        <div class="admin-stat-card"><span>Average satisfaction</span><strong>{{ number_format((float) $stats->average, 1) }} <small>/ 5</small></strong></div>
        <div class="admin-stat-card"><span>Awaiting response</span><strong>{{ number_format($unanswered) }}</strong></div>
    </div>

    <section class="admin-card evaluation-distribution">
        <div class="admin-card-heading"><div><h2>Rating distribution</h2><p>Published evaluations for {{ $agency->agency_name }}.</p></div></div>
        @foreach(range(5, 1) as $rating)
            @php($count = (int) ($distribution[$rating] ?? 0))
            <div class="evaluation-bar-row"><span>{{ $rating }} <i class="ph-fill ph-star"></i></span><div class="evaluation-bar"><span style="width: {{ $stats->total ? ($count / $stats->total * 100) : 0 }}%"></span></div><strong>{{ $count }}</strong></div>
        @endforeach
    </section>

    <section class="admin-card">
        <div class="admin-card-heading evaluation-list-heading">
            <div><h2>Public evaluations</h2><p>Responses are optional and become visible to public users when published.</p></div>
            <span class="admin-count-pill">{{ $evaluations->total() }} {{ $filter === 'all' ? 'total' : 'matching' }}</span>
        </div>

        <nav class="evaluation-admin-filters" aria-label="Filter evaluations">
            <a href="{{ route('agency-admin.evaluations', ['filter' => 'all']) }}" @class(['is-active' => $filter === 'all']) aria-current="{{ $filter === 'all' ? 'page' : 'false' }}">All <span>{{ number_format($stats->total) }}</span></a>
            <a href="{{ route('agency-admin.evaluations', ['filter' => 'unanswered']) }}" @class(['is-active' => $filter === 'unanswered']) aria-current="{{ $filter === 'unanswered' ? 'page' : 'false' }}">Unanswered <span>{{ number_format($unanswered) }}</span></a>
            <a href="{{ route('agency-admin.evaluations', ['filter' => 'responded']) }}" @class(['is-active' => $filter === 'responded']) aria-current="{{ $filter === 'responded' ? 'page' : 'false' }}">Responded <span>{{ number_format($responded) }}</span></a>
        </nav>

        <div class="evaluation-admin-list">
            @forelse($evaluations as $evaluation)
                <article class="evaluation-admin-item">
                    <div class="evaluation-admin-item-head">
                        <div><strong>{{ $evaluation->masked_reviewer_name ?? 'Public User' }}</strong><span class="evaluation-admin-date">{{ $evaluation->created_at->format('M d, Y') }}</span></div>
                        <span class="evaluation-admin-stars" aria-label="{{ $evaluation->rating }} out of 5 stars">{{ str_repeat('★', $evaluation->rating) }}{{ str_repeat('☆', 5 - $evaluation->rating) }}</span>
                    </div>

                    <p class="evaluation-admin-comment">{{ $evaluation->comment ?: 'No additional comment provided.' }}</p>

                    <div class="evaluation-admin-meta">
                        <span class="evaluation-admin-helpful"><i class="ph-light ph-thumbs-up" aria-hidden="true"></i>{{ number_format((int) $evaluation->helpful_votes_count) }} {{ (int) $evaluation->helpful_votes_count === 1 ? 'person' : 'people' }} found this helpful</span>
                        @unless($evaluation->response)
                            <div class="evaluation-admin-actions" id="response-actions-{{ $evaluation->id }}">
                                <button type="button" class="btn btn-primary evaluation-compose-toggle" data-target="response-form-{{ $evaluation->id }}"><i class="ph-light ph-chat-circle" aria-hidden="true"></i> Reply</button>
                            </div>
                        @endunless
                    </div>

                    @if($evaluation->response)
                        <div class="evaluation-admin-response" id="response-card-{{ $evaluation->id }}">
                            <div class="evaluation-admin-response-heading">
                                <strong>{{ $evaluation->response->responder_role === 'agency_admin' ? 'Official Agency Response' : 'Official System Response' }}</strong>
                                <div class="evaluation-admin-response-heading-actions">
                                    <small>{{ $evaluation->response->updated_at?->format('M d, Y') }}</small>
                                    <div class="evaluation-response-menu">
                                        <button type="button" class="evaluation-response-menu-trigger" aria-label="Response actions" aria-haspopup="true" aria-expanded="false">
                                            <i class="ph-light ph-dots-three" aria-hidden="true"></i>
                                        </button>
                                        <div class="evaluation-response-menu-panel" role="menu">
                                            <button type="button" class="evaluation-response-menu-item evaluation-compose-toggle" data-target="response-form-{{ $evaluation->id }}" role="menuitem">
                                                <i class="ph-light ph-pencil-simple" aria-hidden="true"></i> Edit response
                                            </button>
                                            <button type="button" class="evaluation-response-menu-item is-danger evaluation-delete-response-trigger" data-form="delete-response-{{ $evaluation->id }}" role="menuitem">
                                                <i class="ph-light ph-trash" aria-hidden="true"></i> Delete response
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="evaluation-admin-response-content">
                                <p>{{ $evaluation->response->body }}</p>
                                <small>Published by {{ $evaluation->response->responder_role === 'agency_admin' ? 'Agency Administrator' : 'System Administrator' }}</small>
                            </div>
                            <form id="response-form-{{ $evaluation->id }}" method="POST" action="{{ route('agency-admin.evaluations.respond', $evaluation->id) }}" class="evaluation-response-form evaluation-response-inline-form" hidden>
                                @csrf
                                <label for="response-{{ $evaluation->id }}">Edit your official response</label>
                                <textarea id="response-{{ $evaluation->id }}" name="body" rows="3" maxlength="2000" required placeholder="Acknowledge the feedback and explain any follow-up...">{{ old('body', $evaluation->response->body) }}</textarea>
                                <div class="evaluation-response-form-actions">
                                    <button type="button" class="btn btn-cancel evaluation-compose-cancel">Cancel</button>
                                    <button type="submit" class="btn btn-primary"><i class="ph-light ph-paper-plane-tilt" aria-hidden="true"></i><span>Save changes</span></button>
                                </div>
                            </form>
                            <form id="delete-response-{{ $evaluation->id }}" method="POST" action="{{ route('agency-admin.evaluations.responses.delete', $evaluation->response) }}" class="evaluation-delete-response-form">
                                @csrf @method('DELETE')
                            </form>
                        </div>
                    @else
                        <form id="response-form-{{ $evaluation->id }}" method="POST" action="{{ route('agency-admin.evaluations.respond', $evaluation->id) }}" class="evaluation-response-form" hidden>
                            @csrf
                            <label for="response-{{ $evaluation->id }}">Write an official response</label>
                            <textarea id="response-{{ $evaluation->id }}" name="body" rows="3" maxlength="2000" required placeholder="Acknowledge the feedback and explain any follow-up...">{{ old('body') }}</textarea>
                            <div class="evaluation-response-form-actions">
                                <button type="button" class="btn btn-cancel evaluation-compose-cancel">Cancel</button>
                                <button type="submit" class="btn btn-primary"><i class="ph-light ph-paper-plane-tilt" aria-hidden="true"></i><span>Publish response</span></button>
                            </div>
                        </form>
                    @endif
                </article>
            @empty
                <div class="admin-empty-state"><i class="ph-light ph-chat-circle-text"></i><h3>{{ $filter === 'all' ? 'No evaluations yet' : 'No matching evaluations' }}</h3><p>{{ $filter === 'all' ? 'Public feedback for your agency will appear here.' : 'There are no evaluations in this response category.' }}</p></div>
            @endforelse
        </div>
        <div class="admin-pagination">{{ $evaluations->links() }}</div>
    </section>
</div>
@endsection

@push('styles')
<style>
.evaluation-admin-page{display:grid;gap:18px}
.admin-stats-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.admin-stat-card,.admin-card{background:var(--surface,#fff);border:1px solid var(--border-color,#e5eaf1);border-radius:14px;padding:18px}
.admin-stat-card span{display:block;color:var(--text-muted,#64748b);font-size:13px}
.admin-stat-card strong{display:block;font-size:26px;margin-top:8px}
.admin-stat-card small{font-size:13px;color:#64748b}
.admin-card-heading,.evaluation-admin-item-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.admin-card-heading h2{font-size:17px;margin:0}
.admin-card-heading p{color:#64748b;margin:5px 0 16px;font-size:13px}
.evaluation-bar-row{display:grid;grid-template-columns:40px 1fr 35px;gap:10px;align-items:center;margin:9px 0}
.evaluation-bar-row>span{font-size:13px}
.evaluation-bar-row i,.evaluation-admin-stars{color:#e9a52e}
.evaluation-bar{height:8px;border-radius:99px;background:#edf1f6;overflow:hidden}
.evaluation-bar span{display:block;height:100%;background:#e9a52e;border-radius:99px}
.evaluation-admin-filters{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 8px}
.evaluation-admin-filters a{display:inline-flex;align-items:center;gap:8px;padding:7px 11px;border:1px solid #e1e7ef;border-radius:999px;color:#475569;text-decoration:none;font-size:12px;font-weight:600;background:#fff;transition:.18s ease}
.evaluation-admin-filters a:hover{border-color:#b9c9df;background:#f8fafc}
.evaluation-admin-filters a.is-active{color:#1d4ed8;background:#eff6ff;border-color:#bfdbfe}
.evaluation-admin-filters a span{font-variant-numeric:tabular-nums;color:inherit;opacity:.8}
.evaluation-admin-item{padding:18px 0;border-bottom:1px solid #edf0f4}
.evaluation-admin-item:first-child{padding-top:0}
.evaluation-admin-item-head strong{font-size:14px}
.evaluation-admin-date{display:block;color:#8491a4;font-size:12px;margin-top:3px}
.evaluation-admin-stars{white-space:nowrap}
.evaluation-admin-comment{white-space:pre-wrap;line-height:1.55;margin:12px 0;color:#334155}
.evaluation-admin-meta{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:10px 0 12px}
.evaluation-admin-helpful{display:inline-flex;align-items:center;gap:6px;color:#718096;font-size:12px}
.evaluation-admin-helpful i{font-size:15px}
.evaluation-response-status{font-size:11px;font-weight:600;padding:4px 8px;border-radius:999px}
.evaluation-response-status.is-responded{background:#ecfdf3;color:#15803d}
.evaluation-response-status.is-pending{background:#fff7ed;color:#b45309}
.evaluation-admin-response{background:#f4f8fc;border:1px solid #e1eaf4;border-radius:10px;padding:12px;margin:12px 0}
.evaluation-admin-response p{margin:7px 0;white-space:pre-wrap;line-height:1.5;color:#334155}
.evaluation-admin-response small{color:#64748b;font-size:12px}
.evaluation-admin-response-heading{display:flex;align-items:center;justify-content:space-between;gap:10px}
.evaluation-admin-actions{display:flex;justify-content:flex-end;align-items:center;gap:8px;margin-top:12px;flex-wrap:wrap}
.evaluation-admin-actions form{margin:0}
.evaluation-admin-actions .btn,
.evaluation-response-form-actions .btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    min-height:42px !important;
    padding:10px 16px !important;
    border-radius:9px !important;
    font-size:13px !important;
    font-weight:600 !important;
    line-height:1.2;
    white-space:nowrap;
    cursor:pointer;
    transition:background-color .16s ease,border-color .16s ease,box-shadow .16s ease,transform .16s ease;
}
.evaluation-admin-actions .btn i,
.evaluation-response-form-actions .btn i{
    font-size:17px;
    line-height:1;
}
.evaluation-admin-actions .btn:hover,
.evaluation-response-form-actions .btn:hover{
    transform:translateY(-1px);
}
.evaluation-admin-actions .btn:focus-visible,
.evaluation-response-form-actions .btn:focus-visible{
    outline:3px solid rgba(59,130,246,.22);
    outline-offset:2px;
}
.evaluation-admin-actions .btn:disabled,
.evaluation-response-form-actions .btn:disabled{
    opacity:.7;
    cursor:wait;
    transform:none;
}
.evaluation-admin-actions [hidden]{display:none !important}
.evaluation-response-menu{position:relative}
.evaluation-response-menu-trigger{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border:1px solid #e2e8f0;border-radius:9px;background:#fff;color:#475569;cursor:pointer;transition:background .16s,border-color .16s,color .16s}
.evaluation-response-menu-trigger:hover,.evaluation-response-menu-trigger[aria-expanded="true"]{background:#f8fafc;border-color:#cbd5e1;color:#1e293b}
.evaluation-response-menu-trigger i{font-size:20px}
.evaluation-response-menu-panel{position:absolute;right:0;top:calc(100% + 6px);z-index:20;display:none;min-width:178px;padding:5px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.12)}
.evaluation-response-menu.is-open .evaluation-response-menu-panel{display:grid}
.evaluation-response-menu-item{display:flex;align-items:center;gap:9px;width:100%;min-height:38px;padding:0 10px;border:0;border-radius:7px;background:transparent;color:#334155;text-align:left;font:inherit;font-size:13px;font-weight:500;cursor:pointer}
.evaluation-response-menu-item:hover{background:#f1f5f9}
.evaluation-response-menu-item i{font-size:17px}
.evaluation-response-menu-item.is-danger{color:#dc2626}
.evaluation-response-menu-item.is-danger:hover{background:#fef2f2}
.evaluation-admin-actions [hidden]{display:none!important}
.evaluation-response-form{display:grid;gap:12px;margin-top:14px;padding:16px;background:#f8fafc;border:1px solid #e7edf4;border-radius:11px}
.evaluation-response-form[hidden]{display:none}
.evaluation-response-form label{font-size:13px;font-weight:600;color:#334155}
.evaluation-response-form textarea{width:100%;min-height:92px;border:1px solid #d9e1eb;border-radius:9px;padding:11px;resize:vertical;background:#fff;color:#1e293b;font:inherit}
.evaluation-response-form textarea:focus{outline:2px solid #bfdbfe;border-color:#60a5fa}
.evaluation-response-form-actions{display:flex;justify-content:flex-end;align-items:center;gap:10px}
.evaluation-response-form-actions .evaluation-compose-cancel{min-width:92px}
.evaluation-response-form-actions button[type="submit"]{min-width:150px}
.evaluation-admin-response{position:relative}
.evaluation-admin-response-heading-actions{display:flex;align-items:center;gap:10px}
.evaluation-admin-response-heading-actions>small{white-space:nowrap}
.evaluation-admin-response-heading-actions .evaluation-response-menu{flex:0 0 auto}
.evaluation-admin-response.is-editing .evaluation-admin-response-heading-actions .evaluation-response-menu{visibility:hidden}
.evaluation-admin-response-content p{margin:7px 0;white-space:pre-wrap;line-height:1.5;color:#334155}
.evaluation-response-inline-form{margin:14px 0 0;padding:14px 0 0;border:0;border-top:1px solid #dce6f0;border-radius:0;background:transparent}
.evaluation-response-inline-form textarea{min-height:110px;line-height:1.5}
.evaluation-response-inline-form .evaluation-response-form-actions{padding-top:2px}
.evaluation-delete-response-form{display:none!important}
@media(max-width:700px){
    .evaluation-admin-actions .btn{min-height:44px !important;padding:11px 16px !important}
    .evaluation-response-form-actions{display:grid;grid-template-columns:1fr 1.4fr}
    .evaluation-response-form-actions .btn{width:100%;min-width:0}
}
.admin-count-pill{background:#f1f5f9;border-radius:99px;padding:5px 10px;font-size:12px;white-space:nowrap}
.admin-empty-state{text-align:center;padding:40px 15px;color:#64748b}
.admin-pagination{margin-top:16px}
@media(max-width:700px){.admin-stats-grid{grid-template-columns:1fr}.admin-card{padding:14px}.evaluation-admin-item-head{align-items:flex-start}.evaluation-admin-meta{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const closeMenus = (except = null) => {
        document.querySelectorAll('.evaluation-response-menu.is-open').forEach(menu => {
            if (menu === except) return;
            menu.classList.remove('is-open');
            menu.querySelector('.evaluation-response-menu-trigger')?.setAttribute('aria-expanded', 'false');
        });
    };

    document.addEventListener('click', event => {
        const trigger = event.target.closest('.evaluation-response-menu-trigger');
        if (trigger) {
            const menu = trigger.closest('.evaluation-response-menu');
            const willOpen = !menu.classList.contains('is-open');
            closeMenus(menu);
            menu.classList.toggle('is-open', willOpen);
            trigger.setAttribute('aria-expanded', String(willOpen));
            return;
        }
        if (!event.target.closest('.evaluation-response-menu')) closeMenus();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeMenus();
    });

    document.querySelectorAll('.evaluation-compose-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const form = document.getElementById(button.dataset.target);
            if (!form) return;
            closeMenus();
            const responseCard = button.closest('.evaluation-admin-response');
            if (responseCard) {
                responseCard.classList.add('is-editing');
                const content = responseCard.querySelector('.evaluation-admin-response-content');
                if (content) content.hidden = true;
            } else {
                const actions = button.closest('.evaluation-admin-actions');
                if (actions) actions.hidden = true;
            }
            form.hidden = false;
            form.querySelector('textarea')?.focus();
        });
    });

    document.querySelectorAll('.evaluation-compose-cancel').forEach(button => {
        button.addEventListener('click', () => {
            const form = button.closest('.evaluation-response-form');
            if (!form) return;
            form.hidden = true;
            const responseCard = form.closest('.evaluation-admin-response');
            if (responseCard) {
                responseCard.classList.remove('is-editing');
                const content = responseCard.querySelector('.evaluation-admin-response-content');
                if (content) content.hidden = false;
                const textarea = form.querySelector('textarea');
                if (textarea) textarea.value = textarea.defaultValue;
            } else {
                const actions = form.closest('.evaluation-admin-item')?.querySelector('.evaluation-admin-actions');
                if (actions) actions.hidden = false;
                const textarea = form.querySelector('textarea');
                if (textarea) textarea.value = '';
            }
            form.querySelector('textarea')?.blur();
        });
    });

    document.querySelectorAll('.evaluation-response-form').forEach(form => {
        form.addEventListener('submit', () => {
            const submit = form.querySelector('button[type="submit"]');
            if (!submit) return;
            submit.disabled = true;
            const isEditing = form.classList.contains('evaluation-response-inline-form');
            submit.innerHTML = '<i class="ph-light ph-spinner-gap" aria-hidden="true"></i><span>' + (isEditing ? 'Updating…' : 'Publishing…') + '</span>';
        });
    });

    document.querySelectorAll('.evaluation-delete-response-trigger').forEach(button => {
        button.addEventListener('click', () => {
            closeMenus();
            const form = document.getElementById(button.dataset.form);
            if (!form) return;
            const confirmDelete = () => {
                button.disabled = true;
                button.innerHTML = '<i class="ph-light ph-spinner-gap" aria-hidden="true"></i> Deleting…';
                form.submit();
            };
            if (typeof window.showAlertModal === 'function') {
                window.showAlertModal({
                    title: 'Delete official response?',
                    text: 'This will remove the public response from this evaluation.',
                    icon: 'ph-light ph-trash',
                    variant: 'danger',
                    confirmText: 'Delete response',
                    showCancel: true,
                    cancelText: 'Cancel',
                    onConfirm: confirmDelete
                });
            } else if (window.confirm('Delete this official response?')) {
                confirmDelete();
            }
        });
    });
});
</script>
@endpush
