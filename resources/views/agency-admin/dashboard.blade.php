@extends('layouts.admin')
@section('title', 'KNOWURLOCAL | Agency Workspace')
@section('page-title', 'Agency Overview')
@section('page-subtitle', 'Your agency support and public information workspace')
@push('styles')
<style>
.agency-workspace{display:grid;gap:20px;min-width:0;color:var(--text-primary,#172033)}
.aw-welcome{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:24px;border:1px solid var(--border-color,#e5e9f0);border-radius:18px;background:var(--surface,#fff)}
.aw-eyebrow{font-size:11px;letter-spacing:.1em;font-weight:750;color:#2859a8;text-transform:uppercase}.aw-welcome h2{font-size:23px;line-height:1.25;margin:7px 0 8px}.aw-welcome p{margin:0;line-height:1.55;color:var(--text-secondary,#64748b)}
.aw-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.aw-stat{padding:18px;border:1px solid var(--border-color,#e5e9f0);border-radius:15px;background:var(--surface,#fff);min-width:0}.aw-stat span{display:block;color:var(--text-secondary,#64748b);font-size:12px}.aw-stat strong{display:block;font-size:27px;margin-top:10px;letter-spacing:-.04em}.aw-card{padding:21px;border:1px solid var(--border-color,#e5e9f0);border-radius:16px;background:var(--surface,#fff);min-width:0}.aw-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.aw-card h3{margin:0;font-size:16px}.aw-link{color:#2859a8;font-size:13px;font-weight:650;text-decoration:none}.aw-table-wrap{overflow-x:auto}.aw-table{width:100%;border-collapse:collapse;min-width:560px}.aw-table th,.aw-table td{text-align:left;padding:12px;border-bottom:1px solid var(--border-color,#edf0f5);font-size:13px}.aw-table th{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-secondary,#64748b)}.aw-table tr:last-child td{border-bottom:0}.aw-status{display:inline-flex;border-radius:99px;padding:5px 8px;font-size:11px;font-weight:700;background:#eff6ff;color:#1d4ed8}.aw-empty{padding:24px;text-align:center;color:var(--text-secondary,#64748b);font-size:13px}
.aw-filter{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}.aw-filter input,.aw-filter select,.aw-form input,.aw-form textarea{width:100%;border:1px solid var(--border-color,#dce2eb);border-radius:10px;padding:11px 12px;background:var(--surface,#fff);color:inherit;font:inherit}.aw-filter .field{flex:1;min-width:180px}.aw-btn{display:inline-flex;justify-content:center;align-items:center;min-height:42px;border:0;border-radius:10px;padding:10px 15px;background:#2859a8;color:#fff;font-weight:650;cursor:pointer;text-decoration:none}.aw-form{display:grid;gap:15px;max-width:850px}.aw-form label{display:grid;gap:7px;font-size:12px;font-weight:700;color:var(--text-secondary,#64748b)}.aw-form textarea{min-height:115px;resize:vertical}.aw-muted{font-size:13px;color:var(--text-secondary,#64748b);line-height:1.55}.aw-alert{padding:12px 14px;border-radius:10px;background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;font-size:13px}
@media(max-width:900px){.aw-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.aw-welcome{padding:18px}.aw-grid{gap:10px}.aw-stat{padding:14px}.aw-stat strong{font-size:23px}.aw-card{padding:15px}.aw-card-head{align-items:flex-start;flex-direction:column}}
</style>
@endpush
@section('content')
<div class="agency-workspace">
    <section class="aw-welcome">
        <div>
            <span class="aw-eyebrow">Agency workspace</span>
            <h2>{{ $agency->agency_name }}</h2>
            <p>{{ $agency->agency_description ?: 'Keep your agency information accurate and help citizens get reliable answers.' }}</p>
        </div>
        <a class="aw-btn" href="{{ route('agency-admin.tickets') }}"><i class="ph-light ph-inbox" style="margin-right:8px"></i>Open support inbox</a>
    </section>
    <section class="aw-grid" aria-label="Agency statistics">
        <div class="aw-stat"><span>Pending inquiries</span><strong>{{ $counts['pending'] }}</strong></div>
        <div class="aw-stat"><span>Needs follow-up</span><strong>{{ $counts['follow_up'] }}</strong></div>
        <div class="aw-stat"><span>Awaiting citizen</span><strong>{{ $counts['awaiting_confirmation'] }}</strong></div>
        <div class="aw-stat"><span>Agency FAQs</span><strong>{{ $counts['faqs'] }}</strong></div>
    </section>
    <section class="aw-card">
        <div class="aw-card-head"><h3>Recent support requests</h3><a class="aw-link" href="{{ route('agency-admin.tickets') }}">View all <i class="ph-light ph-arrow-up-right"></i></a></div>
        @if($recentRequests->isEmpty())
            <div class="aw-empty">No support requests have been assigned to your agency yet.</div>
        @else
            <div class="aw-table-wrap"><table class="aw-table"><thead><tr><th>Inquiry</th><th>Citizen</th><th>Received</th><th>Status</th></tr></thead><tbody>
            @foreach($recentRequests as $ticket)
                <tr><td>{{ \Illuminate\Support\Str::limit($ticket->question, 100) }}</td><td>{{ trim(($ticket->user?->first_name ?? '').' '.($ticket->user?->last_name ?? '')) ?: 'Citizen' }}</td><td>{{ $ticket->created_at?->format('M j, Y') }}</td><td><span class="aw-status">{{ str_replace('_',' ',ucfirst($ticket->status)) }}</span></td></tr>
            @endforeach
            </tbody></table></div>
        @endif
    </section>
    <section class="aw-card">
        <div class="aw-card-head"><h3>Keep your public information current</h3></div>
        <p class="aw-muted">Update your agency description, office location, services, office hours, and office-head information. Changes are limited to your assigned agency.</p>
        <a class="aw-btn" href="{{ route('agency-admin.agency') }}">Manage agency profile</a>
    </section>
</div>
@endsection
