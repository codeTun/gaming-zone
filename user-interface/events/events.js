document.addEventListener("DOMContentLoaded", () => {
  // Sample events data
  const events = [
    {
      id: 1,
      title: "Gaming Convention 2024",
      category: "Convention",
      country: "USA",
      date: "2024-06-15",
      location: "Los Angeles, CA",
      image: "https://th.bing.com/th/id/OIP.otKiTQ80GoHybJB7WJMJDQAAAA?rs=1&pid=ImgDetMain",
      description:
        "The biggest gaming convention of the year featuring latest releases and tournaments.",
      price: "$50",
    },
    {
      id: 2,
      title: "ESports World Cup",
      category: "Conference",
      country: "Japan",
      date: "2024-07-20",
      location: "Tokyo",
      image: "https://www.gannett-cdn.com/presto/2018/08/23/USAT/351fb7d8-7749-42ea-94f8-7a364bab9b97-666.jpg?crop=4499,2531,x0,y469&width=3200&height=1680&fit=bounds",
      description:
        "International esports competition with top teams from around the world.",
      price: "$75",
    },
    // Add more events as needed
  ];

  // Render events
  function renderEvents(filteredEvents = events) {
    const eventsGrid = document.getElementById("eventsGrid");
    eventsGrid.innerHTML = "";

    filteredEvents.forEach((event) => {
      const eventCard = document.createElement("div");
      eventCard.className = "event-card";
      eventCard.innerHTML = `
          <img src="${event.image}" alt="${event.title}" class="event-image">
          <div class="event-details">
            <span class="event-category">${event.category}</span>
            <h3 class="event-title">${event.title}</h3>
            <div class="event-info">
              <i data-lucide="calendar"></i>
              ${event.date}
            </div>
            <div class="event-info">
              <i data-lucide="map-pin"></i>
              ${event.location}
            </div>
            <p class="event-description">${event.description}</p>
            <div class="event-footer">
              <span class="event-price">${event.price}</span>
              <button class="event-register-btn">Register Now</button>
            </div>
          </div>
        `;
      eventsGrid.appendChild(eventCard);
    });

    // Initialize Lucide icons
    lucide.createIcons();
  }

  // Search and filter functionality
  const searchInput = document.getElementById("eventSearchInput");
  const categoryFilter = document.getElementById("eventCategoryFilter");
  const countryFilter = document.getElementById("eventCountryFilter");

  function filterEvents() {
    const searchTerm = searchInput.value.toLowerCase();
    const categoryValue = categoryFilter.value.toLowerCase();
    const countryValue = countryFilter.value.toLowerCase();

    const filteredEvents = events.filter((event) => {
      const matchesSearch =
        event.title.toLowerCase().includes(searchTerm) ||
        event.description.toLowerCase().includes(searchTerm);
      const matchesCategory =
        !categoryValue || event.category.toLowerCase() === categoryValue;
      const matchesCountry =
        !countryValue || event.country.toLowerCase() === countryValue;

      return matchesSearch && matchesCategory && matchesCountry;
    });

    renderEvents(filteredEvents);
  }

  searchInput.addEventListener("input", filterEvents);
  categoryFilter.addEventListener("change", filterEvents);
  countryFilter.addEventListener("change", filterEvents);

  // Initial render
  renderEvents();
});
