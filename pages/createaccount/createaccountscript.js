function validatePasswords() {
  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirmPassword").value;

  if (password !== confirmPassword) {
    alert("Passwords do not match. Please try again.");
    return false;
  }

  return true;
}

function redirectToLogin() {
  window.location.href = "/";
}
