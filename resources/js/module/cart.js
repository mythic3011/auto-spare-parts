import { API_ENDPOINTS, buildApiUrl } from "./config/endpoints.js";
import { escapeHtml } from "./html.js";

const CART_API_URL = API_ENDPOINTS.cart;
const CART_KEYS = ["cart", "cartItems", "shoppingCart"];
const VIEW_MODES = new Set(["table", "list", "compact"]);

let currentItems = [];
let isSyncing = false;
let serverTotal = 0;

const $ = (id) => document.getElementById(id);

const elements = {};

function bindElements() {
  Object.assign(elements, {
    syncState: $("clientCartSyncState"),
    serverContent: $("serverCartContent"),
    emptyState: $("cartEmptyState"),
    tableContainer: $("clientCartTableContainer"),
    listContainer: $("clientCartListContainer"),
    compactContainer: $("clientCartCompactContainer"),
    tableBody: $("clientCartBody"),
    itemCount: $("cartItemCount"),
    summaryItemCount: $("summaryItemCount"),
    subtotal: $("cartSubtotal"),
    shipping: $("cartShipping"),
    total: $("cartTotal"),
    checkoutButton: $("checkoutButton"),
  });
}

function getViewMode() {
  const mode =
    new URLSearchParams(window.location.search).get("mode") || "table";
  return VIEW_MODES.has(mode) ? mode : "table";
}

function setVisible(element, isVisible) {
  if (!element) return;
  element.hidden = !isVisible;
  element.classList.toggle("hidden", !isVisible);
  element.style.display = isVisible ? "" : "none";
}

