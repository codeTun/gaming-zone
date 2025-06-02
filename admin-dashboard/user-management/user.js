(function() {
  'use strict';
  
  console.log('📝 UserManager script loading...');

  class UserManager {
    constructor() {
      this.users = [];
      this.initialized = false;
      this.apiBase = 'http://localhost/gaming-zone/api';
      console.log('🏗️ UserManager instance created');
    }

    async init() {
      console.log('🚀 UserManager.init() called');
      
      if (this.initialized) {
        console.log('🔄 UserManager already initialized, refreshing data...');
        await this.loadUsersFromAPI();
        return;
      }
      
      console.log('🚀 Initializing UserManager with database API integration');
      
      try {
        await this.loadUsersFromAPI();
        this.initialized = true;
        console.log("✅ UserManager initialized with database connection");
      } catch (error) {
        console.error("❌ Failed to initialize UserManager:", error);
        this.showError("Failed to initialize user management: " + error.message);
      }
    }

    async loadUsersFromAPI() {
      console.log('📡 Loading users from database via API...');
      const tableBody = document.getElementById('users-table-body');
      
      try {
        // Show loading state
        if (tableBody) {
          tableBody.innerHTML = '<tr><td colspan="5" class="user_loading">🔄 Loading users from database...</td></tr>';
        }

        // Fetch users from API
        const apiUrl = `${this.apiBase}/users.php`;
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
          this.users = data;
          console.log(`✅ Successfully loaded ${this.users.length} users from database`);
        } else if (data && data.error) {
          console.error('❌ API Error:', data.error);
          this.users = [];
          throw new Error(data.error);
        } else {
          console.warn('⚠️ Unexpected API response format:', data);
          this.users = [];
          throw new Error('Unexpected response format from API');
        }
        
        this.renderUsersFromDatabase();
        this.updateStats();
        
      } catch (error) {
        console.error("❌ Failed to load users from database:", error);
        this.users = [];
        if (tableBody) {
          tableBody.innerHTML = `<tr><td colspan="5" class="user_error">❌ Failed to load users: ${error.message}</td></tr>`;
        }
        this.showError("Failed to load users from database: " + error.message);
        this.updateStats();
      }
    }

    renderUsersFromDatabase() {
      const tableBody = document.getElementById('users-table-body');
      if (!tableBody) {
        console.error("❌ Users table body not found!");
        return;
      }

      console.log(`🎨 Rendering ${this.users.length} users from database`);

      if (this.users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" class="user_no_data">📭 No users found in database</td></tr>';
        return;
      }

      const usersHTML = this.users.map(user => `
        <tr data-user-id="${user.id}">
          <td>
            <div class="user_id_cell" title="${user.id}">${user.id.substring(0, 8)}...</div>
          </td>
          <td>
            <div class="user_name_cell">
              <div class="user_avatar">
                <img src="${user.imageUrl || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100'}" 
                     alt="${user.username}" class="user_thumb" 
                     onerror="this.src='https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100'">
              </div>
              <div>
                <div class="user_username">${user.username}</div>
                <div class="user_fullname">${user.name}</div>
              </div>
            </div>
          </td>
          <td>
            <div class="user_email">${user.email}</div>
          </td>
          <td>
            <span class="user_role_badge ${user.role.toLowerCase()}">${user.role}</span>
          </td>
          <td>
            <span class="user_date">${new Date(user.createdAt).toLocaleDateString('en-US', { 
              year: 'numeric', 
              month: 'short', 
              day: 'numeric' 
            })}</span>
          </td>
        </tr>
      `).join('');

      tableBody.innerHTML = usersHTML;
      console.log(`✅ Successfully rendered ${this.users.length} users in table`);
    }

    updateStats() {
      const totalUsers = this.users.length;
      const adminUsers = this.users.filter(user => user.role === 'ADMIN').length;
      const regularUsers = this.users.filter(user => user.role === 'USER').length;

      console.log(`📊 Updating stats - Total: ${totalUsers}, Admin: ${adminUsers}, Regular: ${regularUsers}`);

      // Update total users in the user management section
      const usersTotalCount = document.getElementById('users-total-count');
      if (usersTotalCount) {
        usersTotalCount.textContent = totalUsers;
      }

      // Update admin users count
      const adminUserElement = document.getElementById('admin-users');
      if (adminUserElement) {
        adminUserElement.textContent = adminUsers;
      }

      // Update regular users count
      const regularUserElement = document.getElementById('regular-users');
      if (regularUserElement) {
        regularUserElement.textContent = regularUsers;
      }
    }

    showSuccess(message) {
      this.showNotification(message, "success");
    }

    showError(message) {
      this.showNotification(message, "error");
    }

    showNotification(message, type) {
      const existing = document.querySelector(".user-notification");
      if (existing) existing.remove();

      const notification = document.createElement("div");
      notification.className = `user-notification user-notification-${type}`;
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

  // Make UserManager available globally immediately
  window.UserManager = UserManager;
  console.log('✅ UserManager class attached to window');

  // Initialize when DOM is ready
  document.addEventListener("DOMContentLoaded", () => {
    console.log("🚀 UserManager DOM ready - Creating instance");
    
    // Create global instance
    if (!window.userManager) {
      window.userManager = new UserManager();
      console.log("✅ UserManager instance created and attached to window");
    }
  });

  console.log('📝 UserManager script loaded successfully');
})();
