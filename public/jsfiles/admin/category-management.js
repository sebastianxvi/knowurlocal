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
let currentMode = "add";

// ================= OPEN =================
function openModal(){
    currentMode = "add";

    categoryForm.reset();

    if(window.resetFormValidation){
        resetFormValidation(categoryForm);
    }

    modalTitle.textContent = 'Add Category';

    categoryForm.action = window.categoryRoutes.store;

    formMethod.value = "POST";

    categoryName.value = "";

    colorInput.value = "#3B82F6";

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

    if(window.resetFormValidation){
        resetFormValidation(categoryForm);
    }

    modalTitle.textContent = "Edit Category";

    categoryName.value = button.dataset.name;

    colorInput.value = button.dataset.color;

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

const colorInput = document.getElementById('display_color');

const colorChips = document.querySelectorAll('.color-chip');

colorChips.forEach(chip => {

    chip.addEventListener('click', () => {

        colorChips.forEach(c => c.classList.remove('active'));

        chip.classList.add('active');

        colorInput.value = chip.dataset.color;

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
        title: "Delete this category?",
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