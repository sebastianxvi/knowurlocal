// ================= FLASH SUCCESS =================
document.addEventListener("DOMContentLoaded", () => {

    if (!window.__FLASH_SUCCESS__) return;

    showAlertModal({
        title: "Success",
        text: window.__FLASH_SUCCESS__,
        icon: "✓",
        variant: "success",
        confirmText: "OK",
        showCancel: false,

        onConfirm: closeAlertModal
    });

    setTimeout(closeAlertModal, 1500);

    window.__FLASH_SUCCESS__ = null;
});

// ================= MODAL ELEMENTS =================
const modalBack = document.getElementById('modal-back');
const categoryForm = document.getElementById('categoryForm');
const modalTitle = document.getElementById('modal-title');

const formMethod = document.getElementById('form-method');

const categoryName = document.getElementById('category_name');
// ================= CURRENT MODE =================
// ================= CURRENT MODE =================

let currentMode = "add";

/*
 * Stores the ID of the category currently being edited.
 *
 * null means that the modal is currently creating
 * a new category.
 */
let currentCategoryId = null;
let originalCategoryName = "";
let originalCategoryColor = "";

const categorySaveButton = document.querySelector(
    'button[type="submit"][form="categoryForm"]'
);

function setCategorySaveEnabled(enabled) {
    if (!categorySaveButton) return;

    categorySaveButton.disabled = !enabled;
    categorySaveButton.setAttribute("aria-disabled", String(!enabled));
}

function updateEditSaveState() {
    if (currentMode !== "edit") {
        setCategorySaveEnabled(true);
        return;
    }

    const nameChanged =
        categoryName.value.trim() !== originalCategoryName;

    const colorChanged =
        colorInput.value.trim().toUpperCase() !== originalCategoryColor;

    setCategorySaveEnabled(nameChanged || colorChanged);
}

// ================= OPEN =================
function openModal(){

    currentMode = "add";

    /*
     * No existing category is being edited.
     */
    currentCategoryId = null;
    originalCategoryName = "";
    originalCategoryColor = "";

    categoryForm.reset();

    if(window.resetFormValidation){
        resetFormValidation(categoryForm);
    }

    modalTitle.textContent = 'Add Category';

    categoryForm.action = window.categoryRoutes.store;

    formMethod.value = "POST";

    categoryName.value = "";

    colorInput.value = "#2563EB";

    /*
 * Check the default color immediately when the
 * Add Category modal opens.
 */
updateColorUsage(
    colorInput.value
);

    colorChips.forEach(chip => {

        chip.classList.toggle(
            "active",
            chip.dataset.color === "#2563EB"
        );

    });

    updateColorChipUsage();
    setCategorySaveEnabled(true);

    modalBack.classList.add('active');

}
window.openModal = openModal;

function openEditModal(button){

    currentMode = "edit";

    /*
     * Remember which category is being edited.
     *
     * Its own color should not be reported as
     * "already used" by another category.
     */
    currentCategoryId =
        Number(button.dataset.id);

    if(window.resetFormValidation){
        resetFormValidation(categoryForm);
    }

    modalTitle.textContent = "Edit Category";

    categoryName.value = button.dataset.name;
    colorInput.value = button.dataset.color;

    originalCategoryName = categoryName.value.trim();
    originalCategoryColor = colorInput.value.trim().toUpperCase();

    /*
 * Check the category's current color.
 */
updateColorUsage(
    colorInput.value
);

    formMethod.value = "PUT";

    categoryForm.action = button.dataset.update;

    colorChips.forEach(chip => {

        chip.classList.toggle(
            "active",
            chip.dataset.color === button.dataset.color
        );

    });

    updateEditSaveState();
    modalBack.classList.add("active");

}

// ================= CLOSE =================
function closeModal(){

    modalBack.classList.remove('active');

}
window.closeModal = closeModal;

// ================= CLOSE WHEN CLICKING BACKDROP =================
modalBack.addEventListener('click', (e) => {

    if(e.target === modalBack){
        closeModal();
    }

});


// ================= COLOR PICKER =================

const colorInput =
    document.getElementById('display_color');

const colorChips =
    document.querySelectorAll('.color-chip');


/*
 * Existing category color information supplied by Laravel.
 * Used only for informative picker indicators.
 */
const categoryColorUsage = window.categoryColorUsage || [];

function categoriesUsingColor(selectedColor) {
    const normalizedColor = String(selectedColor || "").toUpperCase();

    return categoryColorUsage.filter(category => (
        String(category.display_color || "").toUpperCase() === normalizedColor &&
        Number(category.id) !== currentCategoryId
    ));
}

/*
 * Mark every palette swatch that is already used by another
 * active category. Duplicate colors remain allowed.
 */
