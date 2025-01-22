// Sample user data
const users = [
  {
    id: 1,
    username: "john_doe",
    email: "john@example.com",
    status: "active",
    joinDate: "2024-01-15",
  },
  {
    id: 2,
    username: "jane_smith",
    email: "jane@example.com",
    status: "banned",
    joinDate: "2024-02-01",
  },
  {
    id: 3,
    username: "mike_wilson",
    email: "mike@example.com",
    status: "active",
    joinDate: "2024-02-15",
  },
];

// Initialize counters
let totalUsers = users.length;
let bannedUsers = users.filter((user) => user.status === "banned").length;

// Function to update counters
function updateCounter(elementId, value) {
  const element = document.getElementById(elementId);
  if (element) {
    element.classList.add("counter-update");
    element.textContent = value;

    // Remove animation class after animation completes
    setTimeout(() => {
      element.classList.remove("counter-update");
    }, 500);
  }
}

// Function to update user list
function updateUsersList() {
  const tableBody = document.getElementById("users-table-body");
  if (tableBody) {
    tableBody.innerHTML = "";

    users.forEach((user, index) => {
      const row = document.createElement("tr");
      row.innerHTML = `
          <td>${user.id}</td>
          <td>${user.username}</td>
          <td>${user.email}</td>
          <td>
            <span class="status-badge ${user.status}">
              ${user.status === "active" ? "✅ active" : "❌ banned"}
            </span>
          </td>
          <td>📅 ${user.joinDate}</td>
          <td class="actions">
            <button onclick="toggleBanUser(${user.id})" class="btn ${
        user.status === "banned" ? "unban" : "ban"
      }">
              ${user.status === "banned" ? "🔓 Unban" : "🔒 Ban"}
            </button>
            <button onclick="deleteUser(${user.id})" class="btn delete">
              🗑️ Delete
            </button>
          </td>
        `;
      tableBody.appendChild(row);
    });
  }
}

// Function to toggle ban status
window.toggleBanUser = (userId) => {
  const user = users.find((u) => u.id === userId);
  if (user) {
    user.status = user.status === "banned" ? "active" : "banned";
    bannedUsers = users.filter((user) => user.status === "banned").length;
    updateCounter("banned-users", bannedUsers);
    updateUsersList();
  }
};

// Function to delete user
window.deleteUser = (userId) => {
  const index = users.findIndex((u) => u.id === userId);
  if (index !== -1) {
    const tableBody = document.getElementById("users-table-body");
    if (tableBody) {
      const row = tableBody.querySelector(`tr:nth-child(${index + 1})`);
      if (row) {
        row.classList.add("deleted-row");

        setTimeout(() => {
          users.splice(index, 1);
          totalUsers = users.length;
          bannedUsers = users.filter((user) => user.status === "banned").length;
          updateCounter("total-users", totalUsers);
          updateCounter("banned-users", bannedUsers);
          updateUsersList();
        }, 500); // Duration should match CSS transition
      }
    }
  }
};

// Initialize counters and user list on page load
document.addEventListener("DOMContentLoaded", () => {
  updateCounter("total-users", totalUsers);
  updateCounter("banned-users", bannedUsers);
  updateUsersList();
});
