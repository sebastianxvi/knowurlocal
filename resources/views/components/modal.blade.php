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

    <div class="modal" role="document">

        <!-- TITLE -->
        <h3 class="title" id="alert-modal-title"></h3>
        <h2 class="name" id="alert-modal-name"></h2>

        <!-- MESSAGE -->
        <div class="message-box" id="alert-modal-message">
            <div class="icon" id="alert-modal-icon"></div>
            <p id="alert-modal-text"></p>
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