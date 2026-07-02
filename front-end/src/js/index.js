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

const compareToggle = document.getElementById("compare-toggle");
const compareTable = document.getElementById("compare-table");
const compareChevron = document.getElementById("compare-chevron");

compareToggle.addEventListener("click", () => {
  const isExpanded = compareToggle.getAttribute("aria-expanded") === "true";
  compareTable.classList.toggle("hidden", isExpanded);
  compareChevron.classList.toggle("rotate-180", !isExpanded);
  compareToggle.setAttribute("aria-expanded", String(!isExpanded));
});
