<div class="support-table-shell">

    <div class="support-table-scroll">

        <table class="support-table">

            <caption class="sr-only">
                Support requests
            </caption>


            <thead>

                <tr>

                    <th
                        scope="col"
                        class="support-col-id"
                    >
                        ID
                    </th>

                    <th
                        scope="col"
                        class="support-col-user"
                    >
                        User
                    </th>

                    <th
                        scope="col"
                        class="support-col-question"
                    >
                        Question
                    </th>

                    <th
                        scope="col"
                        class="support-col-agency"
                    >
                        Agency
                    </th>

                    <th
                        scope="col"
                        class="support-col-status"
                    >
                        Status
                    </th>

                    <th
                        scope="col"
                        class="support-col-date"
                    >
                        Date
                    </th>

                    <th
                        scope="col"
                        class="support-col-actions"
                    >
                        <span class="sr-only">
                            Actions
                        </span>
                    </th>

                </tr>

            </thead>


            <tbody
                id="support-requests-table-body"
                data-is-superadmin="{{ auth()->user()->role === 'superadmin' ? 'true' : 'false' }}"
                data-delete-url="{{ url('/admin/support-requests') }}"
                data-faq-url="{{ url('/admin/support-requests') }}"
                data-similar-faq-url="{{ url('/admin/support-requests') }}"
                data-status="{{ $status }}"
                data-status-filter="{{ $statusFilter ?? '' }}"
                data-agency="{{ request('agency', '') }}"
                data-search="{{ $search ?? '' }}"
            >

                @forelse($requests as $request)

                    <tr
                        data-request-id="{{ $request->id }}"
                        class="support-request-row"
                    >

                        {{-- =================================================
                             ID
                             ================================================= --}}
                        <td class="support-request-id">

                            <span>
                                #{{ $request->id }}
                            </span>

                        </td>


                        {{-- =================================================
                             USER
                             ================================================= --}}
                        <td class="support-request-user">

                            <div class="support-user-cell">

                                <div class="support-user-details">

                                    <span class="support-user-name">
                                        {{ $request->user->first_name ?? 'Guest' }}
                                    </span>

                                </div>

                            </div>

                        </td>


                        {{-- =================================================
                             QUESTION
                             ================================================= --}}
                        <td class="support-request-question">

                            <div class="support-question-cell">

                                <span
                                    class="support-question-text"
                                    title="{{ $request->question }}"
                                >
                                    {{ Str::limit($request->question, 90) }}
                                </span>

                            </div>

                        </td>


                        {{-- =================================================
                             AGENCY
                             ================================================= --}}
                        <td class="support-request-agency">

                            @if($request->agency)

                                <span class="support-agency-name">
                                    {{ $request->agency->agency_name }}
                                </span>

                            @else

                                <span class="support-agency-empty">
                                    Unassigned
                                </span>

                            @endif

                        </td>


                        {{-- =================================================
                             STATUS
                             ================================================= --}}
                        <td class="support-request-status">

                            <span
                                class="support-status-badge {{ $request->status }}"
                            >

                                <span
                                    class="support-status-dot"
                                    aria-hidden="true"
                                ></span>

                                {{ ucfirst(str_replace('_', ' ', $request->status)) }}

                            </span>

                        </td>


                        {{-- =================================================
                             DATE
                             ================================================= --}}
                        <td class="support-request-date">

                            <time
                                datetime="{{ $request->created_at?->toIso8601String() }}"
                            >
                                {{ $request->created_at?->format('M d, Y') }}
                            </time>

                        </td>


                        {{-- =================================================
                             ACTIONS
                             ================================================= --}}
                        <td class="support-request-actions">

                            <div class="support-row-actions">


                                {{-- =================================================
                                     ACTIVE REQUEST ACTIONS
                                     ================================================= --}}
                                @if($status === 'active')

                                    {{-- MANAGE --}}
                                    <button
                                        type="button"
                                        class="support-action-primary view-btn"

                                        data-id="{{ $request->id }}"

                                        data-user="{{ $request->user->first_name ?? 'Guest' }}"

                                        data-question="{{ $request->question }}"

                                        data-agency="{{ $request->agency->agency_name ?? '' }}"

                                        data-agency-id="{{ $request->agency_id ?? '' }}"

                                        data-answer="{{ $request->answer ?? '' }}"

                                        data-answer-image="{{ $request->answer_image ?? '' }}"

                                        data-status="{{ $request->status }}"

                                        aria-label="Manage support request #{{ $request->id }}"
                                    >

                                        <i
                                            class="ph-light ph-chat-centered-text"
                                            aria-hidden="true"
                                        ></i>

                                        <span>
                                            Manage
                                        </span>

                                    </button>


                                    {{-- SECONDARY ACTIONS --}}
                                    @if(auth()->user()->role === 'superadmin')

                                        <div class="support-action-menu">

                                            <button
                                                type="button"
                                                class="support-action-menu-trigger"
                                                aria-label="More actions for support request #{{ $request->id }}"
                                                aria-expanded="false"
                                                aria-haspopup="menu"
                                            >

                                                <i
                                                    class="ph-light ph-dots-three-vertical"
                                                    aria-hidden="true"
                                                ></i>

                                            </button>


                                            <div
                                                class="support-action-menu-content"
                                                role="menu"
                                            >

                                                {{-- MOVE TO TRASH --}}
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'admin.support.delete',
                                                        $request->id
                                                    ) }}"
                                                    class="support-lifecycle-form"
                                                >

                                                    @csrf

                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="support-menu-action support-menu-danger delete-btn"
                                                        role="menuitem"
                                                    >

                                                        <i
                                                            class="ph-light ph-trash"
                                                            aria-hidden="true"
                                                        ></i>

                                                        <span>
                                                            Move to trash
                                                        </span>

                                                    </button>

                                                </form>


                                                {{-- ADD TO FAQ --}}
                                                <a
                                                    href="{{ route(
                                                        'admin.support.toFaq',
                                                        $request->id
                                                    ) }}"
                                                    class="support-menu-action support-menu-faq faq-btn"

                                                    data-id="{{ $request->id }}"

                                                    data-similar-url="{{ route(
                                                        'admin.support.similarFaqs',
                                                        $request->id
                                                    ) }}"

                                                    role="menuitem"
                                                >

                                                    <i
                                                        class="ph-light ph-chat-centered-dots"
                                                        aria-hidden="true"
                                                    ></i>

                                                    <span>
                                                        Add to FAQ
                                                    </span>

                                                </a>

                                            </div>

                                        </div>

                                    @endif


                                {{-- =================================================
                                     TRASHED REQUEST ACTIONS
                                     ================================================= --}}
                                @elseif($status === 'trashed')

                                    @if(auth()->user()->role === 'superadmin')

                                        <div class="support-trash-actions">

                                            {{-- RESTORE --}}
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'admin.support.restore',
                                                    $request->id
                                                ) }}"
                                                class="support-lifecycle-form"
                                            >

                                                @csrf

                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="support-action-primary restore-btn"
                                                >

                                                    <i
                                                        class="ph-light ph-arrow-counter-clockwise"
                                                        aria-hidden="true"
                                                    ></i>

                                                    <span>
                                                        Restore
                                                    </span>

                                                </button>

                                            </form>


                                            {{-- PERMANENT DELETE --}}
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'admin.support.forceDelete',
                                                    $request->id
                                                ) }}"
                                                class="support-lifecycle-form"
                                            >

                                                @csrf

                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="support-menu-action support-menu-danger permanent-delete-btn"
                                                >

                                                    <i
                                                        class="ph-light ph-trash-simple"
                                                        aria-hidden="true"
                                                    ></i>

                                                    <span>
                                                        Delete Permanently
                                                    </span>

                                                </button>

                                            </form>

                                        </div>

                                    @endif

                                @endif

                            </div>

                        </td>

                    </tr>


                @empty

                    <tr class="support-empty-row">

                        <td colspan="7">

                            <div class="support-empty-state">

                                <div
                                    class="support-empty-icon"
                                    aria-hidden="true"
                                >
                                    <i class="ph-light ph-chat-circle"></i>
                                </div>


                                <div class="support-empty-content">

                                    <h3>

                                        @if($status === 'trashed')

                                            No support requests in the trash.

                                        @else

                                            No support requests found

                                        @endif

                                    </h3>

                                    <p>

                                        @if($status === 'trashed')

                                            Deleted support requests will appear here.

                                        @else

                                            There are no requests matching the current filters.

                                        @endif

                                    </p>

                                </div>

                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>