function updateColorChipUsage() {
    colorChips.forEach(chip => {
        const matches = categoriesUsingColor(chip.dataset.color);
        const names = matches.map(category => category.category_name).filter(Boolean);
        const badge = chip.querySelector(".color-chip-usage-count");

        chip.classList.toggle("has-usage", matches.length > 0);
        chip.title = matches.length
            ? `Already used by: ${names.join(", ")}`
            : `${chip.dataset.color} — not used by another category`;

        chip.setAttribute(
            "aria-label",
            matches.length
                ? `Select ${chip.dataset.color}. Already used by ${names.join(", ")}.`
                : `Select ${chip.dataset.color}. Not used by another category.`
        );

        if (badge) badge.remove();

        if (matches.length) {
            const countBadge = document.createElement("span");
            countBadge.className = "color-chip-usage-count";
            countBadge.textContent = matches.length > 99 ? "99+" : String(matches.length);
            countBadge.setAttribute("aria-hidden", "true");
            chip.appendChild(countBadge);
        }
    });
}

/*
 * Show the names of other categories using the currently
 * selected color. This is informational, not a validation error.
 */
function updateColorUsage(selectedColor) {
    const usageElement = document.getElementById("color-usage");
    if (!usageElement) return;

    const matches = categoriesUsingColor(selectedColor);
    usageElement.replaceChildren();

    if (!matches.length) return;

    const icon = document.createElement("i");
    icon.className = "ph-light ph-info";
    icon.setAttribute("aria-hidden", "true");

    const message = document.createElement("span");
    message.textContent = `Also used by ${matches.map(category => category.category_name).join(", ")}`;

    const wrapper = document.createElement("span");
    wrapper.className = "color-usage-used";
    wrapper.append(icon, message);
    usageElement.appendChild(wrapper);
}

colorChips.forEach(chip => {

    chip.addEventListener('click', () => {

        /*
         * Remove the active state from all color chips.
         */
        colorChips.forEach(c =>
            c.classList.remove('active')
        );


        /*
         * Mark the selected color as active.
         */
        chip.classList.add('active');


        /*
         * Store the selected color in the form.
         */
        colorInput.value =
            chip.dataset.color;


        /*
         * Check whether another category already
         * uses this color.
         */
        updateColorUsage(chip.dataset.color);
        updateEditSaveState();

    });

});

// Track category-name edits and return Save to disabled if reverted.
categoryName.addEventListener("input", updateEditSaveState);


document.querySelectorAll('.edit-category').forEach(button => {

    button.addEventListener('click', () => {

        openEditModal(button);

    });

});

// ================= CONFIRM + ADMIN PASSWORD =================
function requestCategoryAdminPassword(form, actionDescription, subject) {
    if (typeof window.requestAdminPasswordForForm !== "function") {
        showAlertModal({
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
        verificationUrl: window.categoryRoutes.verifyLifecyclePassword,
        actionDescription,
        subject
    });
}

// Create and edit both require an explicit confirmation followed by password verification.
categoryForm.addEventListener("submit", function(e) {
    e.preventDefault();

    updateEditSaveState();

    if (currentMode === "edit" && categorySaveButton?.disabled) {
        return;
    }

    const isCreate = currentMode === "add";
    const subject = isCreate
        ? "this new category"
        : `"${categoryName.value.trim() || "this category"}"`;

    showAlertModal({
        title: isCreate ? "Create category?" : "Save changes?",
        text: isCreate
            ? "Review the category name and display color before creating this category."
            : "Make sure the category information is correct.",
        icon: "✓",
        variant: "success",
        confirmText: isCreate ? "Create" : "Save",
        cancelText: "Cancel",
        showCancel: true,
        onConfirm: () => {
            requestCategoryAdminPassword(
                categoryForm,
                isCreate ? "create" : "update",
                subject
            );
        }
    });
});

// ================= MOVE TO TRASH =================
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".delete-category");
    if (!btn) return;

    const form = btn.closest("form");
    if (!form) return;

    e.preventDefault();

    const name = btn.dataset.categoryName || "this category";

    showAlertModal({
        title: "Move category to Trash?",
        text: `"${name}" will be moved to the Trashed records.`,
        icon: "!",
        variant: "danger",
        confirmText: "Move to Trash",
        cancelText: "Cancel",
        showCancel: true,
        input: {
            label: "Reason for trashing",
            placeholder: "Explain why this category is being moved to the trash…",
            required: true,
            minLength: 3,
            maxLength: 1000,
            validationMessage: "Please provide a reason (at least 3 characters)."
        },
        onConfirm: (reason) => {
            let reasonInput = form.querySelector('input[name="reason"]');
            if (!reasonInput) {
                reasonInput = document.createElement("input");
                reasonInput.type = "hidden";
                reasonInput.name = "reason";
                form.appendChild(reasonInput);
            }
            reasonInput.value = reason.trim();

            requestCategoryAdminPassword(
                form,
                "move",
                `"${name}" to the trash`
            );
        }
    });
});

