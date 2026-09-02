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

// ================= OPEN =================
function openModal(){

    currentMode = "add";

    /*
     * No existing category is being edited.
     */
    currentCategoryId = null;

    categoryForm.reset();

    if(window.resetFormValidation){
        resetFormValidation(categoryForm);
    }

    modalTitle.textContent = 'Add Category';

    categoryForm.action = window.categoryRoutes.store;

    formMethod.value = "POST";

    categoryName.value = "";

    colorInput.value = "#3B82F6";

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
            chip.dataset.color === "#3B82F6"
        );

    });

    console.log(categoryForm.action);
console.log(formMethod.value);
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
 * Existing category color information supplied by
 * the Laravel controller.
 *
 * This data is used only to inform the administrator
 * about colors that are already being used.
 */
const categoryColorUsage =
    window.categoryColorUsage || [];



/*
 * Check whether the selected color is already being used
 * by another category.
 *
 * This is an informational UX feature only.
 * It does not prevent the administrator from using the color.
 */
function updateColorUsage(selectedColor){

    /*
     * Normalize the selected color so comparisons are
     * case-insensitive.
     *
     * Example:
     * #3b82f6 and #3B82F6 are treated as the same color.
     */
    const normalizedColor =
        selectedColor.toUpperCase();


    /*
     * Find all categories that currently use this color.
     *
     * The category being edited is excluded because a
     * category using its own existing color is not a conflict.
     */
    const matchingCategories =
        categoryColorUsage.filter(category => {

            return (
                category.display_color.toUpperCase() === normalizedColor &&
                category.id !== currentCategoryId
            );

        });


    /*
     * Find the small information area underneath
     * the color palette.
     */
    const usageElement =
        document.getElementById("color-usage");


    /*
     * Stop safely if the indicator has not been added
     * to the Blade yet.
     */
    if(!usageElement){
        return;
    }


    /*
     * No other category uses this color.
     */
    if(matchingCategories.length === 0){

    /*
     * No other category uses this color.
     *
     * Because duplicate colors are allowed, there is
     * no need to display an "Available" message.
     *
     * Leaving the area empty keeps the color picker
     * visually clean.
     */
    usageElement.innerHTML = "";

    return;
}


    /*
     * Collect the names of categories using this color.
     */
    const categoryNames =
        matchingCategories
            .map(category => category.category_name)
            .join(", ");


    /*
     * Inform the administrator that the color is already
     * being used.
     *
     * This is intentionally NOT treated as an error.
     */
    usageElement.innerHTML = `
        <span class="color-usage-used">
            <i class="ph-light ph-info"></i>
            Used by ${categoryNames}
        </span>
    `;
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
        updateColorUsage(
            chip.dataset.color
        );

    });

});


document.querySelectorAll('.edit-category').forEach(button => {

    button.addEventListener('click', () => {

        openEditModal(button);

    });

});

// ================= CONFIRM EDIT =================
categoryForm.addEventListener("submit", function(e){

    // Creating a category? Submit immediately.
    if(currentMode === "add"){
        return;
    }

    // Editing? Ask for confirmation.
    e.preventDefault();

    showAlertModal({
        title: "Save changes?",
        text: "Make sure the category information is correct.",
        icon: "✓",
        variant: "success",
        confirmText: "Save",
        showCancel: true,

        onConfirm: () => {
            categoryForm.submit();
        }
    });

});

// ================= DELETE WITH MODAL =================
document.addEventListener("click", function (e) {

    const btn = e.target.closest(".delete-category");
    if (!btn) return;

    const form = btn.closest("form");
    if (!form) return;

    e.preventDefault();

    showAlertModal({
        title: "Move category to Trash?",
        text: "This category will be moved to the Trashed records.",
        icon: "!",
        variant: "danger",
        confirmText: "Delete",
        showCancel: true,

        onConfirm: () => {
            form.submit();
        }
    });

});

// ================= RESTORE WITH MODAL =================
document.addEventListener("click", function (e) {

    /*
     * Detect a click on the Restore button.
     *
     * closest() also works when the administrator clicks
     * the icon inside the button.
     */
    const btn = e.target.closest(".restore-category");

    /*
     * Ignore clicks that are unrelated to restoration.
     */
    if (!btn) return;

    /*
     * Find the form containing the Restore button.
     *
     * The form already contains the correct route,
     * CSRF token, and PATCH method from Blade.
     */
    const form = btn.closest("form");

    /*
     * Safety check before attempting submission.
     */
    if (!form) return;

    /*
     * Prevent the browser from submitting automatically.
     */
    e.preventDefault();

    /*
     * Get the category name supplied by Blade.
     *
     * This gives the administrator useful context
     * in the confirmation dialog.
     */
    const categoryName =
        btn.dataset.categoryName || "this category";

    /*
     * Show a confirmation dialog before restoring.
     */
    showAlertModal({

        title: "Restore this category?",

        text:
            `"${categoryName}" will be restored and returned to the active category list.`,

        icon: "↶",

        variant: "success",

        confirmText: "Restore",

        showCancel: true,

        /*
         * Submit only after explicit confirmation.
         */
        onConfirm: () => {
            form.submit();
        }

    });

});


// ================= PERMANENT DELETE WITH MODAL =================
document.addEventListener("click", function (e) {

    /*
     * Detect a click on the permanent-delete button.
     */
    const btn = e.target.closest(".force-delete-category");

    /*
     * Ignore unrelated clicks.
     */
    if (!btn) return;

    /*
     * Find the form containing the button.
     */
    const form = btn.closest("form");

    /*
     * Safety check before submitting.
     */
    if (!form) return;

    /*
     * Prevent immediate form submission.
     */
    e.preventDefault();

    /*
     * Retrieve the category name for the confirmation dialog.
     */
    const categoryName =
        btn.dataset.categoryName || "this category";

    /*
     * Permanent deletion is irreversible,
     * so use the danger confirmation style.
     */
    showAlertModal({

        title: "Delete this category permanently?",

        text:
            `"${categoryName}" will be permanently deleted. This action cannot be undone.`,

        icon: "!",

        variant: "danger",

        confirmText: "Delete Permanently",

        showCancel: true,

        /*
         * Only submit the form after confirmation.
         */
        onConfirm: () => {
            form.submit();
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