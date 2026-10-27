@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/nga-management.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | Agency Profile')
@section('page-title', 'Agency Profile')
@section('page-subtitle', 'View and manage your agency’s public information')

@section('content')
@php
    $agencyTypeName = $agency->type?->name;
    $agencyTypeClass = \Illuminate\Support\Str::slug($agencyTypeName ?? 'unknown');
    $storedCategoryColor = $agency->category?->display_color ?? '';
    $agencyCategoryColor = preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $storedCategoryColor)
        ? $storedCategoryColor
        : '#3b82f6';
@endphp
<div class="logs-page agency-profile-page" style="--agency-category-color: {{ $agencyCategoryColor }}">
    @if(session('success'))
        <div class="alert alert-success agency-profile-alert" role="status">
            <i class="ph-light ph-check-circle" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div role="alert" class="alert alert-danger agency-profile-alert agency-profile-alert-error">
            <i class="ph-light ph-warning-circle" aria-hidden="true"></i>
            <div><strong>Please review the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <section class="agency-profile-hero">
        <div class="agency-profile-hero-glow" aria-hidden="true"></div>
        <div class="agency-profile-identity">
            <div class="agency-profile-logo-frame">
                @if($agency->agency_image)
                    <img src="{{ $agencyImageUrl }}" alt="{{ $agency->agency_name }} logo" class="agency-profile-logo" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                @endif
                <div class="agency-profile-logo-placeholder" aria-hidden="true" @if($agency->agency_image) hidden @endif><i class="ph-light ph-buildings"></i></div>
            </div>
            <div class="agency-profile-heading">
                <div class="agency-profile-eyebrow"><span class="agency-profile-eyebrow-dot"></span> Agency profile</div>
                <h2>{{ $agency->agency_name }}@if($agency->agency_abbreviation) <span class="agency-profile-abbreviation">({{ $agency->agency_abbreviation }})</span>@endif</h2>
                <p class="agency-profile-description">{{ $agency->agency_description ?: 'Add a description to help citizens understand your agency and the services it provides.' }}</p>
                <div class="agency-profile-badges">
                    <span class="type-badge {{ $agencyTypeClass }}"><i class="ph-light ph-buildings" aria-hidden="true"></i>{{ $agencyTypeName ?: 'Agency type not set' }}</span>
                    @if($agency->category)
                        <span class="category-badge" style="--category-color: {{ $agencyCategoryColor }}"><span class="agency-category-dot" aria-hidden="true"></span>{{ $agency->category->category_name }}</span>
                    @else
                        <span class="agency-profile-missing-badge">Category not set</span>
                    @endif
                </div>
            </div>
        </div>
        <button type="button" class="agency-profile-edit-button" onclick="openModal('edit', window.agencyProfileData)"><i class="ph-light ph-pencil-simple" aria-hidden="true"></i><span>Edit profile</span></button>
        <div class="agency-profile-hero-footer"><i class="ph-light ph-globe-hemisphere-west" aria-hidden="true"></i><span>This information helps citizens identify and contact your agency.</span></div>
    </section>

    <section class="agency-profile-section">
        <div class="agency-profile-section-heading">
            <div class="agency-profile-section-icon agency-profile-section-icon-blue"><i class="ph-light ph-identification-card" aria-hidden="true"></i></div>
            <div><h3>Agency information</h3><p>Public-facing details about your office and services.</p></div>
        </div>
        <div class="agency-profile-details">
            <article class="agency-profile-detail-card">
                <span class="agency-profile-detail-icon detail-blue"><i class="ph-light ph-map-pin" aria-hidden="true"></i></span>
                <div><small>Office location</small><strong>{{ $agency->agency_location ?: 'Not provided' }}</strong></div>
            </article>
            <article class="agency-profile-detail-card">
                <span class="agency-profile-detail-icon detail-violet"><i class="ph-light ph-clock" aria-hidden="true"></i></span>
                <div><small>Office hours</small><strong>{{ $agency->office_hours ?: 'Not provided' }}</strong></div>
            </article>
            <article class="agency-profile-detail-card">
                <span class="agency-profile-detail-icon detail-teal"><i class="ph-light ph-user-circle" aria-hidden="true"></i></span>
                <div><small>Office head</small><strong>{{ $agency->office_head_name ?: 'Not provided' }}</strong></div>
            </article>
            <article class="agency-profile-detail-card">
                <span class="agency-profile-detail-icon detail-amber"><i class="ph-light ph-identification-badge" aria-hidden="true"></i></span>
                <div><small>Position</small><strong>{{ $agency->office_head_position ?: 'Not provided' }}</strong></div>
            </article>
            <article class="agency-profile-detail-card agency-profile-detail-card-wide">
                <span class="agency-profile-detail-icon detail-green"><i class="ph-light ph-list-checks" aria-hidden="true"></i></span>
                <div><small>Services offered</small><strong class="agency-profile-services">{{ $agency->services_offered ?: 'Not provided' }}</strong></div>
            </article>
            <article class="agency-profile-detail-card">
                <span class="agency-profile-detail-icon detail-rose"><i class="ph-light ph-map-trifold" aria-hidden="true"></i></span>
                <div><small>Map coordinates</small><strong>{{ $agency->lat !== null && $agency->lng !== null ? $agency->lat . ', ' . $agency->lng : 'Not set' }}</strong>
                @if($agency->lat !== null && $agency->lng !== null)
                    <a class="agency-profile-map-link" href="https://www.google.com/maps?q={{ urlencode($agency->lat . ',' . $agency->lng) }}" target="_blank" rel="noopener noreferrer">View on map <i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></a>
                @endif
                </div>
            </article>
        </div>
    </section>

    <section class="agency-profile-section agency-profile-contacts-section">
        <div class="agency-profile-section-heading agency-profile-contact-heading">
            <div class="agency-profile-section-icon agency-profile-section-icon-teal"><i class="ph-light ph-address-book" aria-hidden="true"></i></div>
            <div><h3>Contact information</h3><p>Ways citizens can reach your office.</p></div>
            <span class="agency-profile-contact-count">{{ $agency->contacts->count() }} {{ \Illuminate\Support\Str::plural('contact', $agency->contacts->count()) }}</span>
        </div>
        @if($agency->contacts->isNotEmpty())
            <div class="agency-profile-contact-table-wrap"><table class="table agency-profile-contact-table"><thead><tr><th>Type</th><th>Label</th><th>Contact details</th><th>Primary</th></tr></thead><tbody>
                @foreach($agency->contacts as $contact)
                    <tr>
                        <td><span class="agency-profile-contact-type">{{ $contact->contactType?->name ?? 'Contact' }}</span></td>
                        <td>{{ $contact->label ?: '—' }}</td>
                        <td class="agency-profile-contact-value">{{ $contact->value }}</td>
                        <td>@if($contact->is_primary)<span class="agency-profile-primary"><i class="ph-fill ph-check-circle" aria-hidden="true"></i> Primary</span>@else<span class="agency-profile-secondary">—</span>@endif</td>
                    </tr>
                @endforeach
            </tbody></table></div>
        @else
            <div class="agency-profile-empty-contacts"><span><i class="ph-light ph-address-book" aria-hidden="true"></i></span><strong>No contact information yet</strong><p>Add a hotline, email, or other contact details so citizens know how to reach your office.</p></div>
        @endif
    </section>
