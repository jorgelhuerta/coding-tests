const menuToggle = document.getElementById("menu-toggle");
const mobileNav = document.getElementById("mobile-nav");
const iconMenuOpen = document.getElementById("icon-menu-open");
const iconMenuClose = document.getElementById("icon-menu-close");

function closeMobileNav() {
  mobileNav.classList.add("hidden");
  mobileNav.classList.remove("flex");
  iconMenuOpen.classList.remove("hidden");
  iconMenuClose.classList.add("hidden");
  menuToggle.setAttribute("aria-expanded", "false");
}

function openMobileNav() {
  mobileNav.classList.remove("hidden");
  mobileNav.classList.add("flex");
  iconMenuOpen.classList.add("hidden");
  iconMenuClose.classList.remove("hidden");
  menuToggle.setAttribute("aria-expanded", "true");
}

menuToggle.addEventListener("click", () => {
  const isOpen = menuToggle.getAttribute("aria-expanded") === "true";
  isOpen ? closeMobileNav() : openMobileNav();
});

mobileNav.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", closeMobileNav);
});
