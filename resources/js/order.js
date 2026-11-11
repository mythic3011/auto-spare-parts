import { getAuthToken } from "./module/api.js?v=20260524-api2";
import { API_ENDPOINTS, buildApiUrl } from "./module/config/endpoints.js";
import { APP_ROUTES, buildPageUrl } from "./module/config/routes.js";
import { escapeHtml } from "./module/html.js";

const ORDER_API_URL = API_ENDPOINTS.order;
const LOGIN_PAGE = APP_ROUTES.login;
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 50;
const VIEW_MODES = new Set(["table", "list", "compact"]);

let currentOrders = [];
let currentViewMode = getInitialViewMode();

function $(id) {
  return document.getElementById(id);
}

function setElementVisible(element, isVisible) {
  if (!element) {
    return;
  }

  element.hidden = !isVisible;
  element.classList.toggle("hidden", !isVisible);
  element.style.display = isVisible ? "" : "none";
}

function setText(id, value) {
  const element = $(id);

  if (element) {
    element.textContent = String(value);
  }
}

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);

  if (parts.length !== 2) {
    return "";
  }

  return decodeURIComponent(parts.pop().split(";").shift() || "");
}

function getInitialViewMode() {
  const params = new URLSearchParams(window.location.search);
  const mode =
    params.get("mode") || localStorage.getItem("orderViewMode") || "table";

  return VIEW_MODES.has(mode) ? mode : "table";
}

function setInitialModeUrl() {
  const params = new URLSearchParams(window.location.search);

  if (params.get("mode") === currentViewMode) {
    return;
  }

  params.set("mode", currentViewMode);
  window.history.replaceState(
    {},
    "",
    `${window.location.pathname}?${params.toString()}`,
  );
}

function getOrderAuthToken() {
  return (
    getAuthToken() ||
    localStorage.getItem("userToken") ||
    localStorage.getItem("token") ||
    sessionStorage.getItem("userToken") ||
    sessionStorage.getItem("token") ||
    getCookie("token") ||
    getCookie("auth_token") ||
    getCookie("jwt") ||
    getCookie("userToken") ||
    ""
  ).trim();
}

function clearStaleOrderAuthCache() {
  ["userToken", "token"].forEach((key) => {
    localStorage.removeItem(key);
    sessionStorage.removeItem(key);
  });
}

function buildOrderApiUrl(page = DEFAULT_PAGE, limit = DEFAULT_LIMIT) {
  return buildApiUrl(ORDER_API_URL, {
    action: "dealer-list",
    page,
    limit,
  });
}

function buildOrderDetailsUrl(orderId) {
  return buildPageUrl(APP_ROUTES.orderDetails, { id: orderId });
}

function normalizeDateTime(value) {
  const rawValue = String(value || "").trim();

  if (!rawValue) {
    return { date: "N/A", time: "N/A", timestamp: 0 };
  }

  const [datePart = "", timePart = ""] = rawValue.split(" ");
  const timestamp = Date.parse(rawValue.replace(" ", "T"));

  return {
    date: formatDate(datePart),
    time: formatTime(timePart),
    timestamp: Number.isFinite(timestamp) ? timestamp : 0,
  };
}

function normalizeMoney(value) {
  const amount = Number(value);

  if (!Number.isFinite(amount)) {
    return 0;
  }

  return amount;
}

