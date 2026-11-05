@extends('layouts.admin')
@section('title', $agency->agency_name.' Evaluations')
@section('page-title', 'Agency Evaluations')
@section('page-subtitle', $agency->agency_name.' · Individual public feedback')
@section('content')
<div class="admin-page evaluation-admin-page">
    <a href="{{ route('admin.evaluations.overview', request()->only(['search', 'rating_filter'])) }}" class="btn btn-secondary evaluation-back-link"><i class="ph-light ph-arrow-left" aria-hidden="true"></i><span>Back to Agency Satisfaction</span></a>
    <div class="admin-stats-grid">
        <div class="admin-stat-card"><span>Evaluations</span><strong>{{ number_format($stats->total) }}</strong></div>
        <div class="admin-stat-card"><span>Average satisfaction</span><strong>{{ number_format((float)$stats->average,1) }} <small>/ 5</small></strong></div>
    </div>
    <section class="admin-card">
        <div class="admin-card-heading"><div><h2>Rating distribution</h2><p>Published evaluations only.</p></div></div>
        @foreach(range(5,1) as $rating)
            @php($count=(int)($distribution[$rating]??0))
            <div class="evaluation-bar-row"><span>{{ $rating }} ★</span><div class="evaluation-bar"><span style="width:{{ $stats->total ? ($count/$stats->total*100) : 0 }}%"></span></div><strong>{{ $count }}</strong></div>
        @endforeach
    </section>
    <section class="admin-card">
        <div class="admin-card-heading"><div><h2>Individual evaluations</h2><p>Newest first. Reviewer names are partially masked to protect public users.</p></div></div>
        @forelse($evaluations as $evaluation)
            <article class="evaluation-admin-item">
                <div class="evaluation-admin-item-head">
                    <div>
                        <div class="evaluation-reviewer-identity">
                            <span class="evaluation-reviewer-avatar" aria-hidden="true">
    @if($evaluation->reviewer_avatar_url)
        <img src="{{ $evaluation->reviewer_avatar_url }}" alt="" loading="lazy" onerror="this.hidden=true; this.nextElementSibling.hidden=false">
        <span class="evaluation-reviewer-avatar-fallback" hidden></span>
    @else
        <span class="evaluation-reviewer-avatar-fallback"></span>
    @endif
