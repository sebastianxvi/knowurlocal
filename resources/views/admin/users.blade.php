@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/admin-modules.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'User Management')
@section('page-subtitle', 'View and manage registered users')

@section('content')

<div class="admin-page">

    <!-- ================= FILTER ================= -->
    <div class="filter-card">

        <form method="GET" action="{{ route('admin.users') }}" class="filter-bar">

            <!-- SEARCH -->
            <input 
                type="text" 
                name="search"
                placeholder="Search user..."
                value="{{ request('search') }}"
            >

            <!-- SORT -->
            <select name="sort">
                <option value="desc" {{ request('sort') == 'desc' ? 'selected' : '' }}>Newest First</option>
                <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Oldest First</option>
            </select>

            <button type="submit">Filter</button>

        </form>

    </div>

    <!-- ================= TABLE ================= -->
    <div class="table-wrapper">

        <table class="table">

            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Registered</th>

                    @if(auth()->user()->role === 'superadmin')
                        <th>Action</th>
                    @endif
                </tr>
            </thead>

            <tbody>

                @forelse($users as $user)
                <tr>

                    <!-- USER -->
                    <td>
                        <div class="actor-cell">
                            <span class="actor-name">
                                {{ $user->first_name }} {{ $user->last_name }}
                            </span>
                        </div>
                    </td>

                    <!-- EMAIL -->
                    <td>{{ $user->email }}</td>

                    <!-- ROLE -->
                    <td>
                        <span class="role-badge {{ $user->role }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>

                    <!-- DATE -->
                    <td>
                        {{ $user->created_at->format('M d, Y') }}
                    </td>

                    @if(auth()->user()->role === 'superadmin')
                    <td class="tablebtn">

                        @php
                            $isSelf = $user->id === auth()->id();
                        @endphp

                        @if(!$isSelf)
                        <form method="POST" action="{{ route('admin.users.delete', $user->id) }}">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-danger delete-btn">
                                Delete
                            </button>
                        </form>
                        @else
                            <span style="font-size:12px; color:var(--text-muted);">—</span>
                        @endif

                    </td>
                    @endif

                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty">
                        No users found.
                    </td>
                </tr>
                @endforelse

            </tbody>

        </table>

        <!-- ================= FOOTER ================= -->
        <div class="footer">

            <span class="result-info">
                Showing {{ $users->firstItem() ?? 0 }} 
                to {{ $users->lastItem() ?? 0 }} 
                of {{ $users->total() }} results
            </span>

            <div class="pagination-modern">

                @if ($users->onFirstPage())
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-left"></i>
                    </span>
                @else
                    <a href="{{ $users->previousPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-left"></i>
                    </a>
                @endif

                <span class="page-indicator">
                    Page {{ $users->currentPage() }}
                </span>

                @if ($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-right"></i>
                    </a>
                @else
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-right"></i>
                    </span>
                @endif

            </div>

        </div>

    </div>

</div>

@endsection