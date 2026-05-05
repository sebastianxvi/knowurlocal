// ================= ALERT MODAL MODULE (ISOLATED) =================
(function(){

    // ================= GET MODAL ROOT =================
    const modal = document.getElementById("alert-modal");

    // 🔒 Fail fast (prevents runtime errors)
    if (!modal) {
        console.warn("Alert modal not found in DOM");

        // Safe fallback (prevents app crash)
        window.showAlertModal = () => {};
        window.closeAlertModal = () => {};
        return;
    }

    // ================= ELEMENT REFERENCES =================
    const titleEl   = document.getElementById("alert-modal-title");
    const nameEl    = document.getElementById("alert-modal-name");
    const textEl    = document.getElementById("alert-modal-text");
    const iconEl    = document.getElementById("alert-modal-icon");

    const messageBox = document.getElementById("alert-modal-message");

    // ================= OPEN MODAL =================
    function showAlertModal(config = {}) {

        // 🔥 Force close previous state (prevents stacked state bugs)
        closeAlertModal(true);

        // 🔥 Always fetch fresh buttons (because we replace them)
        let confirmBtn = document.getElementById("alert-modal-confirm");
        let cancelBtn  = document.getElementById("alert-modal-cancel");

        // ================= SAFE CONTENT =================
        titleEl.textContent = config.title || "";
        nameEl.textContent  = config.name || "";
        textEl.textContent  = config.text || "";
        iconEl.textContent  = config.icon || "";

        nameEl.style.display    = config.name ? "block" : "none";
        cancelBtn.style.display = config.showCancel ? "inline-block" : "none";

        // ================= CLEAN EVENT BINDING =================
        // 🔥 Clone nodes to remove ALL previous event listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        const newCancelBtn  = cancelBtn.cloneNode(true);

        confirmBtn.replaceWith(newConfirmBtn);
        cancelBtn.replaceWith(newCancelBtn);

        // 🔥 Re-fetch after replacement
        confirmBtn = document.getElementById("alert-modal-confirm");
        cancelBtn  = document.getElementById("alert-modal-cancel");

        // ================= RESET STATE =================
        messageBox.classList.remove("danger", "success");
        confirmBtn.classList.remove("danger-btn", "success-btn");

        // 🔥 Reset loading state (important)
        confirmBtn.dataset.loading = "false";
        confirmBtn.disabled = false;

        // ================= APPLY VARIANT =================
        if (config.variant) {
            messageBox.classList.add(config.variant);
            confirmBtn.classList.add(config.variant + "-btn");
        }

        confirmBtn.textContent = config.confirmText || "Confirm";
        // 🔥 If no onConfirm → this is NOT an action modal (e.g. success)
        if (typeof config.onConfirm !== "function") {
            confirmBtn.dataset.loading = "false";
            confirmBtn.disabled = false;
        }

        // ================= CONFIRM ACTION =================
        confirmBtn.addEventListener("click", () => {

            // 🔒 Prevent double clicks
            if (confirmBtn.dataset.loading === "true") return;

            confirmBtn.dataset.loading = "true";
            confirmBtn.disabled = true;

            // 🎨 UX feedback
            confirmBtn.textContent = "Processing...";
if (typeof config.onConfirm === "function") {
        config.onConfirm();
    }

                    });

        // ================= CANCEL =================
        cancelBtn.addEventListener("click", closeAlertModal);

        // ================= SHOW =================
        modal.classList.remove("hidden");

        // 🔥 Force reflow for animation
        void modal.offsetWidth;

        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");
    }

    // ================= CLOSE MODAL =================
    function closeAlertModal(force = false){

        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");

        if (force) {
            modal.classList.add("hidden");
            return;
        }

        // ⏱ Smooth transition
        setTimeout(() => {
            modal.classList.add("hidden");
        }, 200);
    }

    // ================= BACKDROP CLICK =================
    modal.addEventListener("click", function(e){
        if (e.target === modal) {
            closeAlertModal();
        }
    });

    // ================= GLOBAL EXPORT =================
    window.showAlertModal = showAlertModal;
    window.closeAlertModal = closeAlertModal;

})();