</span>
                            <strong>{{ $evaluation->reviewer_name ?? 'Anonymous User' }}</strong>
                            @if($evaluation->reviewer_is_anonymous)
                                <span class="evaluation-anonymous-label">Anonymous</span>
                            @endif
                        </div>
                        <span class="evaluation-admin-date">{{ $evaluation->created_at->format('M d, Y') }}</span>
                        @if(($evaluation->reports_count ?? 0) > 0)
                            <span class="evaluation-reported-indicator"><i class="ph-light ph-flag" aria-hidden="true"></i> Reported · {{ $evaluation->reports_count }}</span>
                        @endif
                    </div>
                    <span class="evaluation-admin-stars">{{ str_repeat('★',$evaluation->rating) }}{{ str_repeat('☆',5-$evaluation->rating) }}</span>
                </div>
                <p class="evaluation-admin-comment">{{ $evaluation->comment ?: 'No additional comment provided.' }}</p>
                @if($evaluation->response)
                    <div class="evaluation-admin-response" id="admin-response-card-{{ $evaluation->id }}">
                        <div class="evaluation-admin-response-heading">
                            <strong>{{ $evaluation->response->responder_role === 'agency_admin' ? 'Official Agency Response' : 'Official System Response' }}</strong>
                            <div class="evaluation-admin-response-heading-actions">
                                <small>{{ $evaluation->response->updated_at->format('M d, Y') }}</small>
                                @if($canRespond)
                                    <div class="evaluation-response-menu">
                                        <button type="button" class="evaluation-response-menu-trigger" aria-label="Response actions" aria-haspopup="true" aria-expanded="false"><i class="ph-light ph-dots-three" aria-hidden="true"></i></button>
                                        <div class="evaluation-response-menu-panel" role="menu">
                                            <button type="button" class="evaluation-response-menu-item evaluation-compose-toggle" data-target="admin-response-form-{{ $evaluation->id }}" role="menuitem"><i class="ph-light ph-pencil-simple" aria-hidden="true"></i> Edit response</button>
                                            <button type="button" class="evaluation-response-menu-item is-danger evaluation-delete-response-trigger" data-form="admin-delete-response-{{ $evaluation->id }}" role="menuitem"><i class="ph-light ph-trash" aria-hidden="true"></i> Delete response</button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="evaluation-admin-response-content">
                            <p>{{ $evaluation->response->body }}</p>
                            <small>Published by {{ $evaluation->response->responder_role === 'agency_admin' ? 'Agency Administrator' : 'System Administrator' }}</small>
                        </div>
                        @if($canRespond)
                            <form id="admin-response-form-{{ $evaluation->id }}" method="POST" action="{{ route('admin.evaluations.respond', [$agency, $evaluation]) }}" class="evaluation-response-form evaluation-response-inline-form" hidden>
                                @csrf
                                <label for="admin-response-{{ $evaluation->id }}">Edit official response</label>
                                <textarea id="admin-response-{{ $evaluation->id }}" name="body" rows="3" maxlength="2000" required placeholder="Acknowledge the feedback and explain any follow-up...">{{ old('body', $evaluation->response->body) }}</textarea>
                                <div class="evaluation-response-form-actions">
                                    <button type="button" class="btn btn-cancel evaluation-compose-cancel">Cancel</button>
                                    <button type="submit" class="btn btn-primary"><i class="ph-light ph-paper-plane-tilt" aria-hidden="true"></i><span>Save changes</span></button>
                                </div>
                            </form>
                            <form id="admin-delete-response-{{ $evaluation->id }}" method="POST" action="{{ route('admin.evaluations.responses.delete', [$agency, $evaluation->response]) }}" class="evaluation-delete-response-form">@csrf @method('DELETE')</form>
                        @endif
                    </div>
                @endif

                @if($canRespond && !$evaluation->response)
                    <div class="evaluation-admin-actions" id="admin-response-actions-{{ $evaluation->id }}">
                        <button type="button" class="btn btn-primary evaluation-compose-toggle" data-target="admin-response-form-{{ $evaluation->id }}"><i class="ph-light ph-chat-circle" aria-hidden="true"></i> Reply</button>
                    </div>
                    <form id="admin-response-form-{{ $evaluation->id }}" method="POST" action="{{ route('admin.evaluations.respond', [$agency, $evaluation]) }}" class="evaluation-response-form" hidden>
                        @csrf
                        <label for="admin-response-{{ $evaluation->id }}">Write official response</label>
                        <textarea id="admin-response-{{ $evaluation->id }}" name="body" rows="3" maxlength="2000" required placeholder="Acknowledge the feedback and explain any follow-up...">{{ old('body') }}</textarea>
                        <div class="evaluation-response-form-actions">
                            <button type="button" class="btn btn-cancel evaluation-compose-cancel">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="ph-light ph-paper-plane-tilt" aria-hidden="true"></i><span>Publish response</span></button>
                        </div>
                    </form>
                @elseif(!$canRespond && !$evaluation->response)
                    <p class="evaluation-admin-note">An assigned Agency Administrator is responsible for responding to this agency's evaluations.</p>
                @endif

            </article>
        @empty
            <p>No published evaluations for this agency yet.</p>
        @endforelse
        <div class="admin-pagination">{{ $evaluations->links() }}</div>
    </section>
