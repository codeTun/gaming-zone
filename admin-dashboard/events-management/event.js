let events = [];
// DOM Elements
const eventGrid = document.getElementById("event_grid");
const searchInput = document.getElementById("event_search_input");
const addButton = document.getElementById("event_add_btn");
const modal = document.getElementById("event_modal");
const modalTitle = document.getElementById("event_modal_title");
const closeModal = document.getElementById("event_close_modal");
const cancelButton = document.getElementById("event_cancel_btn");
const eventForm = document.getElementById("event_form");

let editingEventId = null;

// Fetch and initialize events
fetch("/events-management/events.json")
  .then((response) => response.json())
  .then((data) => {
    events = data;
    filterEvents();
  })
  .catch((error) => console.error("Error loading events:", error));

// Filter events
function filterEvents(searchTerm = "") {
  const filteredEvents = events.filter(
    (event) =>
      event.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      event.description.toLowerCase().includes(searchTerm.toLowerCase())
  );
  renderEvents(filteredEvents);
}

// Render events
function renderEvents(eventsToRender) {
  eventGrid.innerHTML = "";
  eventsToRender.forEach((event) => {
    const card = document.createElement("div");
    card.className = "event_card";
    card.innerHTML = `
      <img src="${event.image}" alt="${event.title}" class="event_card_image">
      <div class="event_card_content">
        <h3 class="event_card_title">${event.title}</h3>
        <p class="event_card_description">${event.description}</p>
        <div class="event_card_date">
          <i data-lucide="calendar" class="event_btn_icon"></i>
          ${formatDate(event.date)}
        </div>
        <div class="event_card_actions">
          <button onclick="editEvent(${event.id})" class="event_edit_btn">
            <i data-lucide="edit" class="event_btn_icon"></i>
          </button>
          <button onclick="deleteEvent(${event.id})" class="event_delete_btn">
            <i data-lucide="trash-2" class="event_btn_icon"></i>
          </button>
        </div>
      </div>
    `;
    eventGrid.appendChild(card);
  });
  lucide.createIcons();
}

// Format date
function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

// Show modal
function showModal() {
  modal.classList.add("active");
}

// Hide modal
function hideModal() {
  modal.classList.remove("active");
  eventForm.reset();
  editingEventId = null;
}

// Add event
function addEvent(e) {
  e.preventDefault();
  const newEvent = {
    id: editingEventId || Date.now(),
    title: document.getElementById("event_title").value,
    description: document.getElementById("event_description").value,
    image: document.getElementById("event_image").value,
    date: document.getElementById("event_date").value,
  };
  if (editingEventId) {
    events = events.map((ev) => (ev.id === editingEventId ? newEvent : ev));
  } else {
    events.push(newEvent);
  }
  hideModal();
  filterEvents(searchInput.value);
}

// Edit event
window.editEvent = (id) => {
  const event = events.find((e) => e.id === id);
  if (!event) return;
  editingEventId = id;
  modalTitle.textContent = "Edit Event";
  document.getElementById("event_title").value = event.title;
  document.getElementById("event_description").value = event.description;
  document.getElementById("event_image").value = event.image;
  document.getElementById("event_date").value = event.date;
  showModal();
};

// Delete event
window.deleteEvent = (id) => {
  if (confirm("Are you sure you want to delete this event?")) {
    events = events.filter((event) => event.id !== id);
    filterEvents(searchInput.value);
  }
};

// Event listeners
searchInput.addEventListener("input", (e) => filterEvents(e.target.value));
addButton.addEventListener("click", () => {
  modalTitle.textContent = "Add New Event";
  showModal();
});
closeModal.addEventListener("click", hideModal);
cancelButton.addEventListener("click", hideModal);
eventForm.addEventListener("submit", addEvent);