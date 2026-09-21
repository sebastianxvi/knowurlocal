<div
    id="support-modal-back"
    class="support-modal-back"
    aria-hidden="true"
>

    <section
        class="support-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="support-modal-title"
    >

        {{-- =====================================================
             HEADER
             ===================================================== --}}

        <header class="support-modal-header">

            <div class="support-modal-heading">

                <div
                    class="support-modal-icon"
                    aria-hidden="true"
                >
                    <i class="ph-light ph-chat-centered-text"></i>
                </div>

                <div>

                    <span class="support-modal-eyebrow">
                        Support ticket
                    </span>

                    <h2 id="support-modal-title">
                        Prepare Official Response
                    </h2>

                </div>

            </div>


            <button
                type="button"
                class="support-modal-close"
                onclick="closeSupportModal()"
                aria-label="Close support request"
            >

                <i
                    class="ph-light ph-x"
                    aria-hidden="true"
                ></i>

            </button>

        </header>


        {{-- =====================================================
             FORM
             ===================================================== --}}

        <form
            id="reply-form"
            method="POST"
            action="{{ route('admin.support.forward-response') }}"
            enctype="multipart/form-data"
            data-forward-url="{{ route('admin.support.forward-response') }}"
            data-update-url="{{ url('/admin/support-requests') }}"
            data-latest-response-url="{{ route('admin.support.latest-response', ['id' => '__ID__']) }}"
        >

            @csrf


            {{-- Laravel method spoofing --}}
            <input
                type="hidden"
                name="_method"
                id="form-method"
                value="POST"
            >


            {{-- Support request identifier --}}
            <input
                type="hidden"
                name="request_id"
                id="sr-id"
            >


            <div class="support-modal-body">

                {{-- =================================================
                     REQUEST INFORMATION
                     ================================================= --}}

                <section class="support-modal-section">

                    <div class="support-modal-section-heading">

                        <span>
                            Request details
                        </span>

                    </div>


                    <div class="support-request-details-grid">

                        {{-- USER --}}
                        <div class="support-detail-item">

                            <span class="support-detail-label">
                                User
                            </span>

                            <div class="support-detail-value">

                                <input
                                    type="text"
                                    id="sr-user"
                                    readonly
                                    aria-label="User"
                                >

                            </div>

                        </div>


                        {{-- AGENCY --}}
                        <div class="support-detail-item">

                            <label
                                for="sr-agency-search"
                                class="support-detail-label"
                            >
                                Agency
                            </label>


                            <div
                                class="support-agency-selector"
                                id="support-agency-searchable"
                            >

                                {{-- Visible searchable field --}}
                                <input
                                    type="text"
                                    id="sr-agency-search"
                                    autocomplete="off"
                                    placeholder="Select an agency..."
                                    role="combobox"
                                    aria-expanded="false"
                                    aria-controls="sr-agency-options"
                                    aria-autocomplete="list"
                                    required
                                >


                                <i
                                    class="ph-light ph-caret-down support-agency-caret"
                                    aria-hidden="true"
                                ></i>


                                {{-- Search results --}}
                                <div
                                    id="sr-agency-options"
                                    class="support-agency-options"
                                    role="listbox"
                                ></div>

                            </div>


                            {{-- Actual Laravel field --}}
                            <select
                                id="sr-agency"
                                name="agency_id"
                                tabindex="-1"
                                aria-hidden="true"
                                required
                            >

                                <option value="">
                                    Select an agency
                                </option>

                                @foreach($agencies as $agency)

                                    <option
                                        value="{{ $agency->id }}"
                                        data-full-name="{{ $agency->agency_name }}"
                                        data-abbr="{{ $agency->agency_abbreviation ?? '' }}"
                                    >
                                        {{ $agency->agency_name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </section>


                {{-- =================================================
                     ORIGINAL QUESTION
                     ================================================= --}}

                <section class="support-modal-section">

                    <div class="support-modal-section-heading">

                        <span>
                            Citizen's question
                        </span>

                    </div>


                    <div class="support-question-display">

                        <div
                            class="support-question-display-icon"
                            aria-hidden="true"
                        >
                            <i class="ph-light ph-quotes"></i>
                        </div>


                        <textarea
                            id="sr-question"
                            readonly
                            aria-label="Citizen's question"
                        ></textarea>

                    </div>

                </section>


                {{-- =================================================
                     OFFICIAL RESPONSE
                     ================================================= --}}

                <section class="support-modal-section">

                    <div class="support-modal-section-heading">

                        <div>

                            <span>
                                Official response
                            </span>

                            <small>
                                Build the information you want to forward to the citizen.
                            </small>

                        </div>

                    </div>


                    @include('admin.support-requests.components.response-builder')

                </section>

            </div>


            {{-- =================================================
                 MODAL FOOTER
                 ================================================= --}}

            <footer class="support-modal-footer">

                <button
                    type="button"
                    class="support-modal-button support-modal-cancel"
                    onclick="closeSupportModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    id="forward-response-btn"
                    class="support-modal-button support-modal-save"
                >
                    <i
                        class="ph-light ph-paper-plane-tilt"
                        aria-hidden="true"
                    ></i>

                    <span data-submit-label>
                        Forward to Citizen
                    </span>
                </button>

            </footer>

        </form>

    </section>

</div>