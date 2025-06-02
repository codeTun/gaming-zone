(function() {
  'use strict';
  
  console.log('📝 EventManager script loading...');

  class EventManager {
    constructor() {
      this.events = [];
      this.initialized = false;
      this.currentEditId = null;
      this.apiBase = 'http://localhost/gaming-zone/api';
      console.log('🏗️ EventManager instance created');
    }

    async init() {
      if (this.initialized) {
        console.log('🔄 EventManager already initialized, refreshing data...');
        await this.loadEventsFromAPI();
        return;
      }
      
      console.log('🚀 Initializing EventManager with database API integration');
      
      try {
        await this.loadEventsFromAPI();
        this.bindEvents();
        this.initialized = true;
        console.log("✅ EventManager initialized with database connection");
      } catch (error) {
        console.error("❌ Failed to initialize EventManager:", error);
        this.showError("Failed to initialize event management: " + error.message);
      }
    }

    async loadEventsFromAPI() {
      console.log('📡 Loading events from database via API...');
      const eventGrid = document.getElementById('event_grid');
      
      try {
        // Show loading state
        if (eventGrid) {
          eventGrid.innerHTML = '<div class="event_loading">🔄 Loading events from database...</div>';
        }

        // Fetch events from API
        const apiUrl = `${this.apiBase}/events.php`;
        console.log('📡 Making API request to:', apiUrl);
        
        const response = await fetch(apiUrl, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          }
        });
        
        console.log('📥 API Response status:', response.status);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📥 Raw API response:', data);
        
        if (Array.isArray(data)) {
          this.events = data;
          console.log(`✅ Successfully loaded ${this.events.length} events from database`);
        } else if (data && data.error) {
          console.error('❌ API Error:', data.error);
          this.events = [];
          throw new Error(data.error);
        } else {
          console.warn('⚠️ Unexpected API response format:', data);
          this.events = [];
          throw new Error('Unexpected response format from API');
        }
        
        this.renderEventsFromDatabase();
        
      } catch (error) {
        console.error("❌ Failed to load events from database:", error);
        this.events = [];
        if (eventGrid) {
          eventGrid.innerHTML = `<div class="event_error">❌ Failed to load events: ${error.message}</div>`;
        }
        this.showError("Failed to load events from database: " + error.message);
      }
    }

    renderEventsFromDatabase() {
      const eventGrid = document.getElementById('event_grid');
      if (!eventGrid) {
        console.error("❌ Event grid not found!");
        return;
      }

      console.log(`🎨 Rendering ${this.events.length} events from database`);

      if (this.events.length === 0) {
        eventGrid.innerHTML = '<div class="event_no_data">📭 No events found in database</div>';
        return;
      }

      const eventsHTML = this.events.map(event => `
        <div class="event_card" data-event-id="${event.id}">
          <div class="event_image">
            <img src="${event.imageUrl || 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400'}" 
                 alt="${event.title}" 
                 onerror="this.src='https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400'">
          </div>
          <div class="event_content">
            <h3 class="event_title">${event.title}</h3>
            <p class="event_description">${event.description || 'No description available'}</p>
            <div class="event_details">
              <div class="event_detail">
                <i data-lucide="map-pin"></i>
                <span>${event.place}</span>
              </div>
              <div class="event_detail">
                <i data-lucide="calendar"></i>
                <span>${new Date(event.eventDate).toLocaleDateString('en-US', { 
                  year: 'numeric', 
                  month: 'short', 
                  day: 'numeric' 
                })}</span>
              </div>
              <div class="event_detail">
                <i data-lucide="clock"></i>
                <span>${event.eventTime || '10:00'}</span>
              </div>
            </div>
            <div class="event_actions">
              <button class="event_edit_btn" onclick="eventManager.editEvent('${event.id}')">
                <i data-lucide="edit-2"></i>
                Edit
              </button>
              <button class="event_delete_btn" onclick="eventManager.deleteEvent('${event.id}')">
                <i data-lucide="trash-2"></i>
                Delete
              </button>
            </div>
          </div>
        </div>
      `).join('');

      eventGrid.innerHTML = eventsHTML;
      
      // Recreate Lucide icons for the new content
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
      
      console.log(`✅ Successfully rendered ${this.events.length} events in grid`);
    }

    bindEvents() {
      const addBtn = document.getElementById('event_add_btn');
      const closeBtn = document.getElementById('event_close_modal');
      const cancelBtn = document.getElementById('event_cancel_btn');
      const form = document.getElementById('event_form');
      const modal = document.getElementById('event_modal');
      const searchInput = document.getElementById('event_search_input');

      if (addBtn) {
        addBtn.onclick = () => this.openEventModal();
      }

      if (closeBtn) closeBtn.onclick = () => this.closeEventModal();
      if (cancelBtn) cancelBtn.onclick = () => this.closeEventModal();

      if (form) {
        form.onsubmit = (e) => this.handleEventSubmit(e);
      }

      if (modal) {
        modal.onclick = (e) => {
          if (e.target === modal) this.closeEventModal();
        };
      }

      if (searchInput) {
        searchInput.addEventListener('input', (e) => this.searchEvents(e.target.value));
      }
    }

    openEventModal(event = null) {
      const modal = document.getElementById("event_modal");
      const title = document.getElementById("event_modal_title");
      const form = document.getElementById("event_form");

      if (!modal || !title || !form) {
        console.error("Event modal elements not found!");
        return;
      }

      if (event) {
        this.currentEditId = event.id;
        title.textContent = "Edit Event";
        this.populateEventForm(event);
      } else {
        this.currentEditId = null;
        title.textContent = "Add New Event";
        form.reset();
      }

      modal.style.display = "flex";
    }

    closeEventModal() {
      const modal = document.getElementById("event_modal");
      if (modal) modal.style.display = "none";
      this.currentEditId = null;
    }

    async handleEventSubmit(e) {
      e.preventDefault();
      
      try {
        const eventData = {
          title: document.getElementById('event_title').value.trim(),
          description: document.getElementById('event_description').value.trim(),
          imageUrl: document.getElementById('event_image').value.trim(),
          place: document.getElementById('event_place').value.trim(),
          eventDate: document.getElementById('event_date').value,
          eventTime: document.getElementById('event_time').value
        };
        
        if (!eventData.title || !eventData.place || !eventData.eventDate) {
          throw new Error('Please fill in all required fields');
        }

        let result;
        if (this.currentEditId) {
          result = await fetch(`${this.apiBase}/events.php?id=${this.currentEditId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(eventData)
          });
        } else {
          result = await fetch(`${this.apiBase}/events.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(eventData)
          });
        }
        
        const response = await result.json();
        
        if (response.success) {
          this.showSuccess(this.currentEditId ? 'Event updated successfully' : 'Event created successfully');
          this.closeEventModal();
          await this.loadEventsFromAPI();
        } else {
          throw new Error(response.error || 'Failed to save event');
        }

      } catch (error) {
        console.error('❌ Failed to save event:', error);
        this.showError('Failed to save event: ' + error.message);
      }
    }

    populateEventForm(event) {
      document.getElementById("event_title").value = event.title || "";
      document.getElementById("event_description").value = event.description || "";
      document.getElementById("event_image").value = event.imageUrl || "";
      document.getElementById("event_place").value = event.place || "";
      document.getElementById("event_date").value = event.eventDate || "";
      document.getElementById("event_time").value = event.eventTime || "10:00";
    }

    async editEvent(eventId) {
      const event = this.events.find(e => e.id === eventId);
      if (event) {
        this.openEventModal(event);
      }
    }

    async deleteEvent(eventId) {
      if (!confirm('Are you sure you want to delete this event?')) {
        return;
      }

      try {
        const response = await fetch(`${this.apiBase}/events.php?id=${eventId}`, {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
          }
        });

        const result = await response.json();
        
        if (result.success) {
          this.showSuccess('Event deleted successfully');
          await this.loadEventsFromAPI();
        } else {
          throw new Error(result.error || 'Failed to delete event');
        }
      } catch (error) {
        console.error('❌ Failed to delete event:', error);
        this.showError('Failed to delete event: ' + error.message);
      }
    }

    searchEvents(query) {
      const eventCards = document.querySelectorAll('.event_card');
      eventCards.forEach(card => {
        const title = card.querySelector('.event_title').textContent.toLowerCase();
        const description = card.querySelector('.event_description').textContent.toLowerCase();
        const isVisible = title.includes(query.toLowerCase()) || description.includes(query.toLowerCase());
        card.style.display = isVisible ? 'block' : 'none';
      });
    }

    showSuccess(message) {
      this.showNotification(message, "success");
    }

    showError(message) {
      this.showNotification(message, "error");
    }

    showNotification(message, type) {
      const existing = document.querySelector(".event-notification");
      if (existing) existing.remove();

      const notification = document.createElement("div");
      notification.className = `event-notification event-notification-${type}`;
      notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 8px;">
          <span>${type === 'success' ? '✅' : '❌'}</span>
          <span>${message}</span>
        </div>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 18px;">×</button>
      `;

      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === "success" ? "#22c55e" : "#ef4444"};
        color: white;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-weight: 600;
        max-width: 400px;
        animation: slideInRight 0.3s ease;
      `;

      document.body.appendChild(notification);
      setTimeout(() => {
        if (notification.parentElement) {
          notification.remove();
        }
      }, 5000);
    }
  }

  // Make EventManager available globally
  window.EventManager = EventManager;
  console.log('✅ EventManager class attached to window');

  // Initialize when DOM is ready
  document.addEventListener("DOMContentLoaded", () => {
    console.log("🚀 EventManager DOM ready - Creating instance");
    
    if (!window.eventManager) {
      window.eventManager = new EventManager();
      console.log("✅ EventManager instance created and attached to window");
    }
  });

  console.log('📝 EventManager script loaded successfully');
})();