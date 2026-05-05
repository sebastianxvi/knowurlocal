const menuToggle = document.getElementById("menuToggle");
const navDrawer = document.getElementById("navDrawer");


const navLinks = document.querySelectorAll(".nav-link");

const accountToggle = document.getElementById("accountToggle");
const accountDropdown = document.getElementById("accountDropdown");

// 🔥 TOGGLE DRAWER
menuToggle.addEventListener("click", () => {
    navDrawer.classList.toggle("is-open");
});

// 🔥 CLOSE DRAWER WHEN CLICKING LINK
navLinks.forEach(link => {
    link.addEventListener("click", closeMenu);
});

// 🔥 ACCOUNT DROPDOWN TOGGLE
accountToggle.addEventListener("click", (e) => {
    e.stopPropagation(); // prevent closing immediately
    accountDropdown.classList.toggle("active");
});

// 🔥 GLOBAL CLICK HANDLER (COMBINED)
document.addEventListener("click", (event) => {

    // CLOSE DRAWER IF CLICK OUTSIDE
    if (!navDrawer.contains(event.target) && !menuToggle.contains(event.target)) {
        closeMenu();
    }

    // CLOSE ACCOUNT DROPDOWN IF CLICK OUTSIDE
    if (
        !accountToggle.contains(event.target) &&
        !accountDropdown.contains(event.target)
    ) {
        accountDropdown.classList.remove("active");
    }
});

// 🔥 CLOSE MENU FUNCTION
function closeMenu() {
    navDrawer.classList.remove("is-open");

    const icon = menuToggle.querySelector("i");

    icon.classList.remove("fa-xmark");
    icon.classList.add("fa-bars");
}