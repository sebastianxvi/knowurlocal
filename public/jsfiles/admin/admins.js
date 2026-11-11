document.addEventListener("DOMContentLoaded", function () {

    // Success and error feedback is handled centrally by modal-system.js
    // from the flash values exposed by layouts/admin.blade.php.

    // ================= INVITE MODAL =================

    const modal = document.getElementById("invite-modal");
    const form = document.getElementById("inviteForm");
    const adminPage = document.querySelector(".admin-page");
    const lifecyclePasswordUrl = adminPage?.dataset.lifecyclePasswordUrl;

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
    function openInviteModal(options = {}) {

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
        if (!options.preserveValues) form.reset();
    }

    /*
     * Make the function available to the Blade onclick handler.
     */
    window.openInviteModal = openInviteModal;

    // Validation failures are presented through the shared alert modal,
    // then the invitation form is reopened with the submitted email intact.
    if (window.__ADMIN_INVITE_VALIDATION_ERROR__) {
        const validationMessage = window.__ADMIN_INVITE_VALIDATION_ERROR__;
        const previousEmail = window.__ADMIN_INVITE_OLD_EMAIL__ || "";
        window.__ADMIN_INVITE_VALIDATION_ERROR__ = null;
        window.__ADMIN_INVITE_OLD_EMAIL__ = null;

        window.showAlertModal({
            title: "Invitation could not be sent",
            text: String(validationMessage),
            icon: "ph-light ph-warning-circle",
            variant: "danger",
            confirmText: "OK",
            showCancel: false,
            onConfirm: () => {
                window.closeAlertModal();
                openInviteModal({ preserveValues: true });
                const emailInput = form?.querySelector('input[name="email"]');
                if (emailInput) {
                    emailInput.value = previousEmail;
                    emailInput.focus();
                }
            }
        });
    }


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

    // Invitation security: use the shared confirmation modal first, then
    // the dedicated administrator-password modal helper.
    if (form) {
        form.addEventListener("submit", async (event) => {
            if (form.dataset.passwordVerified === "true") return;

            event.preventDefault();
            if (form.dataset.inviteConfirming === "true") return;
            form.dataset.inviteConfirming = "true";

            const email = form.querySelector('input[name="email"]')?.value.trim() || "this email address";
            // Use the same shared confirmation modal and success treatment
            // as the existing NGA/NGO "Save changes?" confirmation.
            const confirmed = await window.showConfirmModal({
                title: "Confirm Admin Invitation",
                text: `Make sure the email address is correct. You are inviting ${email} as a System Admin.`,
                icon: "✓",
                variant: "success",
                confirmText: "Send",
                cancelText: "Cancel"
            });

            if (!confirmed) {
                form.dataset.inviteConfirming = "false";
                return;
            }

            if (typeof window.requestAdminPasswordForForm !== "function") {
                form.dataset.inviteConfirming = "false";
                window.showAlertModal({
                    title: "Security verification unavailable",
                    text: "Please refresh the page and try again.",
                    variant: "danger",
                    confirmText: "OK",
                    showCancel: false
                });
                return;
            }

            window.requestAdminPasswordForForm({
                form,
                verificationUrl: lifecyclePasswordUrl,
                actionDescription: "invite",
                subject: email,
                onCancel: () => {
                    form.dataset.inviteConfirming = "false";
                }
            });
        });
    }


    /**
     * ================= INVITE BUTTON =================
     */
    document.addEventListener("click", function (e) {

        const btn = e.target.closest(".add-agencybtn");

        if (!btn) return;

        openInviteModal();
    });


    // =========================================================
    // ACCOUNT ACTIONS — CONFIRMATION + ADMIN PASSWORD
    // =========================================================

    const adminActionSelectors = [
        ".evaluation-moderation-btn",
        ".approve-btn",
        ".promote-btn",
        ".demote-btn",
        ".deactivate-admin-btn",
        ".reactivate-admin-btn",
        ".delete-admin-btn"
    ].join(",");

    document.addEventListener("click", async (e) => {
        const btn = e.target.closest(adminActionSelectors);
        if (!btn) return;

        const actionForm = btn.closest("form");
        if (!actionForm) return;

        e.preventDefault();

        if (actionForm.dataset.confirming === "true") return;
        actionForm.dataset.confirming = "true";

        const adminName =
            actionForm.closest("tr")?.querySelector(".actor-name")?.textContent.trim() ||
            "this administrator";

        let config = null;

        if (btn.matches(".evaluation-moderation-btn")) {
            const granting = actionForm.querySelector('[name="can_moderate_evaluations"]')?.value === "1";
            config = {
                title: granting ? "Grant Evaluation Moderation?" : "Revoke Evaluation Moderation?",
                text: granting
                    ? `Allow ${adminName} to moderate agency evaluations?`
                    : `Remove ${adminName}'s permission to moderate agency evaluations?`,
                icon: "ph-light ph-shield-check",
                variant: granting ? "success" : "danger",
                confirmText: granting ? "Grant Permission" : "Revoke Permission",
                verb: granting ? "grant evaluation moderation to" : "revoke evaluation moderation from"
            };
        } else if (btn.matches(".approve-btn")) {
            config = {
                title: "Approve Admin?",
                text: `Are you sure you want to approve ${adminName}? This will activate the administrator account.`,
                icon: "✓", variant: "success", confirmText: "Approve", verb: "approve"
            };
        } else if (btn.matches(".promote-btn")) {
            config = {
                title: "Promote to Superadmin?",
                text: `Are you sure you want to promote ${adminName}? This grants full system access.`,
                icon: "↑", variant: "success", confirmText: "Promote", verb: "promote"
            };
        } else if (btn.matches(".demote-btn")) {
            config = {
                title: "Demote to Admin?",
                text: `Are you sure you want to demote ${adminName}? This removes superadmin privileges.`,
                icon: "↓", variant: "danger", confirmText: "Demote", verb: "demote"
            };
        } else if (btn.matches(".deactivate-admin-btn")) {
            config = {
                title: "Deactivate Admin?",
                text: `Are you sure you want to deactivate ${adminName}? The account will remain in the system and can be reactivated later.`,
                icon: "!", variant: "danger", confirmText: "Deactivate", verb: "deactivate"
            };
        } else if (btn.matches(".reactivate-admin-btn")) {
            config = {
                title: "Reactivate Admin?",
                text: `Are you sure you want to reactivate ${adminName}? This restores the account's active status.`,
                icon: "✓", variant: "success", confirmText: "Reactivate", verb: "reactivate"
            };
        } else if (btn.matches(".delete-admin-btn")) {
            config = {
                title: "Delete Admin Permanently?",
                text: `This permanently removes ${adminName}'s administrator account. This action cannot be undone. Are you sure?`,
                icon: "!", variant: "danger", confirmText: "Delete Permanently", verb: "permanently delete"
            };
        }

        if (!config) {
            actionForm.dataset.confirming = "false";
            return;
        }

        const csrf =
            actionForm.querySelector('input[name="_token"]')?.value ||
            document.querySelector('meta[name="csrf-token"]')?.content ||
            "";

        showAlertModal({
            title: config.title,
            text: config.text,
            icon: config.icon,
            variant: config.variant,
            confirmText: config.confirmText,
            cancelText: "Cancel",
            showCancel: true,
            onCancel: () => {
                actionForm.dataset.confirming = "false";
            },
            onConfirm: () => {
                window.closeAlertModal(true);

                window.showAlertModal({
                    title: "Administrative security verification",
                    text: `Confirm your identity before you ${config.verb} ${adminName}.`,
                    icon: "ph-light ph-shield-check",
                    variant: "info",
                    input: {
                        type: "password",
                        label: "Confirm your administrator password",
                        placeholder: "Enter your current account password",
                        required: true,
                        maxLength: 255,
                        validationMessage: "Your administrator password is required."
                    },
                    confirmText: "Verify and continue",
                    cancelText: "Cancel",
                    showCancel: true,
                    allowCancelDuringLoading: true,
                    onCancel: () => {
                        actionForm.dataset.confirming = "false";
                        actionForm.dataset.passwordCancelled = "true";
                    },
                    onConfirm: async (password) => {
                        actionForm.dataset.passwordCancelled = "false";

                        if (!lifecyclePasswordUrl) {
                            throw new Error("The administrator security-verification endpoint is unavailable. Please refresh the page.");
                        }

                        const response = await fetch(lifecyclePasswordUrl, {
                            method: "POST",
                            credentials: "same-origin",
                            headers: {
                                "Content-Type": "application/json",
                                "Accept": "application/json",
                                "X-CSRF-TOKEN": csrf
                            },
                            body: JSON.stringify({ password })
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (actionForm.dataset.passwordCancelled === "true") return;

                        if (!response.ok || payload.verified !== true) {
                            throw new Error(payload.message || "Password verification failed.");
                        }

                        let passwordField = actionForm.querySelector('input[name="current_password"]');
                        if (!passwordField) {
                            passwordField = document.createElement("input");
                            passwordField.type = "hidden";
                            passwordField.name = "current_password";
                            actionForm.appendChild(passwordField);
                        }

                        passwordField.value = password;
                        window.closeAlertModal(true);
                        actionForm.submit();
                    }
                });
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