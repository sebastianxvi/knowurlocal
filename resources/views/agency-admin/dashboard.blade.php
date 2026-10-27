@extends('layouts.admin')
@section('title', 'KNOWURLOCAL | Agency Dashboard')
@section('page-title', 'Agency Dashboard')
@section('page-subtitle', 'Support performance, common citizen questions, and shared work')
@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/admin/dashboard.css') }}">
<style>

.aw-stat,.aw-card{position:relative;transition:border-color .16s,box-shadow .16s}
.aw-stat:hover,.aw-card:hover{border-color:#c5d5ee;box-shadow:0 5px 18px rgba(24,49,88,.05)}
.aw-stat [data-tip],.aw-card [data-tip]{position:relative;cursor:help;border-bottom:1px dotted #9aa9bd}
.aw-stat [data-tip]:focus::after,.aw-stat [data-tip]:hover::after,.aw-card [data-tip]:focus::after,.aw-card [data-tip]:hover::after{content:attr(data-tip);position:absolute;z-index:30;left:0;top:calc(100% + 7px);width:max-content;max-width:min(280px,75vw);white-space:normal;background:#17243a;color:#fff;padding:9px 11px;border-radius:8px;font-size:11px;line-height:1.45;font-weight:500;box-shadow:0 8px 24px rgba(15,23,42,.18)}
.agency-workspace{display:grid;gap:20px;min-width:0;color:var(--text-primary,#172033)}
.aw-welcome{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:24px;border:1px solid var(--border-color,#e5e9f0);border-radius:18px;background:var(--surface,#fff)}
.aw-eyebrow{font-size:11px;letter-spacing:.1em;font-weight:750;color:#2859a8;text-transform:uppercase}.aw-welcome h2{font-size:23px;line-height:1.25;margin:7px 0 8px}.aw-welcome p{margin:0;line-height:1.55;color:var(--text-secondary,#64748b)}
.aw-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.aw-stat{padding:18px;border:1px solid var(--border-color,#e5e9f0);border-radius:15px;background:var(--surface,#fff);min-width:0}.aw-stat-link{display:block;text-decoration:none;color:inherit;transition:border-color .15s,transform .15s}.aw-stat-link:hover{border-color:#9bb8e8;transform:translateY(-1px)}.aw-stat small{display:block;margin-top:5px;color:var(--text-secondary,#64748b);font-size:11px}.aw-stat span{display:block;color:var(--text-secondary,#64748b);font-size:12px}.aw-stat strong{display:block;font-size:27px;margin-top:10px;letter-spacing:-.04em}.aw-card{padding:21px;border:1px solid var(--border-color,#e5e9f0);border-radius:16px;background:var(--surface,#fff);min-width:0}.aw-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.aw-card h3{margin:0;font-size:16px}.aw-link{color:#2859a8;font-size:13px;font-weight:650;text-decoration:none}.aw-table-wrap{overflow-x:auto}.aw-table{width:100%;border-collapse:collapse;min-width:560px}.aw-table th,.aw-table td{text-align:left;padding:12px;border-bottom:1px solid var(--border-color,#edf0f5);font-size:13px}.aw-table th{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-secondary,#64748b)}.aw-table tr:last-child td{border-bottom:0}.aw-status{display:inline-flex;border-radius:99px;padding:5px 8px;font-size:11px;font-weight:700;background:#eff6ff;color:#1d4ed8}.aw-empty{padding:24px;text-align:center;color:var(--text-secondary,#64748b);font-size:13px}
.aw-filter{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}.aw-filter input,.aw-filter select,.aw-form input,.aw-form textarea{width:100%;border:1px solid var(--border-color,#dce2eb);border-radius:10px;padding:11px 12px;background:var(--surface,#fff);color:inherit;font:inherit}.aw-filter .field{flex:1;min-width:180px}.aw-btn{display:inline-flex;justify-content:center;align-items:center;min-height:42px;border:0;border-radius:10px;padding:10px 15px;background:#2859a8;color:#fff;font-weight:650;cursor:pointer;text-decoration:none}.aw-form{display:grid;gap:15px;max-width:850px}.aw-form label{display:grid;gap:7px;font-size:12px;font-weight:700;color:var(--text-secondary,#64748b)}.aw-form textarea{min-height:115px;resize:vertical}.aw-muted{font-size:13px;color:var(--text-secondary,#64748b);line-height:1.55}.aw-alert{padding:12px 14px;border-radius:10px;background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;font-size:13px}

.aw-collab{display:grid;gap:14px}.aw-collab-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:16px}.aw-collab-stat{padding:14px;border:1px solid var(--border-color,#e5e9f0);border-radius:12px;background:var(--surface,#fff)}.aw-collab-stat strong{display:block;font-size:22px;letter-spacing:-.03em}.aw-collab-stat span{font-size:12px;color:var(--text-secondary,#64748b)}.aw-collab-item{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:13px;align-items:center;padding:15px 0;border-bottom:1px solid var(--border-color,#edf0f5)}.aw-collab-item:last-child{border-bottom:0}.aw-collab-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:12px;background:#eef4ff;color:#2859a8;font-size:18px}.aw-collab-copy{min-width:0}.aw-collab-copy strong{display:block;font-size:14px;overflow-wrap:anywhere}.aw-collab-copy small{display:block;margin-top:5px;color:var(--text-secondary,#64748b);line-height:1.45}.aw-collab-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end}.aw-collab-due{font-size:11px;font-weight:700;color:#b45309}.aw-collab-status{max-width:145px;border:1px solid var(--border-color,#dce2eb);border-radius:9px;padding:8px;background:var(--surface,#fff);color:inherit;font:inherit;font-size:12px}.aw-collab-empty{padding:22px 12px;text-align:center;color:var(--text-secondary,#64748b);font-size:13px}.aw-collab-empty strong{display:block;color:var(--text-primary,#172033);margin-bottom:5px}.aw-collab-modal[hidden]{display:none}.aw-collab-modal{position:fixed;inset:0;z-index:10000;display:grid;place-items:center;padding:18px}.aw-collab-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.aw-collab-dialog{position:relative;width:min(620px,100%);max-height:min(90vh,850px);overflow:auto;border:1px solid var(--border-color,#e5e9f0);border-radius:18px;background:var(--surface,#fff);padding:24px;box-shadow:0 24px 80px rgba(15,23,42,.24)}.aw-collab-dialog-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px}.aw-collab-dialog-head h3{margin:4px 0;font-size:20px}.aw-collab-close{border:1px solid var(--border-color,#e5e9f0);border-radius:10px;background:transparent;color:inherit;width:38px;height:38px;cursor:pointer}.aw-collab-form{display:grid;gap:14px}.aw-collab-form label{display:grid;gap:7px;font-size:12px;font-weight:700;color:var(--text-secondary,#64748b)}.aw-collab-form input,.aw-collab-form textarea,.aw-collab-form select{width:100%;min-width:0;border:1px solid var(--border-color,#dce2eb);border-radius:10px;padding:11px 12px;background:var(--surface,#fff);color:inherit;font:inherit}.aw-collab-form textarea{resize:vertical;min-height:90px}.aw-collab-form-grid,.aw-collab-targets{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.aw-collab-form-error{padding:10px 12px;border-radius:9px;background:#fef2f2;color:#b91c1c;font-size:13px}.aw-collab-actions{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;margin-top:4px}.aw-collab-secondary{border:1px solid var(--border-color,#dce2eb);border-radius:10px;padding:10px 14px;background:transparent;color:inherit;font-weight:650;cursor:pointer}
@media(max-width:560px){.aw-collab-summary{grid-template-columns:repeat(3,minmax(0,1fr));gap:7px}.aw-collab-stat{padding:11px}.aw-collab-stat strong{font-size:19px}.aw-collab-item{grid-template-columns:auto minmax(0,1fr);align-items:start}.aw-collab-meta{grid-column:2;justify-content:flex-start}.aw-collab-status{max-width:100%}.aw-collab-form-grid,.aw-collab-targets{grid-template-columns:1fr}.aw-collab-dialog{padding:18px}}
@media(max-width:900px){.aw-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.aw-welcome{padding:18px}.aw-grid{gap:10px}.aw-stat{padding:14px}.aw-stat strong{font-size:23px}.aw-card{padding:15px}.aw-card-head{align-items:flex-start;flex-direction:column}}
@media print {
    @page { size: A4 portrait; margin: 12mm; }
    body { background: #fff !important; }
    .agency-workspace { gap: 12px !important; color: #172033 !important; }
    .admin-export-menu, button, .aw-collab-modal, .aw-collab-form, .aw-link[href*="tickets"] { display:none !important; }
    .aw-welcome, .aw-card, .aw-stat, .aw-collab-stat { break-inside: avoid; box-shadow:none !important; }
    .aw-grid { grid-template-columns: repeat(2,minmax(0,1fr)) !important; }
    .aw-table-wrap { overflow:visible !important; }
    .aw-table { min-width:0 !important; }
    a { color:#172033 !important; text-decoration:none !important; }
}
</style>
@endpush
@section('content')
<div class="agency-workspace" data-collaboration-realtime="true" data-admin-id="{{ auth()->id() }}">
    <section class="aw-welcome">
        <div>
            <span class="aw-eyebrow">Agency performance</span>
            <h2>Support at a glance</h2>
            <p>Track citizen requests, spot repeat questions, and coordinate the next actions for your agency.</p>
        </div>
        <details class="admin-export-menu">
            <summary class="dashboard-export-button" aria-label="Export agency dashboard report">
                <i class="ph-light ph-export" aria-hidden="true"></i>
                <span>Export</span>
                <i class="ph-light ph-caret-down export-caret" aria-hidden="true"></i>
            </summary>
            <div class="admin-export-dropdown">
                <a href="#" onclick="window.print(); return false;">
                    <i class="ph-light ph-file-pdf" aria-hidden="true"></i>
                    <span><strong>Dashboard summary</strong><small>Save this agency snapshot as PDF</small></span>
                </a>
            </div>
        </details>
    </section>
    <section class="aw-grid" aria-label="Agency statistics">
        <a class="aw-stat aw-stat-link" href="{{ route('agency-admin.tickets') }}"><span tabindex="0" data-tip="Citizen tickets that are waiting for your agency to send its first response.">Pending inquiries</span><strong>{{ $counts['pending'] }}</strong><small>Needs a response</small></a>
        <a class="aw-stat aw-stat-link" href="{{ route('agency-admin.tickets') }}"><span tabindex="0" data-tip="Tickets that need another action from your agency after the initial response.">Needs follow-up</span><strong>{{ $counts['follow_up'] }}</strong><small>Requires another action</small></a>
        <a class="aw-stat aw-stat-link" href="{{ route('agency-admin.tickets') }}"><span tabindex="0" data-tip="Your agency has replied and is waiting for the citizen to respond or confirm.">Awaiting citizen</span><strong>{{ $counts['awaiting_confirmation'] }}</strong><small>Response sent</small></a>
        <a class="aw-stat aw-stat-link" href="{{ route('agency-admin.faqs') }}"><span tabindex="0" data-tip="Public FAQ answers currently published for your agency.">Published FAQs</span><strong>{{ $counts['faqs'] }}</strong><small>Public answers available</small></a>
    </section>
    <section class="aw-card" id="popular-questions">
        <div class="aw-card-head"><div><h3>Most-asked citizen questions <span tabindex="0" data-tip="Repeated ticket wording can reveal answers citizens need often. Review suggestions before changing or publishing an FAQ.">ⓘ</span></h3><p class="aw-muted" style="margin:6px 0 0">Repeated questions are good candidates for clearer FAQs. Review the wording before publishing.</p></div><a class="aw-link" href="{{ route('agency-admin.faqs') }}">Manage FAQs ↗</a></div>
        @if($popularQuestions->isEmpty())
            <div class="aw-empty">Not enough repeated questions yet. As similar tickets arrive, useful patterns will appear here.</div>
        @else
            <div class="aw-table-wrap"><table class="aw-table"><thead><tr><th>Question pattern</th><th>Tickets</th><th>Suggested action</th></tr></thead><tbody>
            @foreach($popularQuestions as $question)
                <tr><td>{{ $question['question'] }}</td><td><span class="aw-status">{{ $question['count'] }} {{ \Illuminate\Support\Str::plural('ticket', $question['count']) }}</span></td><td>{{ $question['faq_match'] ? 'Related FAQ exists — review it' : 'Potential FAQ candidate' }}</td></tr>
            @endforeach
            </tbody></table></div>
        @endif
    </section>
    <section class="aw-card aw-collab" id="agency-collaboration">
        <div class="aw-card-head">
            <div>
                <span class="aw-eyebrow">Shared work</span>
                <h3 style="margin-top:5px">Collaboration with the system team</h3>
                <p class="aw-muted" style="margin:6px 0 0">Receive reminders, request help, and track follow-ups with System Admins and Superadmins.</p>
            </div>
            <button type="button" class="aw-btn" id="open-collaboration-modal"><i class="ph-light ph-plus" style="margin-right:8px"></i>New collaboration</button>
        </div>
        <div class="aw-collab-summary">
            <div class="aw-collab-stat"><strong>{{ number_format($collaborationOpenCount) }}</strong><span>Open shared tasks</span></div>
            <div class="aw-collab-stat"><strong>{{ number_format($collaborationYourActionCount) }}</strong><span>Assigned to you</span></div>
            <div class="aw-collab-stat"><strong>{{ number_format($collaborationOverdueCount) }}</strong><span>Overdue for you</span></div>
        </div>
        @forelse($collaborationTasks as $task)
            @php
                $creatorName = trim(($task->creator?->first_name ?? 'Admin') . ' ' . ($task->creator?->last_name ?? ''));
                $assigneeName = trim(($task->assignee?->first_name ?? 'Admin') . ' ' . ($task->assignee?->last_name ?? ''));
                $isAssignedToMe = (int) $task->assigned_to_id === (int) auth()->id();
                $isOverdue = $task->due_at && $task->due_at->isPast();
            @endphp
            <article class="aw-collab-item" id="collaboration-task-{{ $task->id }}" data-collaboration-task data-task-id="{{ $task->id }}">
                <div class="aw-collab-icon"><i class="ph-light {{ $task->task_type === 'review' ? 'ph-check-square' : ($task->task_type === 'assist' ? 'ph-handshake' : 'ph-bell-ringing') }}" aria-hidden="true"></i></div>
                <div class="aw-collab-copy">
                    <strong>{{ $task->title }}</strong>
                    <small>{{ $task->task_type_label }} · {{ $creatorName }} → {{ $assigneeName }}@if($task->target_label_snapshot) · {{ $task->target_module_label }}: {{ $task->target_label }}@endif</small>
                    @if($task->description)<small>{{ $task->description }}</small>@endif
                </div>
                <div class="aw-collab-meta">
                    @if($task->due_at)<span class="aw-collab-due">{{ $isOverdue ? 'Overdue · ' : 'Due ' }}{{ $task->due_at->format('M j') }}</span>@endif
                    <select class="aw-collab-status" data-collaboration-status data-task-id="{{ $task->id }}" aria-label="Update collaboration task status" {{ $isAssignedToMe || $task->created_by_id === auth()->id() ? '' : 'disabled' }}>
                        @if($isAssignedToMe)
                            @if($task->created_by_id === auth()->id())<option value="cancelled">Cancel task</option>@endif
                            <option value="open" {{ $task->status === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>In progress</option>
                            <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        @else
                            <option value="{{ $task->status }}" selected>{{ $task->status_label }}</option>
                            @if($task->created_by_id === auth()->id())<option value="cancelled">Cancel task</option>@endif
                        @endif
                    </select>
                </div>
            </article>
        @empty
            <div class="aw-collab-empty"><strong>No shared tasks yet</strong>Use collaboration to remind your agency team about important updates or ask the system team for assistance.</div>
        @endforelse
    </section>

    <div class="aw-collab-modal" id="collaboration-modal" hidden aria-hidden="true">
        <div class="aw-collab-backdrop" data-close-collaboration-modal></div>
        <section class="aw-collab-dialog" role="dialog" aria-modal="true" aria-labelledby="agency-collaboration-title">
            <div class="aw-collab-dialog-head">
                <div><span class="aw-eyebrow">Team collaboration</span><h3 id="agency-collaboration-title">Create shared work</h3><p class="aw-muted">Assign a reminder or ask the system team for help.</p></div>
                <button type="button" class="aw-collab-close" data-close-collaboration-modal aria-label="Close"><i class="ph-light ph-x"></i></button>
            </div>
            <form id="collaboration-form" class="aw-collab-form"
                data-store-url="{{ route('agency-admin.collaboration.store') }}"
                data-targets-url="{{ route('agency-admin.collaboration.targets') }}"
                data-status-base-url="{{ url('/agency-admin/collaboration') }}">
                @csrf
                <div class="aw-collab-form-grid">
                    <label><span>Work type</span><select name="task_type" required><option value="assist">Ask for assistance</option><option value="review">Request a review</option><option value="handoff">Follow-up / handoff</option></select></label>
                    <label><span>Assign to</span><select name="assigned_to_id" required><option value="">Choose system administrator</option>@foreach($collaborationAdmins as $admin)<option value="{{ $admin->id }}">{{ trim($admin->first_name . ' ' . $admin->last_name) ?: $admin->email }} — {{ ucfirst($admin->role) }}</option>@endforeach</select></label>
                </div>
                <label><span>Related information (optional)</span><div class="aw-collab-targets"><select name="target_type" id="collaboration-target-type"><option value="">No specific record</option><option value="agency">Agency profile</option><option value="faq">FAQ</option><option value="support_request">Support request</option><option value="category">Category</option><option value="user">Account</option></select><select name="target_id" id="collaboration-target-id" disabled><option value="">Choose a record</option></select></div></label>
                <label><span>Title</span><input type="text" name="title" maxlength="140" placeholder="e.g. Please review our updated office hours" required></label>
                <label><span>Details / reminder</span><textarea name="description" rows="3" maxlength="1000" placeholder="What needs to be updated, reviewed, or resolved?"></textarea></label>
                <label><span>Due date (optional)</span><input type="datetime-local" name="due_at"></label>
                <div class="aw-collab-form-error" id="collaboration-form-error" role="alert" hidden></div>
                <div class="aw-collab-actions"><button type="button" class="aw-collab-secondary" data-close-collaboration-modal>Cancel</button><button type="submit" class="aw-btn" id="collaboration-submit"><i class="ph-light ph-paper-plane-tilt" style="margin-right:8px"></i>Create task</button></div>
            </form>
        </section>
    </div>

</div>
@push('scripts')
<script src="{{ asset('jsfiles/admin/dashboard.js') }}"></script>
@endpush
@endsection
