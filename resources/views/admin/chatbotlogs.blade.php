@extends('layouts.admin')

@push('styles')

<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/logs.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'Chatbot Logs')
@section('page-subtitle', 'Monitor chatbot interactions')

@section('content')

<div class="logs-page">


<!-- FILTER -->
<div class="filter-card">

    <!-- LEFT -->
    <form method="GET" class="filter-bar">

        <!-- SEARCH -->
        <input 
            type="text" 
            name="search" 
            value="{{ request('search') }}" 
            placeholder="Search question or answer"
        >

        <!-- TYPE -->
        <select name="type">
            <option value="">All Types</option>

            @foreach($availableTypes as $type)
                <option 
                    value="{{ $type }}" 
                    {{ request('type') == $type ? 'selected' : '' }}
                >
                    {{ ucfirst(str_replace('_',' ', $type)) }}
                </option>
            @endforeach
        </select>

        <!-- DATE -->
        <select name="date">
            <option value="">All Dates</option>

            @foreach($availableDates as $date)
                <option 
                    value="{{ $date }}" 
                    {{ request('date') == $date ? 'selected' : '' }}
                >
                    {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                </option>
            @endforeach
        </select>

        <!-- SORT -->
        <select name="sort">
            <option value="desc" {{ request('sort') == 'desc' ? 'selected' : '' }}>
                Newest First
            </option>
            <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>
                Oldest First
            </option>
        </select>

        <button type="submit">Filter</button>

    </form>

</div>

<!-- TABLE -->
<div class="table-wrapper">

    <table class="table">

        <thead>
            <tr>
                <th>User</th>
                <th>Question</th>
                <th>Answer</th>
                <th>Agency</th>
                <th>Type</th>
                <th>Score</th>
                <th>Date</th>
            </tr>
        </thead>

        <tbody>

            @forelse($logs as $log)
            <tr>

                <td>
                    <div class="actor-cell">

                        @if($log->user)
                            <span class="actor-name">
                                {{ $log->user->first_name }} {{ $log->user->last_name }}
                            </span>
                        @else
                            <span class="actor-name text-muted">
                                Guest
                            </span>
                        @endif

                    </div>
                </td>

                <!-- QUESTION -->
                <td>
                    {{ \Illuminate\Support\Str::limit($log->question, 60) }}
                </td>

                <!-- ANSWER -->
                <td>
                    {{ \Illuminate\Support\Str::limit(strip_tags($log->answer), 60) }}
                </td>

                <!-- AGENCY -->
                <td>
                    {{ $log->agency->agency_name ?? '-' }}
                </td>

                <!-- TYPE -->
                <td>
                    <span class="badge action {{ $log->type }}">

                        <i class="ph-light 
                            @switch($log->type)

                                @case('faq') ph-check-circle @break
                                @case('options') ph-list @break
                                @case('greeting') ph-hand @break
                                @case('thanks') ph-smiley @break
                                @case('fallback') ph-warning-circle @break
                                @case('wrong_agency') ph-arrow-bend-up-left @break
                                @case('irrelevant') ph-dots-three-outline @break

                                @default ph-chat-centered-text

                            @endswitch
                        "></i>

                        {{ ucfirst(str_replace('_',' ', $log->type)) }}
                    </span>
                </td>

                <!-- SCORE -->
                <td>
                    @if($log->score !== null)

                        @php
                            $score = $log->score;

                            // normalize score (handles both 0-1 and 0-100 inputs)
                            $percent = $score > 1 ? $score : $score * 100;

                            $percent = round($percent);
                        @endphp

                        @php
                            $percent = round($percent);
                        @endphp

                        <span class="badge action 
                            {{ $percent >= 80 ? 'score-high' : '' }}
                            {{ $percent >= 50 && $percent < 80 ? 'score-medium' : '' }}
                            {{ $percent < 50 ? 'score-low' : '' }}
                        ">

                            <i class="ph-light 
                                {{ $percent >= 80 ? 'ph-check-circle' : '' }}
                                {{ $percent >= 50 && $percent < 80 ? 'ph-chart-line' : '' }}
                                {{ $percent < 50 ? 'ph-warning-circle' : '' }}
                            "></i>

                            {{ $percent }}%
                        </span>

                    @else
                        -
                    @endif
                </td>

                <!-- DATE -->
                <td>
                    {{ $log->created_at->format('M d, Y H:i') }}
                </td>

            </tr>
            @empty
            <tr>
                <td colspan="6" class="empty">
                    No chatbot logs found.
                </td>
            </tr>
            @endforelse

        </tbody>

    </table>

    <!-- FOOTER -->
    <div class="footer">

        <div class="result-info">
            Showing {{ $logs->firstItem() ?? 0 }} 
            to {{ $logs->lastItem() ?? 0 }} 
            of {{ $logs->total() }} results
        </div>

        <div class="pagination-modern">

            @if ($logs->onFirstPage())
                <span class="arrow disabled">
                    <svg viewBox="0 0 24 24" width="14" height="14">
                        <path d="M15 6L9 12L15 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="arrow">
                    <svg viewBox="0 0 24 24" width="14" height="14">
                        <path d="M15 6L9 12L15 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            @endif

            <span class="page-indicator">
                Page {{ $logs->currentPage() }}
            </span>

            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="arrow">
                    <svg viewBox="0 0 24 24" width="14" height="14">
                        <path d="M9 6L15 12L9 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            @else
                <span class="arrow disabled">
                    <svg viewBox="0 0 24 24" width="14" height="14">
                        <path d="M9 6L15 12L9 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            @endif

        </div>

    </div>

</div>


</div>

@endsection
