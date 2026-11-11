import { API_ENDPOINTS } from "./module/config/endpoints.js";

const LOGIN_API_URL = API_ENDPOINTS.login;

document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("loginForm");
  const loginButton = document.getElementById("loginButton");
  const loginMessage = document.getElementById("loginMessage");
  const rememberMe = document.getElementById("remember-me");
  const usernameInput = document.getElementById("username");
  const darkModeToggle = document.getElementById("darkModeToggle");
  const html = document.documentElement;

  if (!loginForm) console.error("Login form not found");
  if (!loginButton) console.error("Login button not found");
  if (!loginMessage) console.error("Login message element not found");
  if (!rememberMe) console.error("Remember me checkbox not found");
  if (!usernameInput) console.error("Username input not found");

  // Initialize Turnstile
  const turnstileWidget = document.getElementById("turnstile-widget");
  if (turnstileWidget) {
    turnstile.render("#turnstile-widget", {
      sitekey: "0x4AAAAAAAdmnv0tiJzz_0nV",
      callback: function (token) {
        document.getElementById("turnstileResponse").value = token;
      },
      "error-callback": function () {
        const turnstileError = document.getElementById("turnstile-error");
        if (turnstileError) {
          turnstileError.textContent =
            "Turnstile widget error. Please try again or contact support.";
          turnstileError.classList.remove("hidden");
        }
      },
    });
  } else {
    console.log(
      "Turnstile widget not found. Likely in development environment.",
    );
  }

  // Check for saved theme preference or use system preference
  if (
    localStorage.theme === "dark" ||
    (!("theme" in localStorage) &&
      window.matchMedia("(prefers-color-scheme: dark)").matches)
  ) {
    html.classList.add("dark");
  } else {
    html.classList.remove("dark");
  }

  // Toggle dark mode
  darkModeToggle.addEventListener("click", function () {
    html.classList.toggle("dark");

    // Save preference to localStorage
    if (html.classList.contains("dark")) {
      localStorage.theme = "dark";
    } else {
      localStorage.theme = "light";
    }
  });

  // Load remembered username if exists
  const rememberedUsername = localStorage.getItem("username");
  if (rememberedUsername && usernameInput) {
    usernameInput.value = rememberedUsername;
    if (rememberMe) rememberMe.checked = true;
  }

  if (loginForm && loginButton && loginMessage) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();
      loginButton.disabled = true;
      loginButton.textContent = "Logging in...";

      const formData = new FormData(loginForm);

      fetch(LOGIN_API_URL, {
        method: "POST",
        body: formData,
        credentials: "include",
      })
        .then((response) => response.json())
        .then((data) => {
          const result = data?.result?.result;
          const errorMessage =
            data?.result?.error || data?.error || "Login failed. Please try again.";
          if (!result?.token) {
            throw new Error(errorMessage);
          }

          loginMessage.textContent = "Login successful!";
          loginMessage.style.color = "green";

          sessionStorage.setItem("token", result.token);
          sessionStorage.setItem("userToken", result.token);

          if (rememberMe && rememberMe.checked) {
            localStorage.setItem("token", result.token);
            localStorage.setItem("userToken", result.token);
            localStorage.setItem("remember-me", "true");
            localStorage.setItem("username", result.username);
            loginMessage.textContent =
              "Login successful! Remember me is enabled.";
          } else {
            localStorage.removeItem("token");
            localStorage.removeItem("userToken");
            localStorage.removeItem("remember-me");
            localStorage.removeItem("username");
          }

          const nextPage = result.page;
          if (!nextPage) {
            throw new Error("Login succeeded but no destination was returned.");
          }
          window.setTimeout(() => {
            window.location.href = nextPage;
          }, 1000);
        })
        .catch((error) => {
          console.error("Login error:", error);
          loginMessage.textContent =
            error.message || "An error occurred. Please try again.";
          loginMessage.style.color = "red";
          loginButton.disabled = false;
          loginButton.textContent = "Login";
        });
    });
  } else {
    console.error("One or more required elements are missing from the page.");
  }
});
