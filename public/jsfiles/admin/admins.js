document.addEventListener("DOMContentLoaded", function () {

    // ================= SUCCESS FEEDBACK =================
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

        // 🔥 Auto close after 1.5s (SaaS feel)
        setTimeout(() => {
            closeAlertModal();
        }, 1500);

        // 🔒 Clear to prevent repeat
        window.__FLASH_SUCCESS__ = null;
    }

    const modal = document.getElementById("invite-modal");
    const form = document.getElementById("inviteForm");

    if (!modal || !form) return;

    let currentMode = "add";

    /**
     * ================= OPEN MODAL =================
     */
    function openInviteModal(){

        currentMode = "add";

        // 🔥 HARD SHOW (same as FAQ)
        modal.classList.remove("hidden");
        modal.style.display = "flex";

        // force reflow (animation fix)
        void modal.offsetWidth;

        modal.classList.add("active");

        // reset form
        form.reset();
    }

    window.openInviteModal = openInviteModal;

    /**
     * ================= CLOSE MODAL =================
     */
    function closeInviteModal(){

        modal.classList.remove("active");

        setTimeout(() => {
            modal.style.display = "none";
            modal.classList.add("hidden");

            form.reset();
        }, 200);
    }

    window.closeInviteModal = closeInviteModal;

    /**
     * ================= BUTTON TRIGGER =================
     */
    document.addEventListener('click', function(e){

        const btn = e.target.closest('.add-agencybtn');
        if(!btn) return;

        openInviteModal();
    });

    
    /**
     * 🔥 DELETE ADMIN
     */
    document.addEventListener("click", (e) => {

        const btn = e.target.closest(".delete-admin-btn");
        if (!btn) return;

        const form = btn.closest("form");
        if (!form) return;

        e.preventDefault();

        showAlertModal({
            title: "Delete Admin?",
            text: "This action cannot be undone.",
            icon: "!",
            variant: "danger",
            confirmText: "Delete",
            showCancel: true,

            onConfirm: () => {
                form.submit();
            }
        });
    });

    /**
     * 🔼 PROMOTE
     */
    document.addEventListener("click", (e) => {

        const btn = e.target.closest(".promote-btn");
        if (!btn) return;

        const form = btn.closest("form");

        showAlertModal({
            title: "Promote to Superadmin?",
            text: "This user will gain full system access.",
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
     * 🔽 DEMOTE
     */
    document.addEventListener("click", (e) => {

        const btn = e.target.closest(".demote-btn");
        if (!btn) return;

        const form = btn.closest("form");

        showAlertModal({
            title: "Demote to Admin?",
            text: "This user will lose superadmin privileges.",
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
     * ✅ APPROVE
     */
    document.addEventListener("click", (e) => {

        const btn = e.target.closest(".approve-btn");
        if (!btn) return;

        const form = btn.closest("form");

        showAlertModal({
            title: "Approve Admin?",
            text: "This will activate the admin account.",
            icon: "✓",
            variant: "success",
            confirmText: "Approve",
            showCancel: true,

            onConfirm: () => {
                form.submit();
            }
        });
    });

    /**
     * ================= CLICK OUTSIDE =================
     */
    modal.addEventListener("click", e => {
        if (e.target === modal) closeInviteModal();
    });

    /**
     * ================= ESC KEY =================
     */
    document.addEventListener("keydown", e => {
        if (e.key === "Escape") closeInviteModal();
    });

});