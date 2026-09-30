<!-- ================= GLOBAL ALERT MODAL ================= -->
<div 
    id="alert-modal" 
    class="overlay hidden" 

    role="dialog" 
    aria-modal="true" 
    aria-hidden="true"

    aria-labelledby="alert-modal-title"
    aria-describedby="alert-modal-text"
>

    <div class="modal" role="document" tabindex="-1">

        <!-- TITLE -->
        <h3 class="title" id="alert-modal-title"></h3>
        <h2 class="name" id="alert-modal-name"></h2>

        <!-- MESSAGE -->
        <div class="message-box info" id="alert-modal-message" aria-live="polite">
            <div class="icon" id="alert-modal-icon"></div>
            <p id="alert-modal-text"></p>
        </div>

        <!-- OPTIONAL INPUT (for audited actions such as moving a report to Trash) -->
        <div class="alert-modal-input" id="alert-modal-input-wrap" hidden>
            <label for="alert-modal-input" id="alert-modal-input-label"></label>
            <textarea id="alert-modal-input" rows="3" hidden></textarea>
            <p class="alert-modal-input-error" id="alert-modal-input-error" hidden></p>
        </div>

        <!-- ACTIONS -->
        <div class="actions">

            <button 
                type="button" 
                class="btn cancel" 
                id="alert-modal-cancel"
            >
                Cancel
            </button>

            <button 
                type="button" 
                class="btn confirm" 
                id="alert-modal-confirm"
            >
                Confirm
            </button>

        </div>

    </div>
</div>