function money(value) {
  return `$${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

function normalizeItem(item = {}) {
  const id = String(
    item.id || item.partId || item.sparePartNum || item.itemID || "",
  ).trim();
  const name = String(
    item.name || item.sparePartName || item.itemName || "Unnamed item",
  ).trim();
  const price = Number(
    item.price || item.sparePartOrderPrice || item.unitPrice || 0,
  );
  const quantity = Math.max(
    1,
    Math.floor(Number(item.quantity || item.qty || 1)) || 1,
  );
  const weight = Number(
    item.weight || item.unitWeight || item.sparePartWeight || 0,
  );
  const safePrice = Number.isFinite(price) ? price : 0;
  const totalPrice = Number(
    item.totalPrice || item.total || safePrice * quantity,
  );

  return {
    id,
    name,
    price: safePrice,
    quantity,
    weight: Number.isFinite(weight) ? Math.max(0, weight) : 0,
    totalPrice: Number.isFinite(totalPrice) ? totalPrice : safePrice * quantity,
  };
}

function normalizeItems(items) {
  return Array.isArray(items)
    ? items.map(normalizeItem).filter((item) => item.id)
    : [];
}

function getItems(payload = {}) {
  const cart = getCartPayload(payload);
  return normalizeItems(
    cart.cartItems || cart.items || payload.cartItems || payload.items || [],
  );
}

function getCartId(payload = {}) {
  const cart = getCartPayload(payload);
  return String(cart.cartId || payload.cartId || "").trim();
}

function getCartPayload(payload = {}) {
  if (payload.result && typeof payload.result === "object")
    return payload.result;
  if (payload.data && typeof payload.data === "object") return payload.data;
  return payload;
}

function getServerTotal(payload = {}) {
  const cart = getCartPayload(payload);
  const value = Number(
    cart.cartTotal || cart.total || payload.cartTotal || payload.total || 0,
  );
  return Number.isFinite(value) ? value : 0;
}

function readLocalSeed() {
  for (const key of CART_KEYS) {
    const rawValue = localStorage.getItem(key);
    if (!rawValue) continue;

    try {
      const parsed = JSON.parse(rawValue);
      const items = normalizeItems(
        Array.isArray(parsed) ? parsed : parsed.cartItems || parsed.items || [],
      );
      if (items.length > 0) return items;
    } catch (error) {
      console.error(`Failed to parse local cart from ${key}:`, error);
    }
  }

  return [];
}

function saveCache(items, cartId = "") {
  const cartItems = normalizeItems(items);
  const nextCartId = String(
    cartId || localStorage.getItem("cartId") || "",
  ).trim();
  const payload = { cartId: nextCartId, cartItems };
  if (nextCartId) {
    localStorage.setItem("cartId", nextCartId);
  }
  localStorage.setItem("cart", JSON.stringify(payload));
  localStorage.setItem("cartItems", JSON.stringify(cartItems));
}

async function api(action, payload = {}) {
  const response = await fetch(buildApiUrl(CART_API_URL, { action }), {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify(payload),
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok || data.status === "error") {
    throw new Error(
      data.message || data.error || `Cart API failed. HTTP ${response.status}`,
    );
  }

  return data.result && typeof data.result === "object" ? data.result : data;
}

async function fetchCart({ writeCache = true } = {}) {
  const payload = await api("get");
  console.debug("Loaded server cart:", payload);
  const items = getItems(payload);
  serverTotal = getServerTotal(payload);
  if (writeCache) {
    saveCache(items, getCartId(payload));
  }
  return items;
}

async function syncLocalSeed(seedItems = readLocalSeed()) {
  const items = normalizeItems(seedItems);
  if (items.length === 0) {
    return false;
  }
  for (const item of items) {
    await api("add", { partId: item.id, quantity: item.quantity });
  }
  return true;
}

async function updateQuantity(item, quantity) {
  const payload = await api("update", {
    partId: item.id,
    quantity: Math.max(1, Math.floor(Number(quantity) || 1)),
  });
  return getItems(payload);
}

async function removeItem(item) {
  return getItems(await api("remove", { partId: item.id }));
}

function setBusy(
  nextState,
  message = "Syncing cart with server stock validation...",
) {
  isSyncing = nextState;
  if (elements.syncState) elements.syncState.textContent = message;
  setVisible(elements.syncState, nextState);

  if (elements.checkoutButton) {
    elements.checkoutButton.disabled = nextState || currentItems.length === 0;
    elements.checkoutButton.title = nextState
      ? message
      : currentItems.length === 0
        ? "Cart has not synced with server yet."
        : "";
  }
}

function summary() {
  const calculatedSubtotal = currentItems.reduce(
    (sum, item) => sum + item.totalPrice,
    0,
  );
  const subtotal = serverTotal > 0 ? serverTotal : calculatedSubtotal;
  const count = currentItems.reduce((sum, item) => sum + item.quantity, 0);

  if (elements.itemCount) elements.itemCount.textContent = String(count);
  if (elements.summaryItemCount) elements.summaryItemCount.textContent = String(count);
  if (elements.subtotal) elements.subtotal.textContent = money(subtotal);
  if (elements.total) elements.total.textContent = money(subtotal);
  if (elements.checkoutButton && !isSyncing) {
    elements.checkoutButton.disabled = currentItems.length === 0;
  }

  saveCache(currentItems, localStorage.getItem("cartId") || "");
}

function actionButton(action, item, label, className) {
  return `<button type="button" data-cart-action="${escapeHtml(action)}" data-cart-item-id="${escapeHtml(item.id)}" class="${escapeHtml(className)}">${escapeHtml(label)}</button>`;
}

function quantityControl(item) {
  const buttonClass =
    "inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-lg font-semibold text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700";
  const inputClass =
    "w-20 rounded-lg border border-gray-300 bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-900 dark:text-white";

  return `
    <div class="flex items-center gap-2">
      ${actionButton("decrement", item, "−", buttonClass)}
      <input type="number" min="1" step="1" value="${escapeHtml(item.quantity)}" data-cart-action="quantity" data-cart-item-id="${escapeHtml(item.id)}" class="${escapeHtml(inputClass)}" aria-label="Quantity">
      ${actionButton("increment", item, "+", buttonClass)}
    </div>`;
}

function removeButton(item) {
  return actionButton(
    "remove",
    item,
    "Remove",
    "rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40",
  );
}

function showActiveMode() {
  const mode = getViewMode();
  const hasItems = currentItems.length > 0;
  setVisible(elements.tableContainer, hasItems && mode === "table");
  setVisible(elements.listContainer, hasItems && mode === "list");
  setVisible(elements.compactContainer, hasItems && mode === "compact");
}

function row(item) {
  const tr = document.createElement("tr");
  tr.className = "transition hover:bg-gray-50 dark:hover:bg-gray-900/60";
  tr.innerHTML = `
    <td class="px-5 py-4 whitespace-nowrap text-sm font-semibold text-gray-950 dark:text-white"></td>
    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${escapeHtml(money(item.price))}</td>
    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${quantityControl(item)}</td>
    <td class="px-5 py-4 whitespace-nowrap text-sm font-bold text-blue-600 dark:text-blue-400">${escapeHtml(money(item.totalPrice))}</td>
    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${removeButton(item)}</td>`;
  tr.firstElementChild.textContent = item.name;
  return tr;
}

function listCard(item) {
  const card = document.createElement("article");
  card.className =
    "rounded-3xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700";
  card.innerHTML = `
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Cart item</p>
        <h3 class="mt-1 truncate text-lg font-bold text-gray-950 dark:text-white"></h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">No. ${escapeHtml(item.id)} · Unit price ${escapeHtml(money(item.price))}</p>
      </div>
      <div class="flex shrink-0 flex-col gap-3 sm:items-end">
        <div class="text-right">
          <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Item total</p>
          <p class="text-lg font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">${escapeHtml(money(item.totalPrice))}</p>
        </div>
        ${quantityControl(item)}
        <div class="flex items-center justify-end">${removeButton(item)}</div>
      </div>
    </div>`;
  card.querySelector("h3").textContent = item.name;
  return card;
}

function compactCard(item) {
  const card = document.createElement("article");
  card.className =
    "rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700";
  card.innerHTML = `
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Cart item</p>
        <h3 class="mt-1 truncate text-sm font-bold text-gray-950 dark:text-white"></h3>
        <p class="mt-1 truncate text-xs text-gray-600 dark:text-gray-300">${escapeHtml(money(item.totalPrice))}</p>
      </div>
      ${removeButton(item)}
    </div>
    <div class="mt-3">${quantityControl(item)}</div>`;
  card.querySelector("h3").textContent = item.name;
  return card;
}

function render(items) {
  currentItems = normalizeItems(items);
  elements.tableBody?.replaceChildren();
  elements.listContainer?.replaceChildren();
  elements.compactContainer?.replaceChildren();

  setVisible(elements.serverContent, false);
  setVisible(elements.emptyState, currentItems.length === 0);

  for (const item of currentItems) {
    elements.tableBody?.appendChild(row(item));
    elements.listContainer?.appendChild(listCard(item));
    elements.compactContainer?.appendChild(compactCard(item));
  }

  showActiveMode();
  summary();
}

async function withSync(operation, message) {
  try {
    setBusy(true, message);
    render(await operation());
  } catch (error) {
    console.error("Cart action failed:", error);
    render(await fetchCart().catch(() => currentItems));
  } finally {
    setBusy(false);
  }
}

function onCartAction(event) {
  const target =
    event.target instanceof Element
      ? event.target.closest("[data-cart-action]")
      : null;
  if (!target) return;

  const action = target.getAttribute("data-cart-action");
  const item = currentItems.find(
    (cartItem) => cartItem.id === target.getAttribute("data-cart-item-id"),
  );
  if (!item) return;

  event.preventDefault();

  const operations = {
    remove: () => removeItem(item),
    increment: () => updateQuantity(item, item.quantity + 1),
    decrement: () => updateQuantity(item, Math.max(1, item.quantity - 1)),
  };

  if (operations[action]) {
    withSync(
      operations[action],
      action === "remove" ? "Removing item..." : "Updating quantity...",
    );
  }
}

function onQuantityChange(event) {
  const target = event.target instanceof HTMLInputElement ? event.target : null;
  if (!target || target.getAttribute("data-cart-action") !== "quantity") return;

  const item = currentItems.find(
    (cartItem) => cartItem.id === target.getAttribute("data-cart-item-id"),
  );
  if (!item) return;

  withSync(() => updateQuantity(item, target.value), "Updating quantity...");
}

async function init() {
  bindElements();
  document.addEventListener("click", onCartAction);
  document.addEventListener("change", onQuantityChange);

  await withSync(async () => {
    const seedItems = readLocalSeed();
    const serverItems = await fetchCart({ writeCache: false });
    if (serverItems.length > 0) {
      saveCache(serverItems, localStorage.getItem("cartId") || "");
      return serverItems;
    }
    const wasSeedSynced = await syncLocalSeed(seedItems);
    return wasSeedSynced ? fetchCart({ writeCache: true }) : [];
  }, "Syncing cart with server stock validation...");
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init, { once: true });
} else {
  init();
}
