// ================= ALERT MODAL MODULE (ISOLATED) =================
(function(){

    // ================= GET MODAL ROOT =================
    const modal = document.getElementById("alert-modal");

    /*
     * Fail fast if the modal does not exist.
     *
     * This prevents pages that do not contain the alert modal
     * from throwing JavaScript errors.
     */
    if (!modal) {
        console.warn("Alert modal not found in DOM");

        // Safe fallback functions.
        window.showAlertModal = () => {};
        window.closeAlertModal = () => {};

        return;
    }


    // ================= ELEMENT REFERENCES =================

    const titleEl =
        document.getElementById("alert-modal-title");

    const nameEl =
        document.getElementById("alert-modal-name");

    const textEl =
        document.getElementById("alert-modal-text");

    const iconEl =
        document.getElementById("alert-modal-icon");

    const messageBox =
        document.getElementById("alert-modal-message");


    // ================= OPEN MODAL =================

    function showAlertModal(config = {}) {

        /*
         * Force-close any previous modal state.
         *
         * This prevents stale animations, button states,
         * or event listeners from affecting the new modal.
         */
        closeAlertModal(true);


        /*
         * Fetch the current buttons.
         *
         * The buttons are replaced below so that previous
         * click listeners are completely removed.
         */
        let confirmBtn =
            document.getElementById("alert-modal-confirm");

        let cancelBtn =
            document.getElementById("alert-modal-cancel");


        // ================= SAFE CONTENT =================

        titleEl.textContent =
            config.title || "";

        nameEl.textContent =
            config.name || "";

        textEl.textContent =
            config.text || "";

        iconEl.textContent =
            config.icon || "";


        /*
         * Only display the optional name element when a name
         * was actually supplied.
         */
        nameEl.style.display =
            config.name ? "block" : "none";


        /*
         * Cancel is only visible when explicitly requested.
         */
        cancelBtn.style.display =
            config.showCancel ? "inline-block" : "none";


        // ================= CLEAN EVENT BINDING =================

        /*
         * Clone the buttons.
         *
         * cloneNode(true) creates completely fresh elements
         * without the event listeners attached to the old ones.
         *
         * This prevents multiple callbacks from accumulating
         * when the modal is opened repeatedly.
         */
        const newConfirmBtn =
            confirmBtn.cloneNode(true);

        const newCancelBtn =
            cancelBtn.cloneNode(true);


        confirmBtn.replaceWith(newConfirmBtn);
        cancelBtn.replaceWith(newCancelBtn);


        /*
         * Fetch the newly inserted elements.
         */
        confirmBtn =
            document.getElementById("alert-modal-confirm");

        cancelBtn =
            document.getElementById("alert-modal-cancel");


        // ================= RESET STATE =================

        /*
         * Remove any previous visual variants.
         */
        messageBox.classList.remove(
            "danger",
            "success"
        );

        confirmBtn.classList.remove(
            "danger-btn",
            "success-btn"
        );


        /*
         * Every newly opened modal starts in a clean state.
         */
        confirmBtn.dataset.loading = "false";
        confirmBtn.disabled = false;


        // ================= APPLY VARIANT =================

        if (config.variant) {

            messageBox.classList.add(
                config.variant
            );

            confirmBtn.classList.add(
                config.variant + "-btn"
            );
        }


        // ================= BUTTON TEXT =================

        confirmBtn.textContent =
            config.confirmText || "Confirm";


        // ================= CONFIRM ACTION =================

        confirmBtn.addEventListener("click", () => {

            /*
             * Informational modals may not have an action.
             *
             * Example:
             *
             * "Saved successfully" → "OK"
             *
             * In this case, simply close the modal.
             */
            if (typeof config.onConfirm !== "function") {

                closeAlertModal();

                return;
            }


            /*
             * Prevent double submission.
             *
             * This protects destructive operations such as:
             *
             * - Trash
             * - Restore
             * - Permanent Delete
             * - Approve
             */
            if (
                confirmBtn.dataset.loading === "true"
            ) {
                return;
            }


            /*
             * Determine whether this particular modal
             * actually represents a processing operation.
             *
             * By default:
             *
             * loading = true
             *
             * This preserves the existing behavior.
             *
             * A caller can explicitly use:
             *
             * loading: false
             *
             * for simple informational callbacks.
             */
            const shouldShowLoading =
                config.loading !== false;


            // ================= PROCESSING STATE =================

            if (shouldShowLoading) {

                /*
                 * Mark the button as processing.
                 */
                confirmBtn.dataset.loading = "true";


                /*
                 * Disable the button immediately.
                 *
                 * This prevents duplicate requests.
                 */
                confirmBtn.disabled = true;


                /*
                 * Give the user visual feedback.
                 */
                confirmBtn.textContent =
                    "Processing...";
            }


            // ================= EXECUTE ACTION =================

            /*
             * Execute the callback supplied by the caller.
             *
             * The caller decides what actually happens.
             */
            config.onConfirm();

        });


        // ================= CANCEL =================

        cancelBtn.addEventListener(
            "click",
            closeAlertModal
        );


        // ================= SHOW =================

        modal.classList.remove("hidden");


        /*
         * Force a browser reflow.
         *
         * This ensures the CSS transition starts correctly
         * even when the modal was previously hidden.
         */
        void modal.offsetWidth;


        modal.classList.add("show");

        modal.setAttribute(
            "aria-hidden",
            "false"
        );
    }


    // ================= CLOSE MODAL =================

    function closeAlertModal(force = false){

        /*
         * Remove the visible state.
         */
        modal.classList.remove("show");


        /*
         * Update accessibility state.
         */
        modal.setAttribute(
            "aria-hidden",
            "true"
        );


        /*
         * When force-closing, hide immediately.
         *
         * This is used before opening another modal to ensure
         * no previous animation/state remains.
         */
        if (force) {

            modal.classList.add("hidden");

            return;
        }


        /*
         * Otherwise allow the closing animation to finish
         * before removing the modal from view.
         */
        setTimeout(() => {

            modal.classList.add("hidden");

        }, 200);
    }


    // ================= BACKDROP CLICK =================

    modal.addEventListener("click", function(e){

        /*
         * Only close when the actual backdrop was clicked.
         *
         * Clicking inside the modal does not close it.
         */
        if (e.target === modal) {

            closeAlertModal();
        }
    });


    // ================= GLOBAL EXPORT =================

    window.showAlertModal =
        showAlertModal;

    window.closeAlertModal =
        closeAlertModal;

})();