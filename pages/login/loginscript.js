function validateLogin() {
  const username = document.getElementById("username").value;
  const password = document.getElementById("password").value;

  if (username === "gamingzone" && password === "gamingzone") {
    alert("User is connected");
    window.location.href = "../../user-interface/main.html";
  } else if (username === "admin" && password === "admin") {
    window.location.href = "../../admin-dashboard/index.html";
  } else {
    alert("Invalid username or password. Please try again.");
  }
}
