const DRAFT_KEY = "knowurlocal:new-ticket-draft";

export function initializeCreateTicket() {
    const modal = document.querySelector("[data-ticket-modal]");
    const form = modal?.querySelector("[data-ticket-create-form]");
    const dialog = modal?.querySelector(".ticket-create-dialog");
    const agencySelect = modal?.querySelector("#ticket-agency");
    const agencySearch = modal?.querySelector("#ticket-agency-search");
    const agencyPicker = modal?.querySelector("[data-agency-picker]");
    const agencyOptions = modal?.querySelector("#ticket-agency-options");
    const agencyOptionButtons = [...(modal?.querySelectorAll("[data-agency-option]") || [])];
    const agencyClear = modal?.querySelector("[data-agency-clear]");
    const agencyNoResults = modal?.querySelector("[data-agency-no-results]");
    const questionInput = modal?.querySelector("#ticket-question");
    const errorBox = modal?.querySelector("[data-ticket-error]");
    const submitButton = modal?.querySelector("[data-ticket-submit]");
    const count = modal?.querySelector("[data-ticket-character-count]");
    if (!modal || !form || !dialog || !agencySelect || !agencySearch || !agencyPicker || !agencyOptions || !questionInput || !submitButton) return;

    let lastFocusedElement = null;
    const readDraft = () => {
        try {
            const draft = JSON.parse(sessionStorage.getItem(DRAFT_KEY) || "null");
            if (!draft || typeof draft !== "object") return null;
            // Avoid restoring stale drafts left behind in a tab for days.
            if (!Number.isFinite(draft.createdAt) || Date.now() - draft.createdAt > 2 * 60 * 60 * 1000) {
                sessionStorage.removeItem(DRAFT_KEY);
                return null;
            }
            return draft;
        } catch (_) { return null; }
    };

    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.hidden = false;
    };
    const clearError = () => { errorBox.textContent = ""; errorBox.hidden = true; };

    const open = (trigger = document.activeElement) => {
        lastFocusedElement = trigger;
        modal.hidden = false;
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("ticket-modal-open");
        window.requestAnimationFrame(() => {
            (agencySelect.value ? questionInput : agencySearch).focus();
        });
    };
    // The Chats sidebar plus button opens the same agency/question form
    // used by the chatbot's Send Ticket flow.
    document.querySelectorAll("[data-ticket-modal-open]").forEach((trigger) => {
        trigger.addEventListener("click", () => open(trigger));
    });

    let activeAgencyIndex = -1;
    const visibleAgencyOptions = () => agencyOptionButtons.filter((option) => !option.hidden);
    const closeAgencyOptions = () => {
        agencyOptions.hidden = true;
        agencySearch.setAttribute("aria-expanded", "false");
        agencySearch.removeAttribute("aria-activedescendant");
        activeAgencyIndex = -1;
        agencyOptionButtons.forEach((option) => option.classList.remove("is-active"));
    };
    const openAgencyOptions = () => {
        agencyOptions.hidden = false;
        agencySearch.setAttribute("aria-expanded", "true");
        filterAgencyOptions(agencySearch.value);
    };
    const syncAgencySelection = () => {
        const selected = agencySelect.selectedOptions[0];
        const hasSelection = Boolean(agencySelect.value);
        agencySearch.value = hasSelection ? selected.textContent.trim() : "";
        agencyClear.hidden = !hasSelection && !agencySearch.value;
        agencyOptionButtons.forEach((option) => {
            const isSelected = option.dataset.agencyId === agencySelect.value;
            option.setAttribute("aria-selected", String(isSelected));
        });
    };
    const filterAgencyOptions = (query) => {
        const normalized = String(query || "").trim().toLocaleLowerCase();
        let matches = 0;
        agencyOptionButtons.forEach((option) => {
            const selectedLabel = option.dataset.agencyLabel || option.textContent.trim();
            // Searching the combined name + abbreviation supports either term,
            // including short codes such as DOLE, BFP, BIR, and DepEd.
            const searchable = (option.dataset.agencySearch || selectedLabel).toLocaleLowerCase();
            const match = !normalized || searchable.includes(normalized) || selectedLabel.toLocaleLowerCase().includes(normalized);
            option.hidden = !match;
            if (match) matches += 1;
            option.classList.remove("is-active");
        });
        agencyNoResults.hidden = matches > 0;
        activeAgencyIndex = -1;
    };
    const selectAgencyOption = (option) => {
        if (!option) return;
        agencySelect.value = option.dataset.agencyId || "";
        syncAgencySelection();
        closeAgencyOptions();
        clearError();
        agencySearch.focus();
    };

    agencySearch.addEventListener("focus", openAgencyOptions);
    agencySearch.addEventListener("input", () => {
        // Typing means the user is choosing again; don't submit a stale hidden ID.
        agencySelect.value = "";
        agencyClear.hidden = !agencySearch.value;
        openAgencyOptions();
        filterAgencyOptions(agencySearch.value);
        clearError();
    });
    agencySearch.addEventListener("keydown", (event) => {
        const visible = visibleAgencyOptions();
        if (event.key === "ArrowDown") {
            event.preventDefault();
            if (agencyOptions.hidden) openAgencyOptions();
            if (!visible.length) return;
            activeAgencyIndex = Math.min(activeAgencyIndex + 1, visible.length - 1);
            visible.forEach((option, index) => option.classList.toggle("is-active", index === activeAgencyIndex));
            visible[activeAgencyIndex].scrollIntoView({ block: "nearest" });
            agencySearch.setAttribute("aria-activedescendant", visible[activeAgencyIndex].id);
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            if (!visible.length) return;
            activeAgencyIndex = Math.max(activeAgencyIndex - 1, 0);
            visible.forEach((option, index) => option.classList.toggle("is-active", index === activeAgencyIndex));
            visible[activeAgencyIndex].scrollIntoView({ block: "nearest" });
            agencySearch.setAttribute("aria-activedescendant", visible[activeAgencyIndex].id);
        } else if (event.key === "Enter" && !agencyOptions.hidden) {
            const active = visible[activeAgencyIndex] || (visible.length === 1 ? visible[0] : null);
            if (active) { event.preventDefault(); selectAgencyOption(active); }
        } else if (event.key === "Escape" && !agencyOptions.hidden) {
            event.preventDefault();
            closeAgencyOptions();
        }
    });
    agencyOptionButtons.forEach((option, index) => {
        option.id = `ticket-agency-option-${index}`;
        option.addEventListener("click", () => selectAgencyOption(option));
    });
    agencyClear?.addEventListener("click", () => {
        agencySelect.value = "";
        agencySearch.value = "";
        agencyClear.hidden = true;
        filterAgencyOptions("");
        openAgencyOptions();
        agencySearch.focus();
    });
    document.addEventListener("click", (event) => {
        if (!agencyPicker.contains(event.target)) closeAgencyOptions();
    });

    const close = () => {
        closeAgencyOptions();
        modal.hidden = true;
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("ticket-modal-open");
        clearError();
        lastFocusedElement?.focus?.();
    };

    const draft = readDraft();
    if (draft) {
        questionInput.value = String(draft.question || "").slice(0, 500);
        if (draft.agencyId && [...agencySelect.options].some((option) => option.value === String(draft.agencyId))) {
            agencySelect.value = String(draft.agencyId);
        }
        sessionStorage.removeItem(DRAFT_KEY);
    }
    syncAgencySelection();
    const updateCount = () => { count.textContent = `${questionInput.value.length} / 500`; };
    questionInput.addEventListener("input", updateCount);
    updateCount();

    modal.addEventListener("click", (event) => {
        if (event.target.closest("[data-ticket-modal-close]")) close();
    });
    document.addEventListener("keydown", (event) => {
        if (modal.hidden) return;
        if (event.key === "Escape") { event.preventDefault(); close(); return; }
        if (event.key === "Tab") {
            const focusable = [...dialog.querySelectorAll('button:not(:disabled):not([hidden]), input:not(:disabled):not([type="hidden"]), textarea:not(:disabled)')].filter((element) => element.offsetParent !== null);
            if (!focusable.length) return;
            const first = focusable[0]; const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get("create_ticket") === "1") {
        params.delete("create_ticket");
        const cleanUrl = `${window.location.pathname}${params.toString() ? `?${params}` : ""}${window.location.hash}`;
        window.history.replaceState({}, "", cleanUrl);
        open();
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearError();
        const question = questionInput.value.trim();
        const agencyId = agencySelect.value;
        if (!agencyId) { showError("Please choose the agency you want to contact."); agencySearch.focus(); openAgencyOptions(); return; }
        if (!question) { showError("Please describe your concern before submitting."); questionInput.focus(); return; }
        if (question.length > 500) { showError("Your message must be 500 characters or fewer."); return; }

        submitButton.disabled = true;
        submitButton.setAttribute("aria-busy", "true");
        submitButton.querySelector("span").textContent = "Submitting…";
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch("/chat/support", {
                method: "POST",
                credentials: "same-origin",
                headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrfToken || "" },
                body: JSON.stringify({ question, agency_id: Number(agencyId) })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success !== true) {
                if (response.status === 422 && data.errors) {
                    const firstError = Object.values(data.errors).flat()[0];
                    throw new Error(firstError || data.message || "Please review your ticket details.");
                }
                throw new Error(data.message || (response.status === 429 ? "Too many requests. Please wait a moment and try again." : "We couldn't submit your ticket. Please try again."));
            }
            sessionStorage.removeItem(DRAFT_KEY);
            const next = new URL(window.location.href);
            next.searchParams.set("ticket_created", String(data.support_request_id || ""));
            window.location.assign(next.toString());
        } catch (error) {
            showError(error.message || "We couldn't submit your ticket. Please try again.");
        } finally {
            submitButton.disabled = false;
            submitButton.removeAttribute("aria-busy");
            submitButton.querySelector("span").textContent = "Submit ticket";
        }
    });

    const createdTicketId = new URLSearchParams(window.location.search).get("ticket_created");
    if (createdTicketId && /^\d+$/.test(createdTicketId)) {
        // The Messenger inbox displays every status together; the selected
        // agency/ticket is restored by the inbox initializer from this URL.
        const card = document.querySelector(`.inquiry-card[data-id="${createdTicketId}"]`);
        if (card) {
            card.scrollIntoView({ behavior: "smooth", block: "center" });
            card.querySelector(".inquiry-toggle")?.click();
        }
        const params = new URLSearchParams(window.location.search);
        params.delete("ticket_created");
        const cleanUrl = `${window.location.pathname}${params.toString() ? `?${params}` : ""}${window.location.hash}`;
        window.history.replaceState({}, "", cleanUrl);
    }
}
