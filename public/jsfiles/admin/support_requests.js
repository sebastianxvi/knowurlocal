document.addEventListener("DOMContentLoaded", () => {

    let currentRequestId = null;

    const modal = document.getElementById("support-modal-back");
    const form = document.getElementById("reply-form");
    const methodInput = document.getElementById("form-method");
    const saveBtn = document.querySelector(".btn-save");

    // ================= SUCCESS ALERT =================
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

        setTimeout(() => {
            closeAlertModal();
        }, 1500);

        window.__FLASH_SUCCESS__ = null;
    }

    // ================= GLOBAL CLICK HANDLER =================
    document.addEventListener("click", (e) => {

        // ================= FAQ BUTTON (TOP PRIORITY) =================
        const faqBtn = e.target.closest(".faq-btn");
        if (faqBtn) {

            e.preventDefault(); 
            // 🔒 Prevent default submission (avoids conflicts)

            const faqForm = faqBtn.closest("form");

            if (faqForm) {

                // 🔥 UX + SECURITY: prevent multiple clicks
                faqBtn.disabled = true;
                faqBtn.innerText = "Processing...";

                faqForm.submit(); 
                // ✅ Force submit (bypasses JS interference)
            }

            return; 
            // 🚨 Stop execution — prevents conflicts with other handlers
        }

        // ================= VIEW MODE =================
        const viewBtn = e.target.closest(".view-btn");
        if (viewBtn) {

            currentRequestId = viewBtn.dataset.id;

            document.getElementById("sr-id").value = currentRequestId;
            document.getElementById("sr-user").value = viewBtn.dataset.user;
            document.getElementById("sr-agency").value = viewBtn.dataset.agency;
            document.getElementById("sr-question").value = viewBtn.dataset.question;
            document.getElementById("sr-reply").value = viewBtn.dataset.answer || "";

            // 🔥 SMART MODE SWITCH
            if (viewBtn.dataset.answer) {
                form.action = `${form.dataset.updateUrl}/${currentRequestId}`;
                methodInput.value = "PUT";
                saveBtn.innerText = "Update Answer";
            } else {
                form.action = form.dataset.replyUrl;
                methodInput.value = "POST";
                saveBtn.innerText = "Mark as Answered";
            }

            modal.classList.add("active");
            return;
        }

        // ================= EDIT MODE =================
        const editBtn = e.target.closest(".edit-btn");
        if (editBtn) {

            const id = editBtn.dataset.id;
            const answer = editBtn.dataset.answer || "";

            currentRequestId = id;

            document.getElementById("sr-id").value = id;
            document.getElementById("sr-reply").value = answer;

            // 🔒 Use consistent route structure (avoid hardcoded mismatch issues)
            form.action = `${form.dataset.updateUrl}/${id}`;
            methodInput.value = "PUT";
            saveBtn.innerText = "Update Answer";

            modal.classList.add("active");
            return;
        }

    });

    // ================= CLOSE MODAL =================
    function closeSupportModal(){
        modal.classList.remove("active");
    }

    window.closeSupportModal = closeSupportModal;

});