// ================= RESTORE =================
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".restore-category");
    if (!btn) return;

    const form = btn.closest("form");
    if (!form) return;

    e.preventDefault();

    const name = btn.dataset.categoryName || "this category";

    showAlertModal({
        title: "Restore this category?",
        text: `"${name}" will be restored and returned to the active category list.`,
        icon: "↶",
        variant: "success",
        confirmText: "Restore",
        cancelText: "Cancel",
        showCancel: true,
        onConfirm: () => {
            requestCategoryAdminPassword(form, "restore", `"${name}"`);
        }
    });
});

// ================= PERMANENT DELETE =================
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".force-delete-category");
    if (!btn) return;

    const form = btn.closest("form");
    if (!form) return;

    e.preventDefault();

    const name = btn.dataset.categoryName || "this category";

    showAlertModal({
        title: "Delete this category permanently?",
        text: `"${name}" will be permanently deleted. This action cannot be undone.`,
        icon: "!",
        variant: "danger",
        confirmText: "Delete Permanently",
        cancelText: "Cancel",
        showCancel: true,
        onConfirm: () => {
            requestCategoryAdminPassword(form, "permanently delete", `"${name}"`);
        }
    });
});

// ================= FLASH ERROR =================
document.addEventListener("DOMContentLoaded", () => {

    if (!window.__FLASH_ERROR__) return;

    showAlertModal({
        title: "Cannot delete category",
        text: window.__FLASH_ERROR__,
        icon: "!",
        variant: "danger",
        confirmText: "OK",
        showCancel: false,

        onConfirm: closeAlertModal
    });

    window.__FLASH_ERROR__ = null;
});
// ================= CATEGORY AGENCY DIRECTORY =================
(() => {
    const modal = document.getElementById('category-agencies-modal');
    const title = document.getElementById('category-agencies-title');
    const subtitle = document.getElementById('category-agencies-subtitle');
    const summary = document.getElementById('category-agencies-summary');
    const list = document.getElementById('category-agencies-list');
    const closeButton = document.getElementById('category-agencies-close');

    if (!modal || !title || !subtitle || !summary || !list || !closeButton) return;

    const directory = window.categoryAgencyDirectory || {};
    let lastTrigger = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function render(categoryId, categoryName) {
        const agencies = Array.isArray(directory[categoryId])
            ? directory[categoryId]
            : [];

        title.textContent = categoryName || 'Agencies';
        subtitle.textContent = 'Registered agencies in this category';
        summary.textContent = agencies.length === 1
            ? '1 active agency is currently registered in this category.'
            : `${agencies.length} active agencies are currently registered in this category.`;

        if (!agencies.length) {
            list.innerHTML = `
                <div class="category-agencies-empty">
                    <i class="ph-light ph-buildings" aria-hidden="true"></i>
                    No active agencies are currently registered in this category.
                </div>
            `;
            return;
        }

        list.innerHTML = agencies.map((agency) => {
            const meta = [];
            if (agency.abbreviation) meta.push(`<span class="category-agency-item-abbr">${escapeHtml(agency.abbreviation)}</span>`);
            if (agency.type) meta.push(`<span class="category-agency-item-dot">•</span><span>${escapeHtml(agency.type)}</span>`);

            return `
                <a
                    class="category-agency-item"
                    href="${escapeHtml(agency.view_url || '#')}"
                    aria-label="View ${escapeHtml(agency.name)} in NGA and NGO Management"
                >
                    <div class="category-agency-item-icon" aria-hidden="true">
                        <i class="ph-light ph-buildings"></i>
                    </div>
                    <div class="category-agency-item-body">
                        <p class="category-agency-item-name">${escapeHtml(agency.name)}</p>
                        ${meta.length ? `<div class="category-agency-item-meta">${meta.join('')}</div>` : ''}
                    </div>
                    <i class="ph-light ph-arrow-up-right category-agency-item-link-icon" aria-hidden="true"></i>
                </a>
            `;
        }).join('');
    }

    function open(trigger) {
        lastTrigger = trigger;
        render(trigger.dataset.categoryId, trigger.dataset.categoryName);
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        closeButton.focus();
    }

    function close() {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        if (lastTrigger) lastTrigger.focus();
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.category-agency-count');
        if (!trigger || trigger.classList.contains('category-agency-count--empty')) return;
        open(trigger);
    });

    closeButton.addEventListener('click', close);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('active')) close();
    });
})();


document.addEventListener("DOMContentLoaded", () => {
    updateColorChipUsage();
});
