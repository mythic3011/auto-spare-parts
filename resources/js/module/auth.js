// auth.js
import { apiRequest, getCookie } from "./api.js";
import { API_ENDPOINTS, buildApiUrl } from "./config/endpoints.js";

export function isAuthenticated() {
  const token =
    localStorage.getItem("token") ||
    localStorage.getItem("userToken") ||
    sessionStorage.getItem("token") ||
    sessionStorage.getItem("userToken") ||
    getCookie("token") ||
    getCookie("userToken");
  return !!token;
}

export function logout() {
  localStorage.removeItem("token");
  localStorage.removeItem("userToken");
  sessionStorage.removeItem("token");
  sessionStorage.removeItem("userToken");
  document.cookie = "token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
  document.cookie =
    "userToken=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}

export async function checkToken(token) {
  try {
    const response = await fetch(
      buildApiUrl(API_ENDPOINTS.login, { CheckToken: token }),
      {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
    },
    );
    return await response.json();
  } catch (error) {
    console.error("Error:", error);
    throw error;
  }
}