function formatMoney(value) {
  return `$${normalizeMoney(value).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

function formatDate(dateString) {
  const value = String(dateString || "").trim();

  if (!value || value === "0000-00-00") {
    return "N/A";
  }

  if (/^\d{2}\/\d{2}\/\d{4}$/.test(value)) {
    return value;
  }

  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    const [year, month, day] = value.split("-");
    return `${day}/${month}/${year}`;
  }

  return value;
}

function formatTime(timeString) {
  const value = String(timeString || "").trim();

  if (!value) {
    return "N/A";
  }

  if (/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
    const [hours, minutes] = value.split(":");
    return `${hours}:${minutes}`;
  }

  return value;
}

function mapOrder(order = {}) {
  const orderDateTime = normalizeDateTime(
    order.orderDateTime || `${order.orderDate || ""} ${order.orderTime || ""}`,
  );
  const deliveryDate = formatDate(order.deliveryDate);
  const totalCost = normalizeMoney(
    order.totalCost || order.totalAmount || order.total || 0,
  );
  const id = String(order.orderID || order.orderId || order.id || "").trim();

  return {
    id,
    orderDate: orderDateTime.date,
    orderTime: orderDateTime.time,
    orderTimestamp: orderDateTime.timestamp,
    deliveryDate,
    deliveryTimestamp: Date.parse(String(order.deliveryDate || "")) || 0,
    totalCost,
    totalCostDisplay: formatMoney(totalCost),
    status: String(order.orderStatus || order.status || "Unknown"),
    viewLink: buildOrderDetailsUrl(id),
  };
}

function mapOrders(orders) {
  return Array.isArray(orders)
    ? orders.map(mapOrder).filter((order) => order.id)
    : [];
}

async function parseJsonResponse(response) {
  const text = await response.text();

  if (!text) {
    return {};
  }

  try {
    return JSON.parse(text);
  } catch (error) {
    throw new Error("Order API returned invalid JSON.");
  }
}

async function fetchOrders(page = DEFAULT_PAGE, limit = DEFAULT_LIMIT) {
  const token = getOrderAuthToken();
  const headers = {
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(buildOrderApiUrl(page, limit), {
    method: "GET",
    headers,
    credentials: "same-origin",
  });

  const data = await parseJsonResponse(response);

  if (!response.ok || data.status === "error") {
    const isAuthError = response.status === 401 || response.status === 403;
    const message = isAuthError
      ? "Authentication required. Please log in again."
      : data?.message ||
        data?.error ||
        data?.result?.error ||
        `Order request failed. HTTP ${response.status}`;
    const error = new Error(message);
    error.status = response.status;
    error.isAuthError = isAuthError;
    throw error;
  }

  const orders =
    data.data?.orders ||
    data.orders ||
    data.data?.data ||
    data.data ||
    data.result?.orders ||
    data.result?.data ||
    data.result ||
    [];

  return mapOrders(orders);
}

function createCell(tag, textContent, className) {
  const cell = document.createElement(tag);
  cell.className = className;
  cell.textContent = textContent;
  return cell;
}

function getStatusClass(status) {
  switch (String(status || "").toLowerCase()) {
    case "preparing":
    case "pending":
      return "bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-800";
    case "shipped":
    case "delivered":
      return "bg-green-50 text-green-700 ring-green-200 dark:bg-green-900/20 dark:text-green-300 dark:ring-green-800";
    case "processing":
    case "confirmed":
      return "bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:ring-blue-800";
    case "cancelled":
      return "bg-red-50 text-red-700 ring-red-200 dark:bg-red-900/20 dark:text-red-300 dark:ring-red-800";
    default:
      return "bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600";
  }
}

function createStatusBadge(status) {
  const statusSpan = document.createElement("span");
  statusSpan.className = `inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ${getStatusClass(status)}`;
  statusSpan.textContent = status || "Unknown";
  return statusSpan;
}

function createViewLink(order, compact = false) {
  const viewLink = document.createElement("a");
  viewLink.href = order.viewLink;
  viewLink.className = compact
    ? "inline-flex rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
    : "inline-flex rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500";
  viewLink.textContent = compact ? "View" : "View details";
  return viewLink;
}

function renderErrorState(
  message = "Failed to load order records.",
  isAuthError = false,
) {
  const errorState = $("orderErrorState");

  if (!errorState) {
    return;
  }

  errorState.replaceChildren();

  const wrapper = document.createElement("div");
  wrapper.className =
    "flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between";

  const text = document.createElement("p");
  text.className = "text-sm font-medium";
  text.textContent = message;
  wrapper.appendChild(text);

  if (isAuthError) {
    const loginLink = document.createElement("a");
    loginLink.href = LOGIN_PAGE;
    loginLink.className =
      "inline-flex w-fit rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700";
    loginLink.textContent = "Go to login";
    wrapper.appendChild(loginLink);
  }

  errorState.appendChild(wrapper);
}

function getViewContainers() {
  return {
    table: $("orderTableContainer"),
    list: $("orderListContainer"),
    compact: $("orderCompactContainer"),
  };
}

function setActiveViewMode(mode) {
  currentViewMode = VIEW_MODES.has(mode) ? mode : "table";
  localStorage.setItem("orderViewMode", currentViewMode);

  const params = new URLSearchParams(window.location.search);
  params.set("mode", currentViewMode);
  window.history.replaceState(
    {},
    "",
    `${window.location.pathname}?${params.toString()}`,
  );

  document.querySelectorAll("[data-order-mode-link]").forEach((link) => {
    const isActive =
      link.getAttribute("data-order-mode-link") === currentViewMode;
    link.classList.toggle("bg-blue-600", isActive);
    link.classList.toggle("text-white", isActive);
    link.classList.toggle("border-blue-600", isActive);
    link.classList.toggle("border-gray-300", !isActive);
    link.classList.toggle("text-gray-700", !isActive);
    link.classList.toggle("dark:text-gray-300", !isActive);
    link.setAttribute("aria-current", isActive ? "page" : "false");
  });
}

function applyViewMode() {
  const containers = getViewContainers();

  Object.entries(containers).forEach(([mode, container]) => {
    setElementVisible(
      container,
      mode === currentViewMode && currentOrders.length > 0,
    );
  });
}

function setPageState(state, message = "", options = {}) {
  setElementVisible($("loadingSpinner"), state === "loading");
  setElementVisible($("orderErrorState"), state === "error");
  setElementVisible($("orderEmptyState"), state === "empty");

  if (state !== "ready") {
    Object.values(getViewContainers()).forEach((container) =>
      setElementVisible(container, false),
    );
  } else {
    applyViewMode();
  }

  if (state === "error") {
    renderErrorState(
      message || "Failed to load order records.",
      Boolean(options.isAuthError),
    );
  }
}

function renderTableRows(orders) {
  const orderBody = $("order-body");

  if (!orderBody) {
    return;
  }

  orderBody.replaceChildren();

  const fragment = document.createDocumentFragment();

  orders.forEach((order) => {
    const row = document.createElement("tr");
    row.className = "transition hover:bg-gray-50 dark:hover:bg-gray-900/60";

    row.appendChild(
      createCell(
        "td",
        order.id,
        "px-5 py-4 whitespace-nowrap text-sm font-semibold text-gray-950 dark:text-white",
      ),
    );
    row.appendChild(
      createCell(
        "td",
        order.orderDate,
        "hidden sm:table-cell px-5 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300",
      ),
    );
    row.appendChild(
      createCell(
        "td",
        order.orderTime,
        "hidden md:table-cell px-5 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300",
      ),
    );
    row.appendChild(
      createCell(
        "td",
        order.deliveryDate,
        "hidden lg:table-cell px-5 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300",
      ),
    );
    row.appendChild(
      createCell(
        "td",
        order.totalCostDisplay,
        "px-5 py-4 whitespace-nowrap text-sm font-bold text-blue-600 dark:text-blue-400",
      ),
    );

    const statusCell = document.createElement("td");
    statusCell.className = "px-5 py-4 whitespace-nowrap";
    statusCell.appendChild(createStatusBadge(order.status));
    row.appendChild(statusCell);

    const viewLinkCell = document.createElement("td");
    viewLinkCell.className = "px-5 py-4 whitespace-nowrap text-sm font-medium";
    viewLinkCell.appendChild(createViewLink(order));
    row.appendChild(viewLinkCell);

    fragment.appendChild(row);
  });

  orderBody.appendChild(fragment);
}

function renderListCards(orders) {
  const listBody = $("order-list-body");

  if (!listBody) {
    return;
  }

  listBody.replaceChildren();

  const fragment = document.createDocumentFragment();

  orders.forEach((order) => {
    const card = document.createElement("article");
    card.className =
      "rounded-3xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700";

    const header = document.createElement("div");
    header.className =
      "flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between";

    const titleBlock = document.createElement("div");
    const eyebrow = createCell(
      "p",
      "Order",
      "text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400",
    );
    const title = createCell(
      "h2",
      order.id,
      "mt-1 text-xl font-bold text-gray-950 dark:text-white",
    );
    const subtitle = createCell(
      "p",
      `${order.orderDate} ${order.orderTime}`,
      "mt-1 text-sm text-gray-600 dark:text-gray-300",
    );
    titleBlock.append(eyebrow, title, subtitle);

    header.append(titleBlock, createStatusBadge(order.status));

    const details = document.createElement("dl");
    details.className =
      "mt-4 grid grid-cols-1 gap-3 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2";
    details.innerHTML = `
      <div class="rounded-2xl bg-gray-50 p-3 dark:bg-gray-900/60"><dt class="font-medium text-gray-900 dark:text-white">Delivery</dt><dd class="mt-1">${escapeHtml(order.deliveryDate)}</dd></div>
      <div class="rounded-2xl bg-gray-50 p-3 dark:bg-gray-900/60"><dt class="font-medium text-gray-900 dark:text-white">Total</dt><dd class="mt-1 font-semibold text-blue-600 dark:text-blue-400">${escapeHtml(order.totalCostDisplay)}</dd></div>
    `;

    const footer = document.createElement("div");
    footer.className = "mt-4";
    footer.appendChild(createViewLink(order));

    card.append(header, details, footer);
    fragment.appendChild(card);
  });

  listBody.appendChild(fragment);
}

function renderCompactCards(orders) {
  const compactBody = $("order-compact-body");

  if (!compactBody) {
    return;
  }

  compactBody.replaceChildren();

  const fragment = document.createDocumentFragment();

  orders.forEach((order) => {
    const card = document.createElement("article");
    card.className =
      "flex items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700";

    const content = document.createElement("div");
    content.className = "min-w-0";
    content.append(
      createCell(
        "p",
        "Order",
        "text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400",
      ),
      createCell(
        "h2",
        order.id,
        "mt-1 truncate text-sm font-bold text-gray-950 dark:text-white",
      ),
      createCell(
        "p",
        `${order.status} · ${order.totalCostDisplay}`,
        "mt-1 truncate text-xs text-gray-600 dark:text-gray-300",
      ),
    );

    card.append(content, createViewLink(order, true));
    fragment.appendChild(card);
  });

  compactBody.appendChild(fragment);
}

function renderOrders(orders) {
  setText("total-orders", orders.length);
  renderTableRows(orders);
  renderListCards(orders);
  renderCompactCards(orders);

  if (orders.length === 0) {
    setPageState("empty");
    return;
  }

  setPageState("ready");
}

function sortOrders(sortBy) {
  const sortedOrders = [...currentOrders];

  sortedOrders.sort((a, b) => {
    switch (sortBy) {
      case "id":
      case "orderId":
        return a.id.localeCompare(b.id, undefined, { numeric: true });
      case "orderDate":
        return b.orderTimestamp - a.orderTimestamp;
      case "deliveryDate":
        return a.deliveryTimestamp - b.deliveryTimestamp;
      case "totalCost":
      case "totalAmount":
        return b.totalCost - a.totalCost;
      case "status":
        return a.status.localeCompare(b.status);
      default:
        return 0;
    }
  });

  renderOrders(sortedOrders);
}

function setupViewModeControls() {
  setActiveViewMode(currentViewMode);

  document.querySelectorAll("[data-order-mode-link]").forEach((link) => {
    link.addEventListener("click", (event) => {
      event.preventDefault();
      const mode = link.getAttribute("data-order-mode-link") || "table";
      setActiveViewMode(mode);
      applyViewMode();
    });
  });
}

function setupSortControl() {
  const sortSelect = $("sort-select");

  if (!sortSelect) {
    return;
  }

  sortSelect.addEventListener("change", (event) => {
    sortOrders(event.target.value);
  });
}

async function initializeOrdersPage() {
  setInitialModeUrl();
  setupViewModeControls();
  setupSortControl();
  setPageState("loading");

  try {
    console.debug("Loading authenticated dealer orders");
    currentOrders = await fetchOrders(DEFAULT_PAGE, DEFAULT_LIMIT);
    renderOrders(currentOrders);
  } catch (error) {
    console.error("Error fetching orders:", error);
    setText("total-orders", 0);
    currentOrders = [];
    if (error.isAuthError) {
      clearStaleOrderAuthCache();
    }
    setPageState("error", error.message || "Failed to load order records.", {
      isAuthError: Boolean(error.isAuthError),
    });
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeOrdersPage, {
    once: true,
  });
} else {
  initializeOrdersPage();
}
