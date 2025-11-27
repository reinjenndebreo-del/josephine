document.addEventListener("DOMContentLoaded", () => {
  const token = localStorage.getItem("token");
  const isAdmin = localStorage.getItem("isAdmin"); // "true" or "false"

  // If no token → force login
  if (!token) {
    window.location.href = "/views/auth/login.html";
    return;
  }

  // If this is the admin guard, check admin rights
  if (window.location.pathname.includes("/admin/")) {
    if (isAdmin !== "true" || isAdmin === null) {
      // Not an admin, boot to user home
      window.location.href = "/views/user/home.html";
    }
  }

  // If this is the user guard (optional)
  if (window.location.pathname.includes("/user/")) {
    if (isAdmin === "true") {
      // Admins shouldn’t be here → go to dashboard
      window.location.href = "/views/admin/dashboard.html";
    }
  }
});