</div>
@endsection
@push('styles')
<style>
.evaluation-reviewer-identity{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.evaluation-reviewer-avatar{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;flex:0 0 28px;border-radius:50%;overflow:hidden;background:#e8edf3;color:#94a3b8;position:relative}
.evaluation-reviewer-avatar img{width:100%;height:100%;object-fit:cover}
.evaluation-reviewer-avatar-fallback{position:absolute;inset:0;display:block}
.evaluation-reviewer-avatar-fallback[hidden]{display:none}
.evaluation-reviewer-avatar-fallback::before{content:"";position:absolute;top:6px;left:10px;width:8px;height:8px;border-radius:50%;background:#94a3b8}
.evaluation-reviewer-avatar-fallback::after{content:"";position:absolute;left:5px;bottom:4px;width:18px;height:10px;border-radius:10px 10px 6px 6px;background:#94a3b8}
.evaluation-anonymous-label{display:inline-flex;align-items:center;padding:2px 7px;border-radius:999px;background:#f1f5f9;color:#64748b;font-size:10px;font-weight:600}
.evaluation-admin-page{display:grid;gap:18px}
.admin-stats-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.admin-stat-card,.admin-card{background:var(--surface,#fff);border:1px solid var(--border-color,#e5eaf1);border-radius:14px;padding:18px}
.admin-stat-card span{display:block;color:#64748b;font-size:13px}
.admin-stat-card strong{display:block;font-size:26px;margin-top:8px}
.admin-stat-card small{font-size:13px;color:#64748b}
.admin-card-heading h2{font-size:17px;margin:0}
.admin-card-heading p{color:#64748b;margin:5px 0 16px;font-size:13px}
.evaluation-bar-row{display:grid;grid-template-columns:40px 1fr 35px;gap:10px;align-items:center;margin:9px 0}
.evaluation-bar{height:8px;background:#edf1f6;border-radius:99px;overflow:hidden}
.evaluation-bar span{display:block;height:100%;background:#e9a52e}
.evaluation-admin-item{padding:18px 0;border-bottom:1px solid #edf0f4}
.evaluation-admin-item-head{display:flex;justify-content:space-between;gap:12px}
.evaluation-admin-date,.evaluation-admin-note{display:block;color:#64748b;font-size:12px;margin-top:3px}
.evaluation-reported-indicator{display:inline-flex;align-items:center;gap:4px;margin-top:6px;padding:3px 7px;border-radius:999px;background:#fff7ed;color:#9a5b00;font-size:10px;font-weight:600}
.evaluation-reported-indicator i{font-size:13px}
.evaluation-admin-stars{color:#e9a52e;white-space:nowrap}
.evaluation-admin-comment{white-space:pre-wrap;line-height:1.55;color:#334155}
.evaluation-admin-response{background:#f4f8fc;border:1px solid #e1eaf4;padding:14px;border-radius:10px;margin:12px 0}
.evaluation-admin-response-heading{display:flex;justify-content:space-between;gap:12px;align-items:center}
.evaluation-admin-response p{white-space:pre-wrap;line-height:1.5;color:#334155;margin:8px 0}
.evaluation-admin-response small{color:#64748b;font-size:12px}
.evaluation-admin-actions{display:flex;justify-content:flex-end;align-items:center;gap:8px;margin-top:12px}
.evaluation-response-menu{position:relative}
.evaluation-response-menu-trigger{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border:1px solid #e2e8f0;border-radius:9px;background:#fff;color:#475569;cursor:pointer}
.evaluation-response-menu-trigger:hover,.evaluation-response-menu-trigger[aria-expanded="true"]{background:#f8fafc;border-color:#cbd5e1;color:#1e293b}
.evaluation-response-menu-trigger i{font-size:20px}
.evaluation-response-menu-panel{position:absolute;right:0;top:calc(100% + 6px);z-index:20;display:none;min-width:178px;padding:5px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.12)}
.evaluation-response-menu.is-open .evaluation-response-menu-panel{display:grid}
.evaluation-response-menu-item{display:flex;align-items:center;gap:9px;width:100%;min-height:38px;padding:0 10px;border:0;border-radius:7px;background:transparent;color:#334155;text-align:left;font:inherit;font-size:13px;font-weight:500;cursor:pointer}
.evaluation-response-menu-item:hover{background:#f1f5f9}
.evaluation-response-menu-item i{font-size:17px}
.evaluation-response-menu-item.is-danger{color:#dc2626}
.evaluation-response-menu-item.is-danger:hover{background:#fef2f2}
.evaluation-admin-response{position:relative}
.evaluation-admin-response-heading-actions{display:flex;align-items:center;gap:10px}
.evaluation-admin-response-heading-actions>small{white-space:nowrap}
.evaluation-admin-response-heading-actions .evaluation-response-menu{flex:0 0 auto}
.evaluation-admin-response.is-editing .evaluation-admin-response-heading-actions .evaluation-response-menu{visibility:hidden}
.evaluation-admin-response-content p{white-space:pre-wrap;line-height:1.5;color:#334155;margin:8px 0}
.evaluation-response-inline-form{margin:14px 0 0;padding:14px 0 0;border:0;border-top:1px solid #dce6f0;border-radius:0;background:transparent}
.evaluation-response-inline-form textarea{min-height:110px;line-height:1.5}
.evaluation-response-inline-form .evaluation-response-form-actions{padding-top:2px}
.evaluation-delete-response-form{display:none!important}
.evaluation-response-form{display:grid;gap:12px;margin-top:14px;padding:16px;background:#f8fafc;border:1px solid #e7edf4;border-radius:11px}
.evaluation-response-form[hidden]{display:none!important}
.evaluation-response-form label{font-size:13px;font-weight:600;color:#334155}
.evaluation-response-form textarea{width:100%;min-height:92px;border:1px solid #d9e1eb;border-radius:9px;padding:11px;resize:vertical;background:#fff;color:#1e293b;font:inherit}
.evaluation-response-form textarea:focus{outline:2px solid #bfdbfe;border-color:#60a5fa}
.evaluation-response-form-actions{display:flex;justify-content:flex-end;align-items:center;gap:10px}
.evaluation-response-form-actions .btn,.evaluation-admin-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:10px 16px;border-radius:9px;font-size:13px;font-weight:600;line-height:1.2;white-space:nowrap}
.evaluation-response-form-actions .btn i,.evaluation-admin-actions .btn i{font-size:17px}
.evaluation-response-form-actions .btn:disabled{opacity:.7;cursor:wait}
.evaluation-response-form-actions [hidden],.evaluation-admin-actions [hidden]{display:none!important}
.evaluation-back-link{display:inline-flex;align-items:center;justify-content:center;gap:9px;justify-self:start;min-height:40px;padding:9px 15px;border:1px solid #dbe3ed;border-radius:9px;background:#fff;color:#334155;font-size:13px;font-weight:600;line-height:1.2;text-decoration:none;box-shadow:0 1px 2px rgba(15,23,42,.04);transition:background-color .16s ease,border-color .16s ease,color .16s ease,transform .16s ease}
.evaluation-back-link:hover{background:#f8fafc;border-color:#cbd5e1;color:#1e293b;text-decoration:none;transform:translateY(-1px)}
.evaluation-back-link:focus-visible{outline:3px solid rgba(37,99,235,.18);outline-offset:2px}
.evaluation-back-link i{font-size:17px}
.admin-pagination{margin-top:16px}
@media(max-width:700px){.admin-stats-grid{grid-template-columns:1fr}.admin-card{padding:14px}.evaluation-response-form-actions{display:grid;grid-template-columns:1fr 1.4fr}.evaluation-response-form-actions .btn{width:100%;min-width:0}.evaluation-admin-response-heading{align-items:flex-start}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const closeMenus = (except = null) => document.querySelectorAll('.evaluation-response-menu.is-open').forEach(menu => {
        if (menu === except) return;
        menu.classList.remove('is-open');
        menu.querySelector('.evaluation-response-menu-trigger')?.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('click', event => {
        const trigger = event.target.closest('.evaluation-response-menu-trigger');
        if (trigger) {
            const menu = trigger.closest('.evaluation-response-menu');
            const open = !menu.classList.contains('is-open');
            closeMenus(menu);
            menu.classList.toggle('is-open', open);
            trigger.setAttribute('aria-expanded', String(open));
            return;
        }
        if (!event.target.closest('.evaluation-response-menu')) closeMenus();
    });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenus(); });

    document.querySelectorAll('.evaluation-compose-toggle').forEach(button => button.addEventListener('click', () => {
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
    }));
    document.querySelectorAll('.evaluation-compose-cancel').forEach(button => button.addEventListener('click', () => {
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
    }));
    document.querySelectorAll('.evaluation-response-form').forEach(form => form.addEventListener('submit', () => {
        const submit = form.querySelector('button[type="submit"]');
        if (!submit) return;
        submit.disabled = true;
        const isEditing = form.classList.contains('evaluation-response-inline-form');
        submit.innerHTML = '<i class="ph-light ph-spinner-gap" aria-hidden="true"></i><span>' + (isEditing ? 'Updating…' : 'Publishing…') + '</span>';
    }));
    document.querySelectorAll('.evaluation-delete-response-trigger').forEach(button => button.addEventListener('click', () => {
        closeMenus();
        const form = document.getElementById(button.dataset.form);
        if (!form) return;
        const confirmDelete = () => {
            button.disabled = true;
            button.innerHTML = '<i class="ph-light ph-spinner-gap" aria-hidden="true"></i> Deleting…';
            form.submit();
        };
        if (typeof window.showAlertModal === 'function') {
            window.showAlertModal({title:'Delete official response?',text:'This will remove the public response from this evaluation.',icon:'ph-light ph-trash',variant:'danger',confirmText:'Delete response',showCancel:true,cancelText:'Cancel',onConfirm:confirmDelete});
        } else if (window.confirm('Delete this official response?')) confirmDelete();
    }));
});
</script>
@endpush
