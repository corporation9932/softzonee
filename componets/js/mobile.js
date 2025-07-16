const openMenu = document.getElementById("open-menu");
const closeMenu = document.getElementById("close-menu");
const mobileMenu = document.getElementById("mobile-menu");
const mobileOverlay = document.getElementById("mobile-overlay");
const header = document.getElementById("header");

openMenu.addEventListener("click", function () {
  mobileMenu.classList.remove("translate-x-full");
  mobileOverlay.classList.remove("opacity-0", "pointer-events-none");
  header.classList.add("hidden");
});

closeMenu.addEventListener("click", function () {
  mobileMenu.classList.add("translate-x-full");
  mobileOverlay.classList.add("opacity-0", "pointer-events-none");
  header.classList.remove("hidden");
});

mobileOverlay.addEventListener("click", function () {
  mobileMenu.classList.add("translate-x-full");
  mobileOverlay.classList.add("opacity-0", "pointer-events-none");
  header.classList.remove("hidden");
});

window.addEventListener("resize", function () {
  if (window.innerWidth >= 768) {
    mobileMenu.classList.add("translate-x-full");
    mobileOverlay.classList.add("opacity-0", "pointer-events-none");
    header.classList.remove("hidden");
  }
});