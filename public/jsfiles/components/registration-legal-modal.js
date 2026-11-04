(() => {
    "use strict";

    const modal = document.getElementById("registrationLegalModal");
    if (!modal || modal.dataset.initialized === "true") return;
    modal.dataset.initialized = "true";

    const tabButtons = Array.from(modal.querySelectorAll("[data-legal-tab]"));
    const documents = Array.from(modal.querySelectorAll("[data-legal-document]"));
    const content = modal.querySelector(".legal-modal-content");
    let returnFocusTo = null;

    function selectDocument(name) {
        const selectedName = name === "privacy" ? "privacy" : "terms";
        tabButtons.forEach((button) => {
            const active = button.dataset.legalTab === selectedName;
            button.classList.toggle("is-active", active);
            button.setAttribute("aria-selected", active ? "true" : "false");
            button.tabIndex = active ? 0 : -1;
        });
        documents.forEach((documentSection) => {
            documentSection.hidden = documentSection.dataset.legalDocument !== selectedName;
        });
        if (content) content.scrollTop = 0;
    }

    function openModal(name, trigger) {
        returnFocusTo = trigger || document.activeElement;
        selectDocument(name);
        modal.hidden = false;
        document.body.classList.add("legal-modal-open");
        const closeButton = modal.querySelector("[data-legal-close]");
        if (closeButton) closeButton.focus();
    }

    function closeModal() {
        if (modal.hidden) return;
        modal.hidden = true;
        document.body.classList.remove("legal-modal-open");
        if (returnFocusTo && typeof returnFocusTo.focus === "function") returnFocusTo.focus();
    }

    document.querySelectorAll("[data-legal-open]").forEach((trigger) => {
        trigger.addEventListener("click", (event) => {
            event.preventDefault();
            openModal(trigger.dataset.legalOpen, trigger);
        });
        if (trigger.tagName === "A") {
            trigger.setAttribute("role", "button");
        }
    });

    tabButtons.forEach((button) => {
        button.addEventListener("click", () => selectDocument(button.dataset.legalTab));
    });

    modal.querySelectorAll("[data-legal-close]").forEach((button) => {
        button.addEventListener("click", closeModal);
    });

    modal.addEventListener("click", (event) => {
        if (event.target === modal) closeModal();
    });

    document.addEventListener("keydown", (event) => {
        if (modal.hidden) return;
        if (event.key === "Escape") {
            event.preventDefault();
            closeModal();
            return;
        }
        if (event.key !== "Tab") return;

        const focusable = Array.from(modal.querySelectorAll(
            'button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])'
        )).filter((element) => !element.closest("[hidden]") && element.offsetParent !== null);
        if (!focusable.length) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    // Standalone legal pages use the same content partial without the dialog.
    const standalone = document.querySelector("[data-legal-standalone]");
    if (standalone) selectDocument(standalone.dataset.legalStandalone);
})();
