document.addEventListener("DOMContentLoaded", function () {

    // ================= SUCCESS FEEDBACK =================

    /*
     * Display the global success message created by the Laravel
     * controller after a successful admin-management operation.
     */
    if (window.__FLASH_SUCCESS__) {

        showAlertModal({
            title: "Success",
            text: window.__FLASH_SUCCESS__,
            icon: "✓",
            variant: "success",
            confirmText: "OK",
            showCancel: false,

            onConfirm: () => {
                closeAlertModal();
            }
        });

        /*
         * Automatically close the success notification after
         * 1.5 seconds to keep the interface responsive.
         */
        setTimeout(() => {
            closeAlertModal();
        }, 1500);

        /*
         * Clear the global value so the same notification cannot
         * accidentally be displayed again.
         */
        window.__FLASH_SUCCESS__ = null;
    }


    // ================= INVITE MODAL =================

    const modal = document.getElementById("invite-modal");
    const form = document.getElementById("inviteForm");

    /*
     * The invitation modal is independent from the account
     * action confirmations below.
     *
     * If the invitation elements do not exist, the remaining
     * account-management event listeners should still work.
     */
    let currentMode = "add";


    /**
     * ================= OPEN INVITE MODAL =================
     */
    function openInviteModal() {

        currentMode = "add";

        /*
         * Remove the hidden state and explicitly display the modal.
         */
        modal.classList.remove("hidden");
        modal.style.display = "flex";

        /*
         * Force the browser to process the initial state before
         * adding the active class. This allows the CSS transition
         * to animate correctly.
         */
        void modal.offsetWidth;

        modal.classList.add("active");

        /*
         * Start with a clean invitation form.
         */
        form.reset();
    }

    /*
     * Make the function available to the Blade onclick handler.
     */
    window.openInviteModal = openInviteModal;


    /**
     * ================= CLOSE INVITE MODAL =================
     */
    function closeInviteModal() {

        modal.classList.remove("active");

        /*
         * Wait for the closing transition before completely
         * hiding the modal.
         */
        setTimeout(() => {

            modal.style.display = "none";
            modal.classList.add("hidden");

            form.reset();

        }, 200);
    }

    /*
     * Make the function available to the Blade.
     */
    window.closeInviteModal = closeInviteModal;


    /**
     * ================= INVITE BUTTON =================
     */
    document.addEventListener("click", function (e) {

        const btn = e.target.closest(".add-agencybtn");

        if (!btn) return;

        openInviteModal();
    });


    // =========================================================
    // ACCOUNT ACTION CONFIRMATIONS
    // =========================================================


    /**
     * ================= DELETE ADMIN =================
     *
     * Permanent deletion is only available from the
     * deactivated-account view.
     */
    document.addEventListener("click", (e) => {

        const btn = e.target.closest(".delete-admin-btn");

        if (!btn) return;

        const form = btn.closest("form");

        if (!form) return;

        /*
         * Prevent the browser from submitting the DELETE request
         * before the administrator confirms the operation.
         */
        e.preventDefault();

        showAlertModal({

            title: "Delete Admin Permanently?",

            text:
                "This permanently removes the admin account. " +
                "This action cannot be undone.",

            icon: "!",

            variant: "danger",

            confirmText: "Delete Permanently",

            showCancel: true,

            onConfirm: () => {

                /*
                 * Submit the original Laravel form so the existing
                 * CSRF token and DELETE method spoofing are retained.
                 */
                form.submit();
            }
        });
    });


    /**
     * ================= DEACTIVATE ADMIN =================
     */
    document.addEventListener("click", (e) => {

        const btn = e.target.closest(".deactivate-admin-btn");

        if (!btn) return;

        const form = btn.closest("form");

        if (!form) return;

        e.preventDefault();

        showAlertModal({

            title: "Deactivate Admin?",

            text:
                "This account will lose access to the administrative " +
                "system. The account can be reactivated later.",

            icon: "−",

            variant: "danger",

            confirmText: "Deactivate",

            showCancel: true,

            onConfirm: () => {

                /*
                 * Submit the existing form rather than manually
                 * constructing a request in JavaScript.
                 */
                form.submit();
            }
        });
    });


    /**
     * ================= REACTIVATE ADMIN =================
     */
    document.addEventListener("click", (e) => {

        const btn = e.target.closest(".reactivate-admin-btn");

        if (!btn) return;

        const form = btn.closest("form");

        if (!form) return;

        e.preventDefault();

        showAlertModal({

            title: "Reactivate Admin?",

            text:
                "This will restore the admin account's access " +
                "to the administrative system.",

            icon: "✓",

            variant: "success",

            confirmText: "Reactivate",

            showCancel: true,

            onConfirm: () => {

                /*
                 * Submit the original form so Laravel's CSRF
                 * protection remains in place.
                 */
                form.submit();
            }
        });
    });


    /**
     * ================= PROMOTE =================
     */
    document.addEventListener("click", (e) => {

        const btn = e.target.closest(".promote-btn");

        if (!btn) return;

        const form = btn.closest("form");

        if (!form) return;

        e.preventDefault();

        showAlertModal({

            title: "Promote to Superadmin?",

            text:
                "This user will gain full system access.",

            icon: "↑",

            variant: "success",

            confirmText: "Promote",

            showCancel: true,

            onConfirm: () => {
                form.submit();
            }
        });
    });


    /**
     * ================= DEMOTE =================
     */
    document.addEventListener("click", (e) => {

        const btn = e.target.closest(".demote-btn");

        if (!btn) return;

        const form = btn.closest("form");

        if (!form) return;

        e.preventDefault();

        showAlertModal({

            title: "Demote to Admin?",

            text:
                "This user will lose superadmin privileges.",

            icon: "↓",

            variant: "danger",

            confirmText: "Demote",

            showCancel: true,

            onConfirm: () => {
                form.submit();
            }
        });
    });


    /**
     * ================= APPROVE =================
     */
    document.addEventListener("click", (e) => {

        const btn = e.target.closest(".approve-btn");

        if (!btn) return;

        const form = btn.closest("form");

        if (!form) return;

        e.preventDefault();

        showAlertModal({

            title: "Approve Admin?",

            text:
                "This will activate the admin account.",

            icon: "✓",

            variant: "success",

            confirmText: "Approve",

            showCancel: true,

            onConfirm: () => {
                form.submit();
            }
        });
    });


    // =========================================================
    // INVITE MODAL INTERACTION
    // =========================================================

    /*
     * These listeners only run when the invitation modal exists.
     */
    if (modal) {

        /*
         * Close the invitation modal when the user clicks
         * the backdrop instead of the modal content.
         */
        modal.addEventListener("click", e => {

            if (e.target === modal) {
                closeInviteModal();
            }
        });
    }


    /*
     * Escape closes the invitation modal.
     *
     * We intentionally do not close confirmation dialogs here
     * because their lifecycle is controlled by modal-system.js.
     */
    document.addEventListener("keydown", e => {

        if (e.key === "Escape" && modal) {
            closeInviteModal();
        }
    });

});