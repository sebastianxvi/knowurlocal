<div
    id="support-response-builder"
    class="support-response-builder"
>

    {{-- =========================================================
     LATEST OFFICIAL RESPONSE
     ========================================================= --}}

<section
    id="support-response-latest"
    class="support-response-latest"
    hidden
    aria-labelledby="support-response-latest-title"
>

    <div class="support-response-latest-header">

        <div>
            <span class="support-response-section-eyebrow">
                Latest response
            </span>

            <strong id="support-response-latest-title">
                Latest Official Response
            </strong>

            <span class="support-response-section-description">
                The most recent response previously forwarded to the citizen.
            </span>
        </div>

    </div>


    <div
        id="support-response-latest-components"
        class="support-response-latest-components"
    ></div>

</section>


{{-- =========================================================
     RESPONSE HISTORY
     ========================================================= --}}

<section
    id="support-response-history"
    class="support-response-history"
    hidden
    aria-labelledby="support-response-history-title"
>

    <div class="support-response-history-header">

        <div>
            <span class="support-response-section-eyebrow">
                Previous responses
            </span>

            <strong id="support-response-history-title">
                Response History
            </strong>

            <span class="support-response-section-description">
                Previous official response attempts for this ticket.
            </span>
        </div>

    </div>


    <div
        id="support-response-history-components"
        class="support-response-history-components"
    ></div>


    <div class="support-response-history-footer">

        <button
            type="button"
            id="support-response-history-load-more"
            class="support-response-history-load-more"
            hidden
            aria-hidden="true"
        >

            <i
                class="ph-light ph-arrow-down"
                aria-hidden="true"
            ></i>

            <span>
                Load Older Responses
            </span>

        </button>

    </div>

</section>


    {{-- =========================================================
         NEW OFFICIAL RESPONSE
         ========================================================= --}}

    <section
        class="support-response-new"
        aria-labelledby="support-response-new-title"
    >

        <div class="support-response-new-header">

            <div>
                <span class="support-response-section-eyebrow">
                    New response
                </span>

                <strong id="support-response-new-title">
                    Prepare Official Response
                </strong>

                <span class="support-response-section-description">
                    Build the information you want to forward to the citizen.
                </span>
            </div>

        </div>


        {{-- Dynamic response components --}}

        <div
            id="support-response-components"
            class="support-response-components"
            aria-live="polite"
        ></div>


        {{-- Empty state --}}

        <div
            id="support-response-empty"
            class="support-response-empty"
        >

            <div
                class="support-response-empty-icon"
                aria-hidden="true"
            >
                <i class="ph-light ph-note-pencil"></i>
            </div>

            <div class="support-response-empty-content">

                <strong>
                    Build the official response
                </strong>

                <span>
                    Add text, images, files, links, or a QR code.
                </span>

            </div>

        </div>


        {{-- =================================================
             ADD RESPONSE COMPONENT
             ================================================= --}}

        <div class="support-response-add">

            <button
                type="button"
                id="support-add-component"
                class="support-response-add-button"
                aria-expanded="false"
                aria-controls="support-component-menu"
            >

                <i
                    class="ph-light ph-plus"
                    aria-hidden="true"
                ></i>

                <span>
                    Add Response Component
                </span>

            </button>


            {{-- COMPONENT MENU --}}

            <div
                id="support-component-menu"
                class="support-component-menu"
                hidden
            >

                {{-- TEXT --}}

                <button
                    type="button"
                    class="support-component-option"
                    data-component-type="text"
                >

                    <span
                        class="support-component-option-icon"
                        aria-hidden="true"
                    >
                        <i class="ph-light ph-chat-text"></i>
                    </span>

                    <span class="support-component-option-content">

                        <strong>
                            Text
                        </strong>

                        <small>
                            Add written information.
                        </small>

                    </span>

                </button>


                {{-- IMAGE --}}

                <button
                    type="button"
                    class="support-component-option"
                    data-component-type="image"
                >

                    <span
                        class="support-component-option-icon"
                        aria-hidden="true"
                    >
                        <i class="ph-light ph-image"></i>
                    </span>

                    <span class="support-component-option-content">

                        <strong>
                            Image
                        </strong>

                        <small>
                            Attach a supporting image.
                        </small>

                    </span>

                </button>


                {{-- FILE --}}

                <button
                    type="button"
                    class="support-component-option"
                    data-component-type="file"
                >

                    <span
                        class="support-component-option-icon"
                        aria-hidden="true"
                    >
                        <i class="ph-light ph-file"></i>
                    </span>

                    <span class="support-component-option-content">

                        <strong>
                            File
                        </strong>

                        <small>
                            Attach a document or file.
                        </small>

                    </span>

                </button>


                {{-- LINK --}}

                <button
                    type="button"
                    class="support-component-option"
                    data-component-type="link"
                >

                    <span
                        class="support-component-option-icon"
                        aria-hidden="true"
                    >
                        <i class="ph-light ph-link"></i>
                    </span>

                    <span class="support-component-option-content">

                        <strong>
                            Link
                        </strong>

                        <small>
                            Provide an external website.
                        </small>

                    </span>

                </button>


                {{-- QR CODE --}}

                <button
                    type="button"
                    class="support-component-option"
                    data-component-type="qr_code"
                >

                    <span
                        class="support-component-option-icon"
                        aria-hidden="true"
                    >
                        <i class="ph-light ph-qr-code"></i>
                    </span>

                    <span class="support-component-option-content">

                        <strong>
                            QR Code
                        </strong>

                        <small>
                            Create a QR code for a website.
                        </small>

                    </span>

                </button>

            </div>

        </div>

    </section>

</div>