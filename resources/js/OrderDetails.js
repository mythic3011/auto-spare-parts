function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

const API_ENDPOINTS = window.APP_API_ENDPOINTS || {
  order: "/api/orders/dealer-get",
};
const ORDER_API_URL = API_ENDPOINTS.order;
const ORDER_ITEM_VIEW_MODES = new Set(["table", "list", "compact"]);

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);

  if (parts.length === 2) {
    return decodeURIComponent(parts.pop().split(";").shift() || "");
  }

  return "";
}

function getOrderAuthToken() {
  return (
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

function getUrlParameter(name) {
  return new URLSearchParams(window.location.search).get(name) || "";
}

function getOrderIdFromUrl() {
  const rawValue =
    getUrlParameter("id") ||
    getUrlParameter("orderId") ||
    getUrlParameter("orderID") ||
    getUrlParameter("OrderId") ||
    "";
  const value = String(rawValue).trim();

  if (/^\d+$/.test(value) && Number.isSafeInteger(Number(value)) && Number(value) > 0) {
    return value;
  }

  return "";
}

function getOrderItemViewMode() {
  const mode =
    new URLSearchParams(window.location.search).get("mode") || "table";
  return ORDER_ITEM_VIEW_MODES.has(mode) ? mode : "table";
}

function setVisible(element, isVisible) {
  if (!element) return;
  element.hidden = !isVisible;
  element.classList.toggle("hidden", !isVisible);
  element.style.display = isVisible ? "" : "none";
}

function showLoadingState() {
  setVisible(document.getElementById("loadingSpinner"), true);
  setVisible(document.getElementById("orderContent"), false);
  setVisible(document.getElementById("orderActions"), false);
  setVisible(document.getElementById("orderEmptyState"), false);
  setVisible(document.getElementById("orderErrorState"), false);
}

function showContentState() {
  setVisible(document.getElementById("loadingSpinner"), false);
  setVisible(document.getElementById("orderContent"), true);
  setVisible(document.getElementById("orderActions"), true);
  setVisible(document.getElementById("orderEmptyState"), false);
  setVisible(document.getElementById("orderErrorState"), false);
}

function showEmptyState() {
  setVisible(document.getElementById("loadingSpinner"), false);
  setVisible(document.getElementById("orderContent"), false);
  setVisible(document.getElementById("orderActions"), false);
  setVisible(document.getElementById("orderEmptyState"), true);
  setVisible(document.getElementById("orderErrorState"), false);
}

function showErrorState(message) {
  const errorState = document.getElementById("orderErrorState");
  if (errorState) {
    const text = document.createElement('p');
    text.textContent = message || 'Failed to load order details.';
    const link = document.createElement('a');
    link.href = 'viewOrderRecord.html';
    link.className = 'mt-4 inline-block font-semibold text-blue-600 underline dark:text-blue-400';
    link.textContent = 'Back to order history';
    errorState.replaceChildren(text, link);
  }

  setVisible(document.getElementById("loadingSpinner"), false);
  setVisible(document.getElementById("orderContent"), false);
  setVisible(document.getElementById("orderActions"), false);
  setVisible(document.getElementById("orderEmptyState"), false);
  setVisible(errorState, true);
}

function removeSensitiveUrlParameters() {
  const params = new URLSearchParams(window.location.search);
  const mode = params.get("mode");
  const nextParams = new URLSearchParams();

  if (mode && ORDER_ITEM_VIEW_MODES.has(mode)) {
    nextParams.set("mode", mode);
  }

  const nextQuery = nextParams.toString();
  window.history.replaceState(
    {},
    document.title,
    `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ""}`,
  );
}

function setText(selector, value) {
  const element = document.querySelector(selector);

  if (element) {
    element.textContent = String(value);
  }
}

function formatMoney(value) {
  return `$${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

function formatValue(value, fallback = "N/A") {
  return value === null || value === undefined || value === ""
    ? fallback
    : String(value);
}

async function parseJsonResponse(response) {
  const text = await response.text();

  if (!text) {
    return {};
  }

  try {
    return JSON.parse(text);
  } catch (error) {
    console.error("Invalid JSON response:", text);
    throw new Error("Order API returned invalid JSON.");
  }
}

function normalizeOrderPayload(payload = {}) {
  const data = payload.data || payload.result || payload.order || payload;
  const order = data.summary || data.order || data.details || data;
  const items =
    data.items ||
    data.orderItems ||
    data.orderDetails ||
    data.summary?.items ||
    order.items ||
    order.orderItems ||
    order.orderDetails ||
    [];

  return {
    id: order.orderID || order.orderId || order.id || getOrderIdFromUrl(),
    orderDate: order.orderDate || order.date || order.createdAt || "N/A",
    orderTime: order.orderTime || order.time || "N/A",
    orderStatus: order.orderStatus || order.status || "N/A",
    salesManagerId:
      order.salesManagerId || order.salesManagerID || order.managerId || "N/A",
    salesManagerName:
      order.salesManagerName ||
      order.salesManager ||
      order.managerName ||
      "N/A",
    salesManagerContact:
      order.salesManagerContact || order.managerContact || "N/A",
    deliveryAddress: order.deliveryAddress || order.address || "N/A",
    deliveryDate: order.deliveryDate || "N/A",
    contactPerson:
      order.contactPerson || order.dealerContact || order.contactName || "N/A",
    contactPhone:
      order.contactPhone || order.dealerPhone || order.phone || "N/A",
    totalAmount: Number(
      order.totalAmount || order.subtotal || order.orderTotal || 0,
    ),
    totalWeight: Number(order.totalWeight || 0),
    shippingCost: Number(order.shippingCost || 0),
    shippingCostPerKg: Number(order.shippingCostPerKg || 0),
    totalCost: Number(order.totalCost || order.total || order.totalAmount || 0),
    paymentMethod: order.paymentMethod || "N/A",
    paidOn: order.paidOn || order.paymentDate || "N/A",
    userId: String(
      order.userId ||
        order.UserID ||
        order.dealerId ||
        order.username ||
        order.email ||
        "",
    ),
    items: Array.isArray(items) ? items.map(normalizeOrderItem) : [],
  };
}

function normalizeOrderItem(item = {}) {
  const quantity = Number(item.quantity || item.qty || item.orderQty || 0);
  const price = Number(
    item.price || item.sparePartOrderPrice || item.unitPrice || 0,
  );

  const safeQuantity = Number.isFinite(quantity) ? quantity : 0;
  const safePrice = Number.isFinite(price) ? price : 0;
  const total = Number(
    item.totalPrice || item.total || safeQuantity * safePrice,
  );

  return {
    id: item.id || item.partId || item.sparePartNum || item.itemID || "N/A",
    name: item.name || item.sparePartName || item.itemName || "Unnamed item",
    quantity: safeQuantity,
    price: safePrice,
    total: Number.isFinite(total) ? total : safeQuantity * safePrice,
  };
}

function buildOrderDetailsUrl(orderId) {
  const safeOrderId = String(orderId || "").trim();
  const params = new URLSearchParams({
    action: "dealer-get",
    id: safeOrderId,
    orderId: safeOrderId,
  });
  return `${ORDER_API_URL}?${params.toString()}`;
}

async function fetchOrderDetails(orderId) {
  const token = getOrderAuthToken();
  const headers = {
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(buildOrderDetailsUrl(orderId), {
    method: "GET",
    headers,
    credentials: "same-origin",
  });

  const payload = await parseJsonResponse(response);

  if (!response.ok || payload.status === "error") {
    throw new Error(
      payload.message ||
        payload.error ||
        `Order request failed. HTTP ${response.status}`,
    );
  }

  return normalizeOrderPayload(payload);
}

function isOrderRelatedToUser() {
  return true;
}

function createStrongParagraph(parent, label, value) {
  const paragraph = document.createElement("p");
  paragraph.className = "text-sm text-gray-600 dark:text-gray-300";

  const strong = document.createElement("strong");
  strong.className = "font-semibold text-gray-900 dark:text-white";
  strong.textContent = `${label}: `;

  paragraph.append(strong, document.createTextNode(formatValue(value)));
  parent.appendChild(paragraph);
}

function updateOrderSummary(orderData) {
  const summaryDiv =
    document.querySelector("#orderSummary") ||
    document.querySelector(
      ".bg-white.dark\\:bg-gray-800.shadow.overflow-hidden.sm\\:rounded-lg.p-6",
    );

  if (!summaryDiv) {
    console.error("Order summary container not found.");
    return;
  }

  summaryDiv.replaceChildren();

  const grid = document.createElement("div");
  grid.className = "grid grid-cols-1 gap-3";
  summaryDiv.appendChild(grid);

  createStrongParagraph(grid, "Order Date", orderData.orderDate);
  createStrongParagraph(grid, "Order Time", orderData.orderTime);
  createStrongParagraph(grid, "Order Status", orderData.orderStatus);
  createStrongParagraph(grid, "Sales Manager ID", orderData.salesManagerId);
  createStrongParagraph(grid, "Sales Manager", orderData.salesManagerName);
  createStrongParagraph(grid, "Contact", orderData.salesManagerContact);
  createStrongParagraph(grid, "Delivery Address", orderData.deliveryAddress);
  createStrongParagraph(grid, "Delivery Date", orderData.deliveryDate);
  createStrongParagraph(grid, "Contact Person", orderData.contactPerson);
  createStrongParagraph(grid, "Contact Phone", orderData.contactPhone);
  createStrongParagraph(
    grid,
    "Total Amount",
    formatMoney(orderData.totalAmount),
  );
  createStrongParagraph(grid, "Total Weight", `${orderData.totalWeight} kg`);
  createStrongParagraph(
    grid,
    "Shipping Cost",
    formatMoney(orderData.shippingCost),
  );
  createStrongParagraph(grid, "Total Cost", formatMoney(orderData.totalCost));
  createStrongParagraph(grid, "Payment Method", orderData.paymentMethod);
  createStrongParagraph(grid, "Paid On", orderData.paidOn);
}

function applyOrderItemViewMode() {
  const mode = getOrderItemViewMode();
  const tableContainer = document.getElementById("orderItemsTableContainer");
  const listContainer = document.getElementById("orderItemsListContainer");
  const compactContainer = document.getElementById(
    "orderItemsCompactContainer",
  );

  setVisible(tableContainer, mode === "table");
  setVisible(listContainer, mode === "list");
  setVisible(compactContainer, mode === "compact");
}

function createOrderListCard(item) {
  const card = document.createElement("article");
  card.className =
    "rounded-3xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700";
  card.innerHTML = `
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Order item</p>
        <h3 class="mt-1 truncate text-lg font-bold text-gray-950 dark:text-white"></h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">No. ${escapeHtml(item.id)} · Unit price ${escapeHtml(formatMoney(item.price))}</p>
      </div>
      <div class="text-right">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty ${escapeHtml(item.quantity)}</p>
        <p class="mt-1 text-lg font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">${formatMoney(item.total)}</p>
      </div>
    </div>
  `;
  card.querySelector("h3").textContent = item.name;
  return card;
}

function createOrderCompactCard(item) {
  const card = document.createElement("article");
  card.className =
    "rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700";
  card.innerHTML = `
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Order item</p>
        <h3 class="mt-1 truncate text-sm font-bold text-gray-950 dark:text-white"></h3>
        <p class="mt-1 truncate text-xs text-gray-600 dark:text-gray-300">Qty ${escapeHtml(item.quantity)} · ${escapeHtml(formatMoney(item.price))}</p>
      </div>
      <p class="whitespace-nowrap text-sm font-bold text-blue-600 dark:text-blue-400">${formatMoney(item.total)}</p>
    </div>
  `;
  card.querySelector("h3").textContent = item.name;
  return card;
}

function updateOrderItems(orderItems) {
  const tableBody =
    document.querySelector("#orderItemsBody") ||
    document.querySelector("tbody");
  const listContainer = document.getElementById("orderItemsListContainer");
  const compactContainer = document.getElementById(
    "orderItemsCompactContainer",
  );

  if (!tableBody || !listContainer || !compactContainer) {
    console.error("Order item containers not found.");
    return;
  }

  tableBody.replaceChildren();
  listContainer.replaceChildren();
  compactContainer.replaceChildren();

  if (!Array.isArray(orderItems) || orderItems.length === 0) {
    const row = document.createElement("tr");
    const cell = document.createElement("td");
    cell.colSpan = 4;
    cell.className =
      "px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400";
    cell.textContent = "No order items found.";
    row.appendChild(cell);
    tableBody.appendChild(row);
    applyOrderItemViewMode();
    return;
  }

  orderItems.forEach((item) => {
    const row = document.createElement("tr");
    row.className = "transition hover:bg-gray-50 dark:hover:bg-gray-900/60";

    const cells = [
      `${item.name} (${item.id})`,
      String(item.quantity),
      formatMoney(item.price),
      formatMoney(item.total),
    ];

    cells.forEach((content) => {
      const cell = document.createElement("td");
      cell.className =
        "px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300";
      cell.textContent = content;
      row.appendChild(cell);
    });

    tableBody.appendChild(row);
    listContainer.appendChild(createOrderListCard(item));
    compactContainer.appendChild(createOrderCompactCard(item));
  });

  applyOrderItemViewMode();
}

function redirectForbidden(
  message = "You are not allowed to view this order.",
) {
  showErrorState(message);
}

async function initializePage() {
  const orderId = getOrderIdFromUrl();
  showLoadingState();

  if (!orderId) {
    const params = new URLSearchParams(window.location.search);
    const provided = ['id', 'orderId', 'orderID', 'OrderId'].some(key => params.has(key));
    if (provided) showErrorState('This order link is invalid. Open your order history and select an order.');
    else showEmptyState();
    return;
  }

  try {
    const orderData = await fetchOrderDetails(orderId);

    if (!isOrderRelatedToUser(orderData)) {
      redirectForbidden();
      return;
    }

    setText(
      "#OrderDetails-title",
      `Order Details - ${orderData.id || orderId}`,
    );
    updateOrderSummary(orderData);
    updateOrderItems(orderData.items);
    showContentState();
    removeSensitiveUrlParameters();
  } catch (error) {
    console.error("Error initializing order details:", error);
    showErrorState(error.message || "Failed to load order details.");
  }
}

async function sendOrderActionRequest(orderId, action) {
  const token = getOrderAuthToken();
  const headers = {
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const params = new URLSearchParams({
    action,
    id: orderId,
    orderId,
  });

  const response = await fetch(`${ORDER_API_URL}?${params.toString()}`, {
    method: "POST",
    headers,
    credentials: "same-origin",
  });

  const payload = await parseJsonResponse(response);

  if (!response.ok || payload.status === "error") {
    throw new Error(
      payload.message ||
        payload.error ||
        `Order action failed. HTTP ${response.status}`,
    );
  }

  return payload;
}

document.addEventListener("DOMContentLoaded", () => {
  const orderId = getOrderIdFromUrl();

  initializePage();

  document.getElementById("printOrder")?.addEventListener("click", () => {
    window.print();
  });

  document.getElementById("downloadInvoice")?.addEventListener("click", () => {
    if (!orderId) return;

    const params = new URLSearchParams({
      action: "download",
      id: orderId,
      orderId,
    });

    window.open(`${ORDER_API_URL}?${params.toString()}`, "_blank", "noopener");
  });

  document
    .getElementById("deleteOrder")
    ?.addEventListener("click", async () => {
      if (!orderId || !confirm("Are you sure you want to delete this order?")) {
        return;
      }

      try {
        await sendOrderActionRequest(orderId, "delete");
        window.location.href = "viewOrderRecord.html";
      } catch (error) {
        console.error("Failed to delete order:", error);
        alert(error.message || "Failed to delete order.");
      }
    });
});
