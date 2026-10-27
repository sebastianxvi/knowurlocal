<div class="ticket-create-modal" data-ticket-modal hidden aria-hidden="true">
    <div class="ticket-create-backdrop" data-ticket-modal-close></div>
    <section class="ticket-create-dialog" role="dialog" aria-modal="true" aria-labelledby="ticket-create-title" aria-describedby="ticket-create-description" tabindex="-1">
        <button type="button" class="ticket-create-close" data-ticket-modal-close aria-label="Close ticket form">
            <i class="ph-light ph-x" aria-hidden="true"></i>
        </button>
        <div class="ticket-create-icon" aria-hidden="true"><i class="ph-light ph-chat-circle-dots"></i></div>
        <p class="ticket-create-eyebrow">CONTACT AN AGENCY</p>
        <h2 id="ticket-create-title">Submit a support ticket</h2>
        <p id="ticket-create-description" class="ticket-create-description">Choose the office that can help. Your message will be saved as a new inquiry, and you can follow its response here.</p>
        <form data-ticket-create-form>
            <label for="ticket-agency-search">Agency <span aria-hidden="true">*</span></label>
            <div class="ticket-agency-picker" data-agency-picker>
                <div class="ticket-agency-input-wrap">
                    <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                    <input
                        id="ticket-agency-search"
                        type="text"
                        autocomplete="off"
                        placeholder="Search by agency name or abbreviation..."
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="ticket-agency-options"
                        aria-describedby="ticket-agency-help"
                    >
                    <button type="button" class="ticket-agency-clear" data-agency-clear aria-label="Clear agency selection" hidden>
                        <i class="ph-light ph-x" aria-hidden="true"></i>
                    </button>
                </div>
                <p id="ticket-agency-help" class="ticket-agency-help">Type an office name or abbreviation, such as DOLE, BFP, or DepEd.</p>
                <div id="ticket-agency-options" class="ticket-agency-options" role="listbox" aria-label="Agencies" hidden>
                    @foreach($agencies as $agency)
                        <button
                            type="button"
                            class="ticket-agency-option"
                            role="option"
                            data-agency-option
                            data-agency-id="{{ $agency->id }}"
                            data-agency-label="{{ $agency->agency_name }}@if($agency->agency_abbreviation) ({{ $agency->agency_abbreviation }})@endif"
                            data-agency-search="{{ \Illuminate\Support\Str::lower($agency->agency_name . ' ' . ($agency->agency_abbreviation ?? '')) }}"
                            aria-selected="false"
                        >
                            <span class="ticket-agency-option-name">{{ $agency->agency_name }}</span>
                            @if($agency->agency_abbreviation)
                                <span class="ticket-agency-option-abbreviation">{{ $agency->agency_abbreviation }}</span>
                            @endif
                        </button>
                    @endforeach
                    <p class="ticket-agency-no-results" data-agency-no-results hidden>No agencies match your search.</p>
                </div>
                <select id="ticket-agency" name="agency_id" required tabindex="-1" aria-hidden="true">
                    <option value="">Choose an agency</option>
                    @foreach($agencies as $agency)
                        <option value="{{ $agency->id }}">{{ $agency->agency_name }}@if($agency->agency_abbreviation) ({{ $agency->agency_abbreviation }})@endif</option>
                    @endforeach
                </select>
            </div>
            <label for="ticket-question">How can they help you? <span aria-hidden="true">*</span></label>
            <textarea id="ticket-question" name="question" rows="5" maxlength="500" required placeholder="Describe your concern in a few sentences..."></textarea>
            <div class="ticket-create-meta"><span>Keep your message clear and specific.</span><span data-ticket-character-count>0 / 500</span></div>
            <p class="ticket-create-error" data-ticket-error role="alert" hidden></p>
            <div class="ticket-create-actions">
                <button type="button" class="ticket-create-cancel" data-ticket-modal-close>Cancel</button>
                <button type="submit" class="ticket-create-submit" data-ticket-submit><i class="ph-light ph-paper-plane-tilt" aria-hidden="true"></i><span>Submit ticket</span></button>
            </div>
        </form>
    </section>
</div>
