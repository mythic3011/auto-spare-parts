import "../auth-session.global.js";
// api.js - shared API request helpers

export function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);

  if (parts.length !== 2) {
    return "";
  }

  return decodeURIComponent(parts.pop().split(";").shift() || "");
}

export function getAuthToken() {
  return window.SLMC_AUTH.getToken();
}

export function getCsrfToken() {
  return getCookie("csrf_token") || "";
}

export function clearAuthTokenCache() {
  window.SLMC_AUTH.clear();
}

function buildHeaders() {
  const headers = {
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
  };

  const csrfToken = getCsrfToken();
  const token = getAuthToken();

  if (csrfToken) {
    headers["X-CSRF-Token"] = csrfToken;
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  return headers;
}

export async function parseJsonResponse(response) {
  const text = await response.text();

  if (!text) {
    return {};
  }

  try {
    return JSON.parse(text);
  } catch (error) {
    throw new Error("API returned invalid JSON");
  }
}

function extractErrorMessage(payload, fallback) {
  return (
    payload?.message ||
    payload?.error ||
    payload?.result?.message ||
    payload?.result?.error ||
    fallback
  );
}

export async function apiRequest(API_URL, action, data = {}, method = "POST") {
  const csrfToken = getCsrfToken();
  const url = `${API_URL}?action=${encodeURIComponent(action)}`;

  const response = await fetch(url, {
    method,
    credentials: "same-origin",
    headers: buildHeaders(),
    ...(method === "GET" ? {} : {
      body: JSON.stringify({ ...data, csrf_token: csrfToken }),
    }),
  });

  const payload = await parseJsonResponse(response);

  if (!response.ok) {
    const message =
      response.status === 401
        ? "Unauthorized. Please log in again."
        : extractErrorMessage(
            payload,
            `API request failed. HTTP ${response.status}`,
          );

    const error = new Error(message);
    error.status = response.status;
    error.payload = payload;
    throw error;
  }

  if (payload?.status === "error" || payload?.result?.error) {
    throw new Error(extractErrorMessage(payload, "API request failed"));
  }

  return payload;
}
