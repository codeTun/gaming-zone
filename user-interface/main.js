// Initialize Lucide icons
lucide.createIcons();

// Handle Logout
function handleLogout() {
  console.log("Logging out...");
  window.location.href = "../home.html";
  // Implement actual logout logic here
}

// Define navigation items and corresponding sections
const navItems = document.querySelectorAll(".nav-item");
const sections = {
  homeLink: "homeSection",
  gamesLink: "gamesSection",
  tournamentsLink: "tournamentSection",
  eventsLink: "eventsSection", // Added events section
};

// Add click event listeners to navigation items
navItems.forEach((item) => {
  const sectionId = sections[item.id];
  if (sectionId) {
    item.addEventListener("click", (e) => {
      e.preventDefault();

      // Remove 'active' class from all nav items
      navItems.forEach((i) => i.classList.remove("active"));

      // Add 'active' class to the clicked nav item
      item.classList.add("active");

      // Hide all sections
      Object.values(sections).forEach((secId) => {
        const section = document.getElementById(secId);
        if (section) {
          section.style.display = "none";
        }
      });

      // Show the selected section
      const sectionToShow = document.getElementById(sectionId);
      if (sectionToShow) {
        sectionToShow.style.display = "block";
      }
    });
  }

  // Hover effects for navigation items
  item.addEventListener("mouseenter", () => {
    const chevron = item.querySelector(".chevron");
    if (chevron) chevron.style.opacity = "1";
  });
  item.addEventListener("mouseleave", () => {
    const chevron = item.querySelector(".chevron");
    if (chevron) chevron.style.opacity = "0";
  });
});

// Handle CTA buttons for navigation
const viewTournamentsBtn = document.querySelector("#viewTournamentsBtn");
if (viewTournamentsBtn) {
  viewTournamentsBtn.addEventListener("click", (e) => {
    e.preventDefault();
    const tournamentsLink = document.querySelector("#tournamentsLink");
    if (tournamentsLink) tournamentsLink.click();
  });
}

const browseGamesBtn = document.querySelector("#browseGamesBtn");
if (browseGamesBtn) {
  browseGamesBtn.addEventListener("click", (e) => {
    e.preventDefault();
    const gamesLink = document.querySelector("#gamesLink");
    if (gamesLink) gamesLink.click();
  });
}
