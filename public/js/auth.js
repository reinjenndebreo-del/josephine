//reusable  SweetAlert2 for better alert messages

const alertMessage = (type, title, text, timer = 1500) => {
  Swal.fire({
    icon: type,
    title: title,
    text: text,
    timer: timer,
    allowOutsideClick: false,
    showConfirmButton: false,
    showCancelButton: false,
  });
};
const successMessage = async (title, text, timer = 1500) => {
  Swal.fire({
    icon: "success",
    title: title,
    text: text,
    timer: timer,
    allowOutsideClick: false,
    showConfirmButton: false,
    showCancelButton: false,
  });
};

// domcontentLoaded for login
document.addEventListener("DOMContentLoaded", () => {
  document
    .getElementById("loginForm")
    ?.addEventListener("submit", async (e) => {
      e.preventDefault();

      const username = document.getElementById("username").value.trim();
      const password = document.getElementById("password").value.trim();

      if (!username || !password) {
        alertMessage(
          "error",
          "Validation Error",
          "Please fill in all required fields.",
          1200
        );
        e.target.reset();
        return;
      }

      try {
        const response = await fetch("/api/auth/login.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify({ username, password }),
        });

        // parse to raw text and parse it to json
        const text = await response.text();
        const data = JSON.parse(text);

        if (!response.ok) {
          alertMessage(
            "error",
            "Login Failed",
            data.message || "Something went wrong.",
            2000
          );
          return;
        }
        console.log(data);

        if (data.isAdmin) {
          // Success
          successMessage("Login Successful", data.message, 2000);
          localStorage.setItem("token", data.token);
          localStorage.setItem("isAdmin", data.isAdmin);
          localStorage.setItem("username", data.username);
          setTimeout(() => {
            window.location.href = "/views/admin/dashboard.html";
          }, 2001);
          return;
        }
        // Success
        successMessage(
          "Login Successful",
          data.message || "You have successfully logged in.",
          2000
        );
        localStorage.setItem("token", data.token);
        localStorage.setItem("username", data.username);
        localStorage.setItem("isAdmin", "false");
        // Redirect to dashboard after a short delay
        setTimeout(() => {
          window.location.href = "/views/user/home.html";
        }, 2001);
      } catch (error) {
        alertMessage("error", "Login Failed", error.message, 2000);
      }
    });
});

// domcontentLoaded for register
document.addEventListener("DOMContentLoaded", () => {
  // get the id of the form and validate the form (option chaining)
  document
    .getElementById("registerForm")
    ?.addEventListener("submit", async (e) => {
      e.preventDefault();
      // get the username and password values
      const username = document.getElementById("username").value;
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;

      // form validation
      if (!username || !password || !email) {
        alertMessage(
          "error",
          "Validation Error",
          "Please fill in all required fields.",
          1200
        );
        return;
      }

      try {
        const response = await fetch("/api/register/register.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify({ username, email, password }),
        });

        // parse to raw text and parse it to json
        const text = await response.text();

        const data = JSON.parse(text);

        if (!response.ok) {
          alertMessage("error", "Registration Failed", data.message, 2000);
          e.target.reset();
          return;
        }

        alertMessage(
          "success",
          "Registration Successful",
          data.message || "You have successfully registered.",
          2000
        );

        localStorage.setItem("token", data.token);
        localStorage.setItem("username", data.username);
        localStorage.setItem("isAdmin", "false");
        // Redirect to dashboard after a short delay
        setTimeout(() => {
          window.location.href = "/views/user/home.html";
        }, 2001);
      } catch (error) {
        alertMessage("error", "Login Failed", error.message, 2000);
      }
    });
});
