// profile.js
import { apiRequest, getAuthToken, clearAuthTokenCache } from "./module/api.js?v=20260524-api2";
import { setInitialDarkMode } from "./module/darkMode.js";
import { API_ENDPOINTS } from "./module/config/endpoints.js";
import { APP_ROUTES } from "./module/config/routes.js";

const API_URL = API_ENDPOINTS.profile;
const PROFILE_SETTING_PAGE = APP_ROUTES.profileSetting;
const LOGIN_PAGE = APP_ROUTES.login;

const FIELD_MAP = {
  FullName: ["FullName", "fullName", "name"],
  email: ["email", "Email"],
  firstName: ["firstName", "FirstName", "givenName"],
  lastName: ["lastName", "LastName", "surname", "Surname"],
  mobileNumber: ["mobileNumber", "contactNumber", "phone", "Phone"],
  faxNumber: ["faxNumber", "fax", "Fax"],
  addressLine1: ["addressLine1", "address", "deliveryAddress"],
  addressLine2: ["addressLine2"],
  district: ["district"],
  city: ["city"],
  stateProvince: ["stateProvince", "state", "province"],
  country: ["country"],
};

function $(id) {
  return document.getElementById(id);
}

function firstValue(data, keys, fallback = "") {
  for (const key of keys) {
    const value = data?.[key];

    if (value !== undefined && value !== null && String(value).trim() !== "") {
      return String(value).trim();
    }
  }

  return fallback;
}

function buildLoginLinkMessage(message) {
  const errorBox = $("profileError");

  if (!errorBox) {
    return;
  }

  errorBox.replaceChildren();

  const text = document.createElement("span");
  text.textContent = `${message} `;

  const link = document.createElement("a");
  link.href = LOGIN_PAGE;
  link.className =
    "font-semibold underline underline-offset-2 hover:text-red-900 dark:hover:text-red-100";
  link.textContent = "Go to login";

  errorBox.append(text, link);
}

function clearAuthState() {
  clearAuthTokenCache();
}

function setElementVisible(element, isVisible) {
  if (!element) {
    return;
  }

  element.hidden = !isVisible;
  element.classList.toggle("hidden", !isVisible);
  element.style.display = isVisible ? "" : "none";
}

function setText(id, value, fallback = "N/A") {
  const element = $(id);

  if (!element) {
    return;
  }

  const safeValue =
    value === undefined || value === null || String(value).trim() === ""
      ? fallback
      : String(value).trim();

  element.textContent = safeValue;
}

function splitFullName(fullName) {
  const parts = String(fullName || "")
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  return {
    firstName: parts[0] || "",
    lastName: parts.slice(1).join(" "),
  };
}

function normalizeProfileData(data = {}) {
  const fullName = firstValue(data, FIELD_MAP.FullName);
  const splitName = splitFullName(fullName);
  const firstName = firstValue(data, FIELD_MAP.firstName, splitName.firstName);
  const lastName = firstValue(data, FIELD_MAP.lastName, splitName.lastName);

  return {
    FullName: fullName || `${firstName} ${lastName}`.trim(),
    email: firstValue(data, FIELD_MAP.email),
    firstName,
    lastName,
    mobileNumber: firstValue(data, FIELD_MAP.mobileNumber),
    faxNumber: firstValue(data, FIELD_MAP.faxNumber),
    addressLine1: firstValue(data, FIELD_MAP.addressLine1),
    addressLine2: firstValue(data, FIELD_MAP.addressLine2),
    district: firstValue(data, FIELD_MAP.district),
    city: firstValue(data, FIELD_MAP.city),
    stateProvince: firstValue(data, FIELD_MAP.stateProvince),
    country: firstValue(data, FIELD_MAP.country),
  };
}

function setProfileState(state, message = "") {
  setElementVisible($("profileLoading"), state === "loading");
  setElementVisible($("profileError"), state === "error");
  setElementVisible($("profileContent"), state === "ready");

  const errorBox = $("profileError");

  if (errorBox && message) {
    errorBox.textContent = message;
  }
}

function showProfileMessage(type, message) {
  const existingMessage = $("profileMessage");
  const profileContent = $("profileContent") || document.querySelector("main");
  const messageBox = existingMessage || document.createElement("div");

  if (!profileContent) {
    return;
  }

  messageBox.id = "profileMessage";
  messageBox.className =
    type === "success"
      ? "mb-4 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
      : "mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300";
  messageBox.textContent = message;

  if (!existingMessage) {
    profileContent.prepend(messageBox);
  }
}

function renderAddress(profile) {
  const addressParts = [
    profile.addressLine1,
    profile.addressLine2,
    profile.district,
    profile.city,
    profile.stateProvince,
    profile.country,
  ].filter(Boolean);

  const addressText = addressParts.join(", ") || "N/A";
  const addressLine1Element = $("addressLine1");
  const addressLine2Element = $("addressLine2");

  if (addressLine1Element && addressLine2Element) {
    addressLine1Element.textContent = addressText;
    addressLine2Element.textContent = profile.country || "N/A";
    return;
  }

  setText("addressLine1", addressText);
}

export async function getProfileData() {
  const token = getAuthToken();

  if (!token) {
    const error = new Error("No token provided. Please log in again.");
    error.status = 401;
    throw error;
  }

  const response = await apiRequest(API_URL, "get", {}, "GET");
  return (
    response?.data ||
    response?.result?.data ||
    response?.result ||
    response ||
    {}
  );
}

export async function updateProfile(profileData) {
  return apiRequest(API_URL, "edit", profileData);
}

export async function updatePassword(passwordData) {
  return apiRequest(API_URL, "update_password", passwordData);
}

export function populateProfileFields(data = {}) {
  const profile = normalizeProfileData(data);

  setText("FullName", profile.FullName);
  setText("email", profile.email);
  setText("firstName", profile.firstName);
  setText("lastName", profile.lastName);
  setText("mobileNumber", profile.mobileNumber);
  setText("faxNumber", profile.faxNumber);
  setText("country", profile.country);
  renderAddress(profile);
}

export async function initializeProfile() {
  setInitialDarkMode();
  setProfileState("loading");

  try {
    const data = await getProfileData();
    populateProfileFields(data);
    setProfileState("ready");
  } catch (error) {
    console.error("Error initializing profile:", error);

    const isUnauthorized =
      error?.status === 401 ||
      String(error?.message || "")
        .toLowerCase()
        .includes("unauthorized");
    const message = isUnauthorized
      ? "Your session has expired. Please log in again."
      : "Failed to load profile data. Please refresh the page or try again later.";

    setProfileState("error", message);
    showProfileMessage("error", message);

    if (isUnauthorized) {
      clearAuthState();
      buildLoginLinkMessage(message);
    }

    throw error;
  }
}

export function setupEditButton() {
  const editButton =
    $("editProfileButton") || $("edit-profile-button") || $("editProfile");

  if (editButton) {
    editButton.addEventListener("click", () => {
      window.location.href = PROFILE_SETTING_PAGE;
    });
  }

}

export function bootProfilePage() {
  if (document.readyState === "loading") {
    document.addEventListener(
      "DOMContentLoaded",
      async () => {
        await initializeProfile();
        setupEditButton();
      },
      { once: true },
    );
    return;
  }

  initializeProfile()
    .then(setupEditButton)
    .catch(() => {});
}
