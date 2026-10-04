/**
 * KNOWURLOCAL
 * My Inquiries — Filters & Search
 *
 * Keeps status filtering and client-side search fast and predictable.
 * The page already loads only the authenticated citizen's inquiries,
 * so filtering never exposes another user's records.
 */

function initializeFilters() {
    "use strict";

    const filterButtons = Array.from(
        document.querySelectorAll(
            ".inquiries-filter-tab[data-filter], [data-filter].filter-btn"
        )
    );

    const filterPicker = document.querySelector("[data-inquiry-filter-picker]");
    const filterTrigger = document.querySelector("[data-inquiry-filter-trigger]");
    const filterDrawer = document.querySelector("[data-inquiry-filter-drawer]");
    const filterDrawerClose = document.querySelector("[data-inquiry-filter-close]");
    const filterLabel = document.querySelector("[data-inquiry-filter-label]");
    const filterTriggerCount = document.querySelector("[data-inquiry-filter-trigger-count]");
    const filterTriggerUnread = document.querySelector(".inquiries-filter-trigger-unread");

    const filterLabels = {
        needs_attention: "Needs Attention",
        pending: "Pending",
        follow_up: "Follow-up",
        answered: "Answered",
        trashed: "Trashed",
    };

    const closeFilterDrawer = () => {
        if (!filterDrawer || !filterTrigger) return;
        filterDrawer.hidden = true;
        filterTrigger.setAttribute("aria-expanded", "false");
        filterPicker?.classList.remove("is-open");
    };

    const openFilterDrawer = () => {
        if (!filterDrawer || !filterTrigger) return;
        filterDrawer.hidden = false;
        filterTrigger.setAttribute("aria-expanded", "true");
        filterPicker?.classList.add("is-open");
    };

    filterTrigger?.addEventListener("click", () => {
        if (filterDrawer?.hidden) openFilterDrawer();
        else closeFilterDrawer();
    });

    filterDrawerClose?.addEventListener("click", closeFilterDrawer);

    const updateFilterTrigger = (filter, cards = getInquiryCards()) => {
        if (!filterLabel) return;
        filterLabel.textContent = filterLabels[filter] || "Needs Attention";

        const count = cards.filter((card) => getFilterForStatus(card.dataset.status) === filter).length;
        if (filterTriggerCount) filterTriggerCount.textContent = String(count);

        const sourceBadge = document.querySelector(
            `[data-filter-unread-count][data-filter="${filter}"]:not(.inquiries-filter-trigger-unread)`
        );
        if (filterTriggerUnread) {
            const unread = Number(sourceBadge?.textContent || 0);
            filterTriggerUnread.textContent = unread > 99 ? "99+" : String(unread);
            filterTriggerUnread.hidden = !sourceBadge || sourceBadge.hidden;
        }
    };

    const searchInput = document.querySelector(
        "[data-inquiry-search]"
    );

    const clearSearchButton = document.querySelector(
        "[data-clear-inquiry-search]"
    );

    const setTrashedUnreadCount = (count) => {
        const safeCount = Number.isFinite(Number(count))
            ? Math.max(0, Math.floor(Number(count)))
            : 0;

        document.querySelectorAll(
            '[data-filter-unread-count][data-filter="trashed"]'
        ).forEach((badge) => {
            badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
            badge.hidden = safeCount <= 0;
            badge.setAttribute(
                'aria-label',
                `${safeCount} new trashed ${safeCount === 1 ? 'inquiry' : 'inquiries'}`
            );
        });
    };

    window.setTrashedInquiryUnreadCount = setTrashedUnreadCount;

    const setNeedsAttentionUnreadCount = (count) => {
        const safeCount = Number.isFinite(Number(count))
            ? Math.max(0, Math.floor(Number(count)))
            : 0;

        document.querySelectorAll(
            '[data-filter-unread-count][data-filter="needs_attention"]'
        ).forEach((badge) => {
            badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
            badge.hidden = safeCount <= 0;
            badge.setAttribute(
                'aria-label',
                `${safeCount} new ${safeCount === 1 ? 'response' : 'responses'} needing attention`
            );
        });
    };

    window.setNeedsAttentionUnreadCount = setNeedsAttentionUnreadCount;

    const resultMeta = document.querySelector(
        "[data-inquiry-result-meta]"
    );

    const emptyState = document.querySelector(
        ".empty-state"
    );

    const emptyIcon = emptyState?.querySelector(
        ".empty-icon"
    );

    const emptyHeading = emptyState?.querySelector(
        "h2"
    );

    const emptyMessage = emptyState?.querySelector(
        "p"
    );

    const normalize = (value) => {
        return String(value ?? "")
            .trim()
            .toLocaleLowerCase();
    };

    const getInquiryCards = () => {
        return Array.from(
            document.querySelectorAll(".inquiry-card")
        );
    };

    const getFilterForStatus = (status) => {
        if (status === "awaiting_confirmation") {
            return "needs_attention";
        }

        if (status === "pending") {
            return "pending";
        }

        if (status === "needs_follow_up") {
            return "follow_up";
        }

        if (status === "answered") {
            return "answered";
        }

        if (status === "trashed") {
            return "trashed";
        }

        return null;
    };

    const matchesFilter = (card, filter) => {
        return getFilterForStatus(card.dataset.status) === filter;
    };

    const matchesSearch = (card, query) => {
        if (!query) {
            return true;
        }

        const question =
            card.querySelector(".inquiry-question")?.textContent || "";

        const agency =
            card.querySelector(".inquiry-agency")?.textContent || "";

        const id =
            card.dataset.id || "";

        return normalize(
            `${question} ${agency} ${id}`
        ).includes(query);
    };

    const updateEmptyState = (
        filter,
        visibleCards,
        searchQuery
    ) => {
        if (!emptyState) {
            return;
        }

        emptyState.hidden =
            visibleCards.length > 0;

        if (visibleCards.length > 0) {
            return;
        }

        if (searchQuery) {
            if (emptyIcon) {
                const icon = document.createElement("i");
                icon.className = "ph-light ph-magnifying-glass";
                emptyIcon.replaceChildren(icon);
            }

            if (emptyHeading) {
                emptyHeading.textContent =
                    "No matching inquiries";
            }

            if (emptyMessage) {
                emptyMessage.textContent =
                    "Try a different keyword, agency name, or inquiry number.";
            }

            return;
        }

        const states = {
            needs_attention: {
                icon: "ph-chat-circle-check",
                heading: "Nothing needs your attention",
                message: "You have no responses waiting for your review.",
            },
            pending: {
                icon: "ph-hourglass",
                heading: "No pending inquiries",
                message: "You have no questions waiting for a response.",
            },
            follow_up: {
                icon: "ph-arrow-u-up-left",
                heading: "No follow-up inquiries",
                message: "You have no inquiries waiting for a follow-up response.",
            },
            answered: {
                icon: "ph-chat-circle-dots",
                heading: "No answered inquiries yet",
                message: "Your submitted questions will appear here once an office responds.",
            },
            trashed: {
                icon: "ph-trash",
                heading: "No trashed inquiries",
                message: "Inquiries moved to the trash by administration will appear here with their reason.",
            },
        };

        const state =
            states[filter] ||
            states.needs_attention;

        if (emptyIcon) {
            const icon = document.createElement("i");
            icon.className = `ph-light ${state.icon}`;
            emptyIcon.replaceChildren(icon);
        }

        if (emptyHeading) {
            emptyHeading.textContent =
                state.heading;
        }

        if (emptyMessage) {
            emptyMessage.textContent =
                state.message;
        }
    };

    const updateFilterCounts = (cards) => {
        const counts = {
            needs_attention: 0,
            pending: 0,
            follow_up: 0,
            answered: 0,
            trashed: 0,
        };

        cards.forEach((card) => {
            const filter =
                getFilterForStatus(card.dataset.status);

            if (filter && counts[filter] !== undefined) {
                counts[filter] += 1;
            }
        });

        filterButtons.forEach((button) => {
            const filter = button.dataset.filter;
            const count = counts[filter] ?? 0;
            const countElement = button.querySelector(
                "[data-filter-count]"
            );

            if (countElement) {
                countElement.textContent = String(count);
            }
        });

        const currentFilter = filterButtons.find((button) => button.classList.contains("active"))?.dataset.filter || "needs_attention";
        updateFilterTrigger(currentFilter, cards);
    };

    const updateResultMeta = (
        visibleCards,
        totalCards
    ) => {
        if (!resultMeta) {
            return;
        }

        const searchActive =
            Boolean(normalize(searchInput?.value));

        resultMeta.textContent = searchActive
            ? `${visibleCards.length} of ${totalCards}`
            : `${visibleCards.length} ${visibleCards.length === 1 ? "inquiry" : "inquiries"}`;
    };

    const applyFilter = (
        filter,
        cards = getInquiryCards()
    ) => {
        const searchQuery =
            normalize(searchInput?.value);

        const visibleCards = [];

        cards.forEach((card) => {
            const shouldShow =
                matchesFilter(card, filter) &&
                matchesSearch(card, searchQuery);

            card.hidden = !shouldShow;

            if (shouldShow) {
                visibleCards.push(card);
            }

            if (!shouldShow) {
                const toggle =
                    card.querySelector(".inquiry-toggle");

                const details =
                    card.querySelector(".inquiry-details");

                if (toggle) {
                    toggle.setAttribute(
                        "aria-expanded",
                        "false"
                    );
                }

                if (details) {
                    details.setAttribute(
                        "aria-hidden",
                        "true"
                    );
                    details.hidden = true;
                }

                card.classList.remove("expanded");
            }
        });

        if (clearSearchButton) {
            clearSearchButton.hidden =
                searchQuery.length === 0;
        }

        updateResultMeta(
            visibleCards,
            cards.length
        );

        updateEmptyState(
            filter,
            visibleCards,
            searchQuery
        );

        updateFilterCounts(cards);
    };

    const getCurrentFilter = () => {
        return (
            filterButtons.find(
                (button) =>
                    button.classList.contains("active")
            )?.dataset.filter ||
            "needs_attention"
        );
    };

    filterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const selectedFilter =
                button.dataset.filter;

            if (!selectedFilter) {
                return;
            }

            filterButtons.forEach((filterButton) => {
                const isActive =
                    filterButton === button;

                filterButton.classList.toggle(
                    "active",
                    isActive
                );

                filterButton.setAttribute(
                    "aria-pressed",
                    isActive ? "true" : "false"
                );
            });

            applyFilter(selectedFilter);
            updateFilterTrigger(selectedFilter);
            closeFilterDrawer();

            /*
             * Do not acknowledge trashed inquiries merely because the
             * citizen opened the tab. Each record is acknowledged when
             * that specific card is expanded.
             */
        });
    });

    searchInput?.addEventListener(
        "input",
        () => {
            applyFilter(getCurrentFilter());
        }
    );

    clearSearchButton?.addEventListener(
        "click",
        () => {
            if (!searchInput) {
                return;
            }

            searchInput.value = "";
            searchInput.focus();
            applyFilter(getCurrentFilter());
        }
    );

    window.addEventListener(
        "inquiry:updated",
        () => {
            /*
             * Status changes are reflected on the card before this
             * event fires. Re-running the active filter therefore
             * removes the card from its old tab, adds it to its new
             * tab's count, and keeps the selected tab stable.
             */
            applyFilter(getCurrentFilter());
        }
    );

    const initialCards = getInquiryCards();
    const initialFilter = "needs_attention";

    filterButtons.forEach((button) => {
        const isActive =
            button.dataset.filter === initialFilter;

        button.classList.toggle(
            "active",
            isActive
        );

        button.setAttribute(
            "aria-pressed",
            isActive ? "true" : "false"
        );
    });

    applyFilter(
        initialFilter,
        initialCards
    );
    updateFilterTrigger(initialFilter, initialCards);
}

export {
    initializeFilters,
};
