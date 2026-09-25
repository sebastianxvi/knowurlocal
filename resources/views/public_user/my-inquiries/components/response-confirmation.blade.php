<section
    class="response-confirmation"
    aria-labelledby="response-confirmation-title-{{ $req->id }}"
>
    <div class="response-confirmation-header">
        <div class="response-confirmation-icon" aria-hidden="true">
            <i class="ph-light ph-seal-question"></i>
        </div>

        <div>
            <h3 id="response-confirmation-title-{{ $req->id }}">
                Did this response resolve your concern?
            </h3>

            <p>
                Let the office know whether you need further assistance.
            </p>
        </div>
    </div>

    <div class="confirmation-actions">
        <button
            type="button"
            class="confirmation-btn confirmation-btn-primary"
            data-confirm-response
        >
            <i
                class="ph-light ph-check"
                aria-hidden="true"
            ></i>

            Yes, this resolved my concern
        </button>

        <button
            type="button"
            class="confirmation-btn confirmation-btn-secondary"
            data-follow-up-response
        >
            <i
                class="ph-light ph-arrow-counter-clockwise"
                aria-hidden="true"
            ></i>

            No, I still need help
        </button>
    </div>

    <form
        class="follow-up-form"
        hidden
    >
        <label
            for="follow-up-reason-{{ $req->id }}"
        >
            What still needs clarification?
        </label>

        <textarea
            id="follow-up-reason-{{ $req->id }}"
            name="reason"
            rows="4"
            maxlength="2000"
            placeholder="Tell the office what information is still missing or unclear."
        ></textarea>

        <div class="follow-up-form-actions">
            <button
                type="submit"
                class="confirmation-btn confirmation-btn-primary"
            >
                <i
                    class="ph-light ph-paper-plane-tilt"
                    aria-hidden="true"
                ></i>

                Request follow-up
            </button>
        </div>
    </form>
</section>