</div>

<style>
.agency-profile-page{display:grid;gap:18px;min-width:0;padding-bottom:24px}
.agency-profile-page .agency-profile-hero,.agency-profile-page .agency-profile-section{position:relative;isolation:isolate;min-width:0;overflow:hidden;background:var(--surface,#fff);border:1px solid var(--border-color,#e2e8f0);border-radius:18px;box-shadow:0 4px 18px rgba(15,23,42,.035)}
.agency-profile-page .agency-profile-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:24px;padding:28px;background:linear-gradient(115deg,color-mix(in srgb,var(--agency-category-color) 7%,#fff) 0%,#fff 47%,#f7faff 100%)}
.agency-profile-page .agency-profile-hero:before{position:absolute;z-index:-1;inset:0 auto 0 0;width:5px;background:var(--agency-category-color);content:""}
.agency-profile-page .agency-profile-hero-glow{position:absolute;z-index:-1;top:-100px;right:8%;width:260px;height:260px;border-radius:50%;background:color-mix(in srgb,var(--agency-category-color) 9%,transparent);filter:blur(3px);pointer-events:none}
.agency-profile-page .agency-profile-identity{display:flex;align-items:flex-start;gap:20px;min-width:0;flex:1}
.agency-profile-page .agency-profile-logo-frame{display:grid;place-items:center;flex:0 0 100px;width:100px;height:100px;padding:8px;border:1px solid color-mix(in srgb,var(--agency-category-color) 22%,#e2e8f0);border-radius:20px;background:rgba(255,255,255,.92);box-shadow:0 5px 15px rgba(15,23,42,.06)}
.agency-profile-page .agency-profile-logo{display:block;width:100%;height:100%;object-fit:contain}
.agency-profile-page .agency-profile-logo-placeholder{display:grid;place-items:center;width:100%;height:100%;border-radius:13px;background:color-mix(in srgb,var(--agency-category-color) 10%,#fff);color:var(--agency-category-color);font-size:38px}
.agency-profile-page .agency-profile-heading{min-width:0;padding-top:2px}
.agency-profile-page .agency-profile-eyebrow{display:flex;align-items:center;gap:8px;color:var(--agency-category-color);font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
.agency-profile-page .agency-profile-eyebrow-dot{width:7px;height:7px;border-radius:50%;background:var(--agency-category-color);box-shadow:0 0 0 4px color-mix(in srgb,var(--agency-category-color) 13%,transparent)}
.agency-profile-page .agency-profile-heading h2{margin:9px 0 7px;color:var(--text-primary,#172033);font-size:clamp(21px,2.5vw,29px);font-weight:750;line-height:1.24;letter-spacing:-.035em;overflow-wrap:anywhere}
.agency-profile-page .agency-profile-abbreviation{color:var(--text-secondary,#64748b);font-size:.68em;font-weight:600;letter-spacing:0}
.agency-profile-page .agency-profile-description{max-width:850px;margin:0;color:var(--text-secondary,#64748b);font-size:13px;line-height:1.7;overflow-wrap:anywhere}
.agency-profile-page .agency-profile-badges{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:15px}
.agency-profile-page .agency-profile-badges .type-badge,.agency-profile-page .agency-profile-badges .category-badge,.agency-profile-page .agency-profile-missing-badge{display:inline-flex;align-items:center;gap:6px;min-height:27px;padding:5px 10px;border-radius:999px;font-size:11px;font-weight:650;line-height:1.3;white-space:normal}
.agency-profile-page .agency-profile-badges .type-badge{margin:0}
.agency-profile-page .agency-profile-badges .category-badge{color:var(--category-color);background:color-mix(in srgb,var(--category-color) 10%,#fff);border:1px solid color-mix(in srgb,var(--category-color) 28%,#fff)}
.agency-profile-page .agency-category-dot{width:7px;height:7px;flex:0 0 7px;border-radius:50%;background:var(--category-color)}
.agency-profile-page .agency-profile-missing-badge{color:#64748b;background:#f1f5f9;border:1px solid #e2e8f0}
.agency-profile-page .agency-profile-badges .type-badge.nga{background:#eaf2ff;color:#2563eb;border:1px solid #cfe0ff}
.agency-profile-page .agency-profile-badges .type-badge.ngo{background:#e8faf2;color:#047857;border:1px solid #c3f0dc}
.agency-profile-page .agency-profile-badges .type-badge.lgu{background:#f1edff;color:#6d43c5;border:1px solid #ddd3ff}
.agency-profile-page .agency-profile-badges .type-badge:not(.nga):not(.ngo):not(.lgu){background:#eff6ff;color:#315fbd;border:1px solid #dbeafe}
.agency-profile-page .agency-profile-edit-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;flex:0 0 auto;min-height:40px;padding:0 16px;border:1px solid #1d4ed8;border-radius:11px;background:#2563eb;color:#fff;font-size:12px;font-weight:700;box-shadow:0 5px 12px rgba(37,99,235,.2);cursor:pointer;transition:background .18s ease,transform .18s ease,box-shadow .18s ease}
.agency-profile-page .agency-profile-edit-button i{font-size:16px}
.agency-profile-page .agency-profile-edit-button:hover{transform:translateY(-1px);background:#1d4ed8;box-shadow:0 8px 17px rgba(37,99,235,.25)}
.agency-profile-page .agency-profile-hero-footer{display:none}
.agency-profile-page .agency-profile-section{padding:24px}
.agency-profile-page .agency-profile-section-heading{display:flex;align-items:center;gap:12px;margin-bottom:19px}
.agency-profile-page .agency-profile-section-icon{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;border-radius:13px;font-size:21px}
.agency-profile-page .agency-profile-section-icon-blue{color:#2563eb;background:#eaf2ff}
.agency-profile-page .agency-profile-section-icon-teal{color:#0f8a83;background:#e5faf7}
.agency-profile-page .agency-profile-section-heading h3{margin:0;color:var(--text-primary,#172033);font-size:15px;font-weight:750;letter-spacing:-.01em}
.agency-profile-page .agency-profile-section-heading p{margin:4px 0 0;color:var(--text-secondary,#64748b);font-size:11px;line-height:1.5}
.agency-profile-page .agency-profile-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.agency-profile-page .agency-profile-detail-card{display:flex;align-items:flex-start;gap:12px;min-width:0;padding:16px;border:1px solid #e6ebf3;border-radius:13px;background:linear-gradient(135deg,#fff,#fbfcff);transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}
.agency-profile-page .agency-profile-detail-card:hover{transform:translateY(-1px);border-color:#d3dff2;box-shadow:0 5px 14px rgba(30,64,175,.045)}
.agency-profile-page .agency-profile-detail-card>div{min-width:0;flex:1;padding-top:1px}
.agency-profile-page .agency-profile-detail-icon{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:10px;font-size:17px}
.agency-profile-page .detail-blue{color:#2563eb;background:#eaf2ff}.agency-profile-page .detail-violet{color:#7c3aed;background:#f1ebff}.agency-profile-page .detail-teal{color:#0f8a83;background:#e5faf7}.agency-profile-page .detail-amber{color:#b7791f;background:#fff5df}.agency-profile-page .detail-green{color:#16834a;background:#e8f8ef}.agency-profile-page .detail-rose{color:#d14370;background:#fff0f4}
.agency-profile-page .agency-profile-detail-card small{display:block;margin:0 0 6px;color:#64748b;font-size:10px;font-weight:650;letter-spacing:.035em}
.agency-profile-page .agency-profile-detail-card strong{display:block;color:#182338;font-size:12px;font-weight:650;line-height:1.6;overflow-wrap:anywhere}
.agency-profile-page .agency-profile-detail-card-wide{grid-column:1/-1}
.agency-profile-page .agency-profile-services{white-space:pre-line}
.agency-profile-page .agency-profile-map-link{display:inline-flex;align-items:center;gap:4px;margin-top:8px;color:#2563eb;font-size:11px;font-weight:650;text-decoration:none}
.agency-profile-page .agency-profile-map-link:hover{text-decoration:underline}
.agency-profile-page .agency-profile-contact-heading{margin-bottom:17px}
.agency-profile-page .agency-profile-contact-count{margin-left:auto;padding:6px 10px;border:1px solid #ccefe9;border-radius:999px;background:#effcf9;color:#087e77;font-size:10px;font-weight:700;white-space:nowrap}
.agency-profile-page .agency-profile-contact-table-wrap{overflow-x:auto;border:1px solid #e6ebf3;border-radius:12px}
.agency-profile-page .agency-profile-contact-table{width:100%;min-width:540px;margin:0;border-collapse:separate;border-spacing:0}
.agency-profile-page .agency-profile-contact-table thead th{padding:12px 15px;background:#f5f8fc;color:#64748b;font-size:9px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;border-bottom:1px solid #e6ebf3}
.agency-profile-page .agency-profile-contact-table tbody td{padding:13px 15px;color:#334155;font-size:11px;vertical-align:middle;border-bottom:1px solid #eef2f7}
.agency-profile-page .agency-profile-contact-table tbody tr:last-child td{border-bottom:0}
.agency-profile-page .agency-profile-contact-table tbody tr:hover td{background:#fbfdff}
.agency-profile-page .agency-profile-contact-type{display:inline-flex;padding:5px 8px;border-radius:7px;background:#f0f5ff;color:#355eb5;font-size:10px;font-weight:650}
.agency-profile-page .agency-profile-contact-value{max-width:380px;overflow-wrap:anywhere;font-weight:600}
.agency-profile-page .agency-profile-primary{display:inline-flex;align-items:center;gap:5px;color:#07805e;font-size:10px;font-weight:700;white-space:nowrap}
.agency-profile-page .agency-profile-primary i{font-size:14px}
.agency-profile-page .agency-profile-secondary{color:#94a3b8}
.agency-profile-page .agency-profile-empty-contacts{display:flex;align-items:center;flex-direction:column;padding:28px 18px;border:1px dashed #d8e2ef;border-radius:13px;background:linear-gradient(135deg,#f8fbff,#f7fdfc);text-align:center}
.agency-profile-page .agency-profile-empty-contacts>span{display:grid;place-items:center;width:44px;height:44px;margin-bottom:10px;border-radius:14px;background:#e5faf7;color:#0f8a83;font-size:22px}
.agency-profile-page .agency-profile-empty-contacts strong{color:#1e293b;font-size:12px}
.agency-profile-page .agency-profile-empty-contacts p{max-width:360px;margin:6px 0 0;color:#64748b;font-size:11px;line-height:1.6}
.agency-profile-page .agency-profile-alert{display:flex;align-items:flex-start;gap:9px;margin:0;padding:12px 14px;border-radius:12px}
.agency-profile-page .agency-profile-alert>i{margin-top:1px;font-size:17px}
.agency-profile-page .agency-profile-alert-error ul{margin:7px 0 0;padding-left:18px}
@media(max-width:760px){.agency-profile-page .agency-profile-hero{flex-direction:column;padding:22px;gap:18px}.agency-profile-page .agency-profile-edit-button{align-self:flex-start}.agency-profile-page .agency-profile-section{padding:20px}.agency-profile-page .agency-profile-identity{gap:15px}.agency-profile-page .agency-profile-logo-frame{flex-basis:78px;width:78px;height:78px;border-radius:16px}.agency-profile-page .agency-profile-logo-placeholder{font-size:30px}}
@media(max-width:560px){.agency-profile-page{gap:13px}.agency-profile-page .agency-profile-hero{padding:18px}.agency-profile-page .agency-profile-identity{align-items:flex-start;flex-direction:column;gap:13px}.agency-profile-page .agency-profile-logo-frame{flex-basis:70px;width:70px;height:70px}.agency-profile-page .agency-profile-heading h2{font-size:22px}.agency-profile-page .agency-profile-section{padding:17px}.agency-profile-page .agency-profile-details{grid-template-columns:minmax(0,1fr);gap:9px}.agency-profile-page .agency-profile-detail-card-wide{grid-column:auto}.agency-profile-page .agency-profile-contact-count{font-size:9px;padding:5px 8px}.agency-profile-page .agency-profile-section-heading{gap:10px}.agency-profile-page .agency-profile-section-icon{flex-basis:38px;width:38px;height:38px}}
/* Agency profile hero refinement: keep identity content contained and readable at every width. */
.agency-profile-page .agency-profile-hero{
    align-items:center;
    gap:22px;
    padding:26px 28px;
    border-color:color-mix(in srgb,var(--agency-category-color) 16%,#e2e8f0);
    background:linear-gradient(115deg,color-mix(in srgb,var(--agency-category-color) 4%,#fff) 0%,#fff 58%,#f8faff 100%);
}
.agency-profile-page .agency-profile-hero:before{width:4px}
.agency-profile-page .agency-profile-hero-glow{top:-145px;right:5%;width:230px;height:230px;opacity:.45}
.agency-profile-page .agency-profile-identity{align-items:center;gap:18px}
.agency-profile-page .agency-profile-logo-frame{overflow:hidden;min-width:0}
.agency-profile-page .agency-profile-logo{min-width:0;max-width:100%;font-size:0;color:transparent}
.agency-profile-page .agency-profile-logo-placeholder[hidden]{display:none!important}
.agency-profile-page .agency-profile-logo-placeholder{width:100%;height:100%;border-radius:12px}
.agency-profile-page .agency-profile-heading{flex:1;min-width:0}
.agency-profile-page .agency-profile-heading h2{max-width:850px;margin:8px 0 8px;font-size:clamp(20px,2.2vw,28px);line-height:1.2;letter-spacing:-.028em;overflow-wrap:anywhere;word-break:normal}
.agency-profile-page .agency-profile-description{max-width:760px;font-size:12px;line-height:1.65}
.agency-profile-page .agency-profile-badges{gap:7px;margin-top:13px}
.agency-profile-page .agency-profile-edit-button{position:relative;z-index:1;min-height:42px;padding:0 15px;white-space:nowrap}
@media(max-width:760px){
    .agency-profile-page .agency-profile-hero{align-items:stretch;padding:21px;gap:17px}
    .agency-profile-page .agency-profile-identity{align-items:flex-start;flex-direction:row;gap:14px;width:100%}
    .agency-profile-page .agency-profile-logo-frame{flex:0 0 68px;width:68px;height:68px;padding:6px;border-radius:16px}
    .agency-profile-page .agency-profile-logo-placeholder{font-size:27px}
    .agency-profile-page .agency-profile-heading{padding-top:1px}
    .agency-profile-page .agency-profile-heading h2{font-size:clamp(19px,3.4vw,24px);line-height:1.24}
    .agency-profile-page .agency-profile-edit-button{align-self:flex-start}
}
@media(max-width:560px){
    .agency-profile-page .agency-profile-hero{padding:17px 16px 16px;gap:15px;border-radius:16px}
    .agency-profile-page .agency-profile-hero-glow{top:-115px;right:-90px;width:190px;height:190px;opacity:.28}
    .agency-profile-page .agency-profile-identity{align-items:flex-start;flex-direction:row;gap:12px}
    .agency-profile-page .agency-profile-logo-frame{flex:0 0 56px;width:56px;height:56px;padding:5px;border-radius:14px}
    .agency-profile-page .agency-profile-logo-placeholder{font-size:24px}
    .agency-profile-page .agency-profile-heading{flex:1;width:calc(100% - 68px);min-width:0}
    .agency-profile-page .agency-profile-eyebrow{gap:6px;font-size:9px;letter-spacing:.1em;line-height:1.35}
    .agency-profile-page .agency-profile-eyebrow-dot{flex:0 0 6px;width:6px;height:6px}
    .agency-profile-page .agency-profile-heading h2{margin:7px 0;font-size:clamp(18px,5.4vw,21px);line-height:1.23;letter-spacing:-.025em;overflow-wrap:anywhere}
    .agency-profile-page .agency-profile-abbreviation{font-size:.72em}
    .agency-profile-page .agency-profile-description{font-size:11px;line-height:1.55}
    .agency-profile-page .agency-profile-badges{gap:6px;margin-top:11px}
    .agency-profile-page .agency-profile-badges .type-badge,.agency-profile-page .agency-profile-badges .category-badge,.agency-profile-page .agency-profile-missing-badge{min-height:25px;padding:4px 8px;font-size:10px}
    .agency-profile-page .agency-profile-edit-button{align-self:stretch;width:100%;min-height:40px}
}
@media(max-width:360px){
    .agency-profile-page .agency-profile-hero{padding:15px 13px}
    .agency-profile-page .agency-profile-identity{gap:10px}
    .agency-profile-page .agency-profile-logo-frame{flex-basis:48px;width:48px;height:48px;padding:4px;border-radius:12px}
    .agency-profile-page .agency-profile-heading{width:calc(100% - 58px)}
    .agency-profile-page .agency-profile-heading h2{font-size:18px}
    .agency-profile-page .agency-profile-description{font-size:10.5px}
}
@media(prefers-reduced-motion:reduce){.agency-profile-page *{transition:none!important;scroll-behavior:auto!important}}
</style>

<script>
window.agencyAdminProfileMode = true;
window.agencyAdminProfileUpdateUrl = @json(route('agency-admin.agency.update'));
window.agencyProfileData = @json($agencyProfileData);
window.agencyAdminProfileOpenOnLoad = @json($errors->any());

window.agencyContactTypes = {!! $contactTypes->values()->toJson() !!};
</script>
<div id="modal-back" class="back">

    <div class="modal">

        <!-- HEADER -->
        <div class="modal-header admin-modal-header">
            <div class="admin-modal-heading">
                <div class="admin-modal-icon" aria-hidden="true">
                    <i class="ph-light ph-buildings"></i>
                </div>
                <div>
                    <span class="admin-modal-eyebrow">Agency management</span>
                    <h2 id="modal-title">Agency</h2>
                </div>
            </div>

            <div class="modal-actions">
                <button type="submit" form="agencyForm" class="btn-save">Save</button>
                <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>

        <form 
            id="agencyForm" 
            method="POST" 
            action="{{ route('agency-admin.agency.update') }}" 
            enctype="multipart/form-data" 
            novalidate
        >
            @csrf
            <input type="hidden" name="_method" id="form-method" value="POST">

            <!-- ================= IMAGE ================= -->
            <div class="form-card">

                <label class="upload-label">Agency Image</label>

                <div class="image-upload-box" id="agency-upload-box">

                    <!-- FILE INPUT (HIDDEN FOR SECURITY UX CONTROL) -->
                    <input 
                        type="file" 
                        name="agency_image" 
                        id="agency_image" 
                        accept="image/png, image/jpeg, image/webp"
                        hidden
                    >

                    <!-- UPLOAD UI -->
                    <div class="upload-content" id="agency-upload-placeholder">
                        <i class="ph-light ph-image"></i>
                        <p>Click to upload image</p>
                        <span>PNG, JPG, WebP up to 5MB</span>
                    </div>

                    <!-- PREVIEW -->
                    <img id="agency-preview" class="faq-preview-img" style="display:none;">
                </div>

            </div>

            <!-- ================= AGENCY INFO ================= -->
            <div class="form-card">
                <label>Agency Info</label>

                <!-- REQUIRED -->
                <div class="floating-group" data-validate="required">
                    <textarea
                        name="agency_name"
                        id="agency_name"
                        placeholder=" "
                        rows="2"
                        required
                        maxlength="255"
                    ></textarea>

                    <label for="agency_name">
                        Agency Name
                    </label>

                    <span class="form-message"></span>
                </div>

                <!-- ABBREVIATION -->
                <div class="floating-group" data-validate="required">

                    <input
                        type="text"
                        name="agency_abbreviation"
                        id="agency_abbreviation"
                        placeholder=" "
                        required
                    >

                    <label for="agency_abbreviation">
                        Abbreviation
                    </label>

                    <span class="form-message"></span>

                </div>

                <!-- AGENCY TYPE -->
                <div class="floating-group">

                    <select 
                        name="agency_type_id" 
                        id="agency_type_id" 
                        required
                    >
                        <option value="" disabled selected hidden></option>

                        @foreach($types as $type)
                            <option value="{{ $type->id }}">
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>

                    <label for="agency_type_id">
                        Agency Type
                    </label>

                </div>

                <!-- CATEGORY -->
                <div class="floating-group">

                    <select
                        name="category_id"
                        id="category_id"
                        required
                    >
                        <option value="" disabled selected hidden></option>

                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>

                    <label for="category_id">
                        Category
                    </label>

                </div>

                <!-- OPTIONAL (NO STRICT VALIDATION) -->
                {{-- DESCRIPTION --}}
                <div class="floating-group" data-validate="optional-text">

                    <textarea
                        name="agency_description"
                        id="agency_description"
                        placeholder=" "
                        maxlength="5000"
                    ></textarea>

                    <label for="agency_description">
                        Description
                    </label>

                    <span class="form-message"></span>

                </div>


                {{-- OFFICE HEAD INFORMATION --}}
                <div class="agency-section-label">
                    Office Head Information
                </div>


                {{-- OFFICE HEAD NAME --}}
                <div class="floating-group" data-validate="optional-text">

                    <input
                        type="text"
                        name="office_head_name"
                        id="office_head_name"
                        placeholder=" "
                        maxlength="255"
                    >

                    <label for="office_head_name">
                        Office Head Name
                    </label>

                    <span class="form-message"></span>

                </div>


                {{-- OFFICE HEAD POSITION --}}
                <div class="floating-group" data-validate="optional-text">

                    <input
                        type="text"
                        name="office_head_position"
                        id="office_head_position"
                        placeholder=" "
                        maxlength="255"
                    >

                    <label for="office_head_position">
                        Office Head Position
                    </label>

                    <span class="form-message"></span>

                </div>


                {{-- SERVICES OFFERED --}}
                <div class="floating-group" data-validate="optional-text">

                    <textarea
                        name="services_offered"
                        id="services_offered"
                        placeholder=" "
                    ></textarea>

                    <label for="services_offered">
                        Services Offered
                    </label>

                    <span class="form-message"></span>

                </div>
            </div>

            

            <!-- ================= CONTACT ================= -->
<div class="form-card">

    <label>Contact Information</label>

    <p class="form-helper">
        Add the agency's available contact information.
        At least one Hotline and one Email are required.
Additional contact information may be added.
    </p>

    <!--
        JavaScript will dynamically generate the individual
        contact fields inside this container.

        Each generated contact will submit data using:

        contacts[index][contact_type_id]
        contacts[index][label]
        contacts[index][value]
        contacts[index][is_primary]
        contacts[index][sort_order]
    -->
    <div
        id="agency-contacts"
        class="agency-contacts"
    ></div>


    <!--
        JavaScript uses this button to append another
        contact entry.
    -->
    <button
        type="button"
        id="add-contact-btn"
        class="add-contact-btn"
    >
        <i class="ph-light ph-plus"></i>
        Add Contact
    </button>


    <!--
        Backend validation errors for the entire contact
        collection will be displayed here.
    -->
    <span
        id="contacts-form-message"
        class="form-message"
    ></span>

</div>

            <!-- ================= OFFICE HOURS ================= -->
            <div class="form-card">

                <label>Office Hours</label>

                <div
                    class="floating-group"
                    data-validate="required"
                >

                    <textarea
                        name="office_hours"
                        id="office_hours"
                        placeholder=" "
                        required
                    ></textarea>

                    <label for="office_hours">
                        Office Hours
                    </label>

                    <span class="form-message"></span>

                </div>

            </div>

            <!-- ================= LOCATION ================= -->
            <div class="form-card">
                <label>Location</label>

                <!-- REQUIRED -->
                <div class="floating-group" data-validate="required">
                    <input type="text" name="agency_location" id="agency_location" placeholder=" " required>
                    <label for="agency_location">Address</label>
                    <span class="form-message"></span>
                </div>

                <!-- 🔍 SEARCH -->
                <div class="map-search">
                    <div class="floating-group" style="flex:1;">
                        <input id="searchLocation" type="text" placeholder=" ">
                        <label for="searchLocation">Search location</label>
                    </div>

                    <button type="button" id="searchBtn">
                        Search
                    </button>
                </div>

                <!-- 🗺 MAP -->
                <div id="map"></div>

                <!-- 📍 COORDS -->
                <div class="coord-row">

                    <!-- SYSTEM GENERATED -->
                    <div class="floating-group">
                        <input type="text" id="lat" name="lat" placeholder=" ">
                        <label for="lat">Latitude</label>
                    </div>

                    <div class="floating-group">
                        <input type="text" id="lng" name="lng" placeholder=" ">
                        <label for="lng">Longitude</label>
                    </div>

                </div>
            </div>
        </form>

    </div>
</div>

<script src="{{ asset('jsfiles/components/form-system.js') }}"></script>
@endsection

@push('scripts')
<script src="{{ asset('jsfiles/admin/nga-management.js') }}"></script>
@endpush
