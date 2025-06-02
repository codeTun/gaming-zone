class EventManager {
  constructor() {
    this.events = [];
    this.currentEditId = null;
    this.initialized = false;
  }

  async init() {
    if (this.initialized) return;
    this.bindEvents();
    this.initialized = true;
    lucide.createIcons();
    console.log("EventManager initialized");
  }

  bindEvents() {
    const addBtn = document.getElementById("event_add_btn");
    const modal = document.getElementById("event_modal");
    const closeBtn = document.getElementById("event_close_modal");
    const cancelBtn = document.getElementById("event_cancel_btn");
    const searchInput = document.getElementById("event_search_input");

    if (addBtn) addBtn.addEventListener("click", () => this.openModal());
    if (closeBtn) closeBtn.addEventListener("click", () => this.closeModal());
    if (cancelBtn) cancelBtn.addEventListener("click", () => this.closeModal());
    if (searchInput) {
      searchInput.addEventListener("input", (e) =>
        this.searchEvents(e.target.value)
      );
    }

    if (modal) {
      modal.addEventListener("click", (e) => {
        if (e.target === modal) this.closeModal();
      });
    }
  }

  async loadEvents() {
    console.log("Loading events...");
    try {
      const response = await fetch(
        "http://localhost/gaming-zone/api/events.php"
      );
      const data = await response.json();

      console.log("Events API Response:", data);

      this.events = Array.isArray(data) ? data : [];
      this.renderEvents();
    } catch (error) {
      console.error("Failed to load events:", error);
      this.events = [];
      this.renderEvents();
      this.showError("Could not load events from server");
    }
  }

  renderEvents(eventsToRender = this.events) {
    const grid = document.getElementById("event_grid");
    if (!grid) return;

    console.log("Rendering events:", eventsToRender);

    if (eventsToRender.length === 0) {
      grid.innerHTML = '<div class="event_no_data">No events found</div>';
      return;
    }

    grid.innerHTML = eventsToRender
      .map(
        (event) => `
            <div class="event_card">
                <div class="event_image_container">
                    <img src="${
                      event.imageUrl ||
                      "https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=500"
                    }" alt="${event.name}" class="event_image">
                    <div class="event_type_badge">Event</div>
                </div>
                <div class="event_content">
                    <h3 class="event_title">${event.name}</h3>
                    <p class="event_description">${
                      event.description || "No description"
                    }</p>
                    <div class="event_meta">
                        <div class="event_meta_item event_place">
                            <i data-lucide="map-pin"></i>
                            ${event.place}
                        </div>
                        <div class="event_meta_item event_time">
                            <i data-lucide="clock"></i>
                            ${new Date(event.startDate).toLocaleTimeString("en-US", {
                              hour: "2-digit",
                              minute: "2-digit",
                            })}
                        </div>
                        <div class="event_meta_item event_date">
                            <i data-lucide="calendar"></i>
                            ${new Date(event.startDate).toLocaleDateString("en-US", {
                              weekday: "short",
                              year: "numeric",
                              month: "short",
                              day: "numeric",
                            })}
                        </div>
                    </div>
                    <div class="event_actions">
                        <button class="event_delete_btn" onclick="eventManager.deleteEvent('${
                          event.id
                        }')">
                            <i data-lucide="trash-2"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        `
      )
      .join("");

    lucide.createIcons();
  }

  openModal(event = null) {
    const modal = document.getElementById("event_modal");
    const title = document.getElementById("event_modal_title");

    if (event) {
      this.currentEditId = event.id;
      title.textContent = "Edit Event";
    } else {
      this.currentEditId = null;
      title.textContent = "Add New Event";
    }

    modal.style.display = "flex";
  }

  closeModal() {
    const modal = document.getElementById("event_modal");
    modal.style.display = "none";
    this.currentEditId = null;
  }

  async deleteEvent(id) {
    if (!confirm("Are you sure you want to delete this event?")) return;

    console.log("Deleting event:", id);

    try {
      const response = await fetch(
        `http://localhost/gaming-zone/api/events.php?id=${id}`,
        {
          method: "DELETE",
        }
      );

      const result = await response.json();
      console.log("Delete response:", result);

      if (result.success) {
        this.showSuccess("Event deleted successfully");
        await this.loadEvents();
      } else {
        throw new Error(result.error || "Delete failed");
      }
    } catch (error) {
      console.error("Failed to delete event:", error);
      this.showError("Failed to delete event: " + error.message);
    }
  }

  searchEvents(query) {
    const filtered = this.events.filter(
      (event) =>
        event.name.toLowerCase().includes(query.toLowerCase()) ||
        (event.description &&
          event.description.toLowerCase().includes(query.toLowerCase()))
    );
    this.renderEvents(filtered);
  }

  showSuccess(message) {
    this.showNotification(message, "success");
  }

  showError(message) {
    this.showNotification(message, "error");
  }

  showNotification(message, type) {
    const existing = document.querySelector(".notification");
    if (existing) existing.remove();

    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
            <i data-lucide="${
              type === "success" ? "check-circle" : "x-circle"
            }"></i>
            <span>${message}</span>
        `;

    notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${
              type === "success" ? "#22c55e" : "#ef4444"
            };
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        `;

    document.body.appendChild(notification);
    lucide.createIcons();

    setTimeout(() => {
      notification.remove();
    }, 5000);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  window.eventManager = new EventManager();
});