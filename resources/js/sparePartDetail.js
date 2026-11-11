const API_ENDPOINTS = window.APP_API_ENDPOINTS || {
  cart: "/api/cart/api",
  sparePartDetail: "/api/spare_part/detail",
};
const API_URL = API_ENDPOINTS.cart;
const SPARE_PART_DETAIL_API_URL = API_ENDPOINTS.sparePartDetail;
const CART_ID_STORAGE_KEY = "cartId";
const CART_STORAGE_KEY = "cart";
const DEFAULT_IMAGE_URL = "../../resources/image/SLMC_Icon_DM.png";

const CART_ACTION_ALIASES = {
  create: ["create", "createCart"],
  get: ["get", "getCart", "list", "view"],
  add: ["add", "addItem", "addToCart"],
  update: ["update", "updateItem", "updateQuantity"],
  remove: ["remove", "removeItem", "delete"],
};

let cartId = localStorage.getItem(CART_ID_STORAGE_KEY) || "";
let currentPartId = "";
let currentStockQty = 0;

function $(id) {
  return document.getElementById(id);
}

function getCookie(name) {
  const cookies = document.cookie.split(";");

  for (const cookie of cookies) {
    const [rawKey, ...rawValue] = cookie.trim().split("=");

    if (rawKey === name) {
      return decodeURIComponent(rawValue.join("="));
    }
  }

  return "";
}

function getAuthToken() {
  return localStorage.getItem("token") || getCookie("token") || "";
}

function getCsrfToken() {
  return getCookie("csrf_token") || "";
}

function getSafePartId() {
  const params = new URLSearchParams(window.location.search);
  const sparePartId = params.get("id");

  return sparePartId && /^\d+$/.test(sparePartId) ? sparePartId : null;
}

function setElementVisible(element, isVisible) {
  if (!element) {
    return;
  }

  element.hidden = !isVisible;
  element.classList.toggle("hidden", !isVisible);
  element.style.display = isVisible ? "" : "none";
}

function setDetailState(state, message = "") {
  const loading = $("sparePartLoading");
  const error = $("sparePartError");
  const empty = $("sparePartEmpty");
  const container = $("sparePartContainer");

  setElementVisible(loading, state === "loading");
  setElementVisible(error, state === "error");
  setElementVisible(empty, state === "empty");
  setElementVisible(container, state === "ready");

  if (error && message) {
    error.textContent = message;
  }
}

function showPageError(message) {
  setDetailState("error", message || "Failed to load spare part detail.");
}

function showInlineMessage(type, message) {
  const container = $("sparePartContainer");

  if (!container) {
    return;
  }

  let messageBox = $("sparePartMessage");

  if (!messageBox) {
    messageBox = document.createElement("div");
    messageBox.id = "sparePartMessage";
    container.before(messageBox);
  }

  messageBox.className =
    type === "success"
      ? "mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300"
      : "mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300";
  messageBox.textContent = message;
  setElementVisible(messageBox, true);
}

function formatMoney(value) {
  const amount = Number(value);

  if (!Number.isFinite(amount)) {
    return "$0.00";
  }

  return `$${amount.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

function getCategoryLabel(category) {
  const categoryNumber = Number(category);

  if (
    Number.isInteger(categoryNumber) &&
    categoryNumber >= 1 &&
    categoryNumber <= 26
  ) {
    return String.fromCharCode(64 + categoryNumber);
  }

  return category ? String(category) : "N/A";
}

function getSafeImageUrl(fileName) {
  const safeFileName = String(fileName || "")
    .replaceAll("\\", "/")
    .split("/")
    .pop();

  if (!safeFileName) {
    return DEFAULT_IMAGE_URL;
  }

  return `../../resources/image/spare/${encodeURIComponent(safeFileName)}`;
}

function mapSparePartData(sparePart) {
  return {
    id: String(sparePart.sparePartNum || ""),
    name: String(sparePart.sparePartName || "Unnamed spare part"),
    category: getCategoryLabel(sparePart.sparePartCategory),
    price: Number(sparePart.price) || 0,
    imageUrl: getSafeImageUrl(sparePart.sparePartImage),
    stockItemQty: Number(sparePart.stockItemQty) || 0,
    weight: sparePart.weight || "N/A",
    countryOfOrigin: sparePart.countryOfOrigin || "N/A",
    brand: sparePart.brand || "N/A",
    description: sparePart.sparePartDescription || "No description available.",
  };
}

async function fetchJson(url, options) {
  const response = await fetch(url, options);
  const text = await response.text();
  let data = null;

  try {
    data = text ? JSON.parse(text) : null;
  } catch (error) {
    throw new Error("Server returned invalid JSON.");
  }

  if (!response.ok) {
    throw new Error(data?.error || data?.message || `HTTP ${response.status}`);
  }

  return data;
}

async function fetchSparePartDetail(sparePartId) {
  const data = await fetchJson(SPARE_PART_DETAIL_API_URL, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: getAuthToken() ? `Bearer ${getAuthToken()}` : "",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-Token": getCsrfToken(),
    },
    body: JSON.stringify({
      csrf_token: getCsrfToken(),
      sparePartId,
    }),
  });

  if (data?.error) {
    throw new Error(data.error);
  }

  if (!data?.data) {
    throw new Error("Spare part not found.");
  }

  return mapSparePartData(data.data);
}

function createElement(tag, className = "", text = "") {
  const element = document.createElement(tag);

  if (className) {
    element.className = className;
  }

  if (text !== "") {
    element.textContent = text;
  }

  return element;
}

function createMetaItem(label, value) {
  const wrapper = createElement(
    "div",
    "rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40",
  );
  const labelElement = createElement(
    "dt",
    "text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400",
    label,
  );
  const valueElement = createElement(
    "dd",
    "mt-1 text-sm font-semibold text-gray-900 dark:text-white",
    value || "N/A",
  );
  wrapper.append(labelElement, valueElement);
  return wrapper;
}

function createQuantityControls(data) {
  const wrapper = createElement("div", "mt-6");
  const label = createElement(
    "label",
    "mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300",
    "Quantity",
  );
  label.htmlFor = "quantity";

  const control = createElement(
    "div",
    "inline-flex overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600",
  );

  const decrement = createElement(
    "button",
    "w-10 bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600",
    "-",
  );
  decrement.type = "button";
  decrement.setAttribute("aria-label", "Decrease quantity");
  decrement.addEventListener("click", decrementQuantity);

  const input = document.createElement("input");
  input.type = "number";
  input.id = "quantity";
  input.min = "1";
  input.max = String(Math.max(1, Math.min(data.stockItemQty, 99)));
  input.value = data.stockItemQty > 0 ? "1" : "0";
  input.className =
    "w-20 border-x border-gray-300 bg-white px-3 py-2 text-center text-sm text-gray-900 outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white";
  input.addEventListener("change", handleQuantityInputChange);

  const increment = createElement(
    "button",
    "w-10 bg-gray-100 text-lg font-semibold text-gray-700 hover:bg-gray-200 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600",
    "+",
  );
  increment.type = "button";
  increment.setAttribute("aria-label", "Increase quantity");
  increment.addEventListener("click", incrementQuantity);

  if (data.stockItemQty <= 0) {
    decrement.disabled = true;
    increment.disabled = true;
    input.disabled = true;
  }

  control.append(decrement, input, increment);
  wrapper.append(label, control);
  return wrapper;
}

function createActionButton(id, label, className, onClick) {
  const button = createElement("button", className, label);
  button.id = id;
  button.type = "button";
  button.addEventListener("click", onClick);
  return button;
}

function renderSparePart(data) {
  document.title = `Spare Part - ${data.name}`;
  currentPartId = data.id;
  currentStockQty = data.stockItemQty;

  const container = $("sparePartContainer");

  if (!container) {
    throw new Error("Spare part container is missing.");
  }

  container.replaceChildren();

  const card = createElement(
    "article",
    "overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800",
  );
  const layout = createElement("div", "grid grid-cols-1 gap-0 lg:grid-cols-2");

  const imagePanel = createElement("div", "bg-gray-100 p-4 dark:bg-gray-900");
  const image = document.createElement("img");
  image.src = data.imageUrl;
  image.alt = data.name;
  image.loading = "lazy";
  image.className =
    "h-full max-h-[520px] min-h-[320px] w-full rounded-xl object-contain bg-white dark:bg-gray-800";
  image.onerror = () => {
    image.src = DEFAULT_IMAGE_URL;
  };
  imagePanel.appendChild(image);

  const infoPanel = createElement("div", "p-6 lg:p-8");
  const status = data.stockItemQty > 0 ? "In stock" : "Out of stock";
  const statusClass =
    data.stockItemQty > 0
      ? "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300"
      : "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300";

  const topLine = createElement(
    "div",
    "mb-4 flex flex-wrap items-center gap-2",
  );
  const partNo = createElement(
    "span",
    "rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300",
    `No. ${data.id}`,
  );
  const stockBadge = createElement(
    "span",
    `rounded-full px-3 py-1 text-xs font-semibold ${statusClass}`,
    status,
  );
  topLine.append(partNo, stockBadge);

  const title = createElement(
    "h1",
    "text-3xl font-bold tracking-tight text-gray-900 dark:text-white",
    data.name,
  );
  const price = createElement(
    "p",
    "mt-4 text-3xl font-bold text-blue-600 dark:text-blue-400",
    formatMoney(data.price),
  );
  const description = createElement(
    "p",
    "mt-4 text-sm leading-6 text-gray-600 dark:text-gray-300",
    data.description,
  );
  const stock = createElement(
    "p",
    "mt-4 text-sm text-gray-600 dark:text-gray-300",
    `Available stock: ${data.stockItemQty}`,
  );

  const quantityControls = createQuantityControls(data);

  const actions = createElement("div", "mt-6 flex flex-col gap-3 sm:flex-row");
  const buyNowButton = createActionButton(
    "buyNowBtn",
    "Buy Now",
    "inline-flex justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60",
    buyNow,
  );
  const addToCartButton = createActionButton(
    "addToCartBtn",
    "Add to Cart",
    "inline-flex justify-center rounded-lg bg-green-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60",
    addToCart,
  );
  const removeFromCartButton = createActionButton(
    "removeFromCartBtn",
    "Remove from Cart",
    "hidden inline-flex justify-center rounded-lg bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-red-700",
    removeFromCart,
  );

  if (data.stockItemQty <= 0) {
    buyNowButton.disabled = true;
    addToCartButton.disabled = true;
  }

  actions.append(buyNowButton, addToCartButton, removeFromCartButton);

  const metaGrid = createElement(
    "dl",
    "mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2",
  );
  metaGrid.append(
    createMetaItem("Category", data.category),
    createMetaItem("Weight", String(data.weight)),
    createMetaItem("Country of Origin", String(data.countryOfOrigin)),
    createMetaItem("Brand", String(data.brand)),
  );

  infoPanel.append(
    topLine,
    title,
    price,
    description,
    stock,
    quantityControls,
    actions,
    metaGrid,
  );
  layout.append(imagePanel, infoPanel);
  card.appendChild(layout);
  container.appendChild(card);

  setDetailState("ready");
}

function getQuantityInput() {
  return $("quantity");
}

function getSelectedQuantity() {
  const input = getQuantityInput();
  const rawValue = Number(input?.value || 1);
  const maxQty = Math.max(1, Math.min(currentStockQty || 1, 99));

  if (!Number.isFinite(rawValue)) {
    return 1;
  }

  return Math.max(1, Math.min(Math.floor(rawValue), maxQty));
}

function setSelectedQuantity(quantity) {
  const input = getQuantityInput();

  if (!input) {
    return;
  }

  input.value = String(quantity);
}

function decrementQuantity() {
  setSelectedQuantity(Math.max(1, getSelectedQuantity() - 1));
}

function incrementQuantity() {
  const maxQty = Math.max(1, Math.min(currentStockQty || 1, 99));
  setSelectedQuantity(Math.min(maxQty, getSelectedQuantity() + 1));
}

function handleQuantityInputChange() {
  setSelectedQuantity(getSelectedQuantity());
}

async function fetchCartApi(action, data = {}) {
  const aliases = CART_ACTION_ALIASES[action] || [action];
  let lastError = null;

  for (const actionName of aliases) {
    try {
      const result = await fetchJson(
        `${API_URL}?action=${encodeURIComponent(actionName)}`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: getAuthToken(),
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-Token": getCsrfToken(),
          },
          body: JSON.stringify({
            cartId,
            csrf_token: getCsrfToken(),
            ...data,
          }),
        },
      );

      return result.result && typeof result.result === "object"
        ? result.result
        : result;
    } catch (error) {
      lastError = error;

      if (
        !String(error.message || "")
          .toLowerCase()
          .includes("invalid action")
      ) {
        throw error;
      }
    }
  }

  throw lastError || new Error("Cart action is not supported.");
}

function updateLocalCart(cartItems) {
  localStorage.setItem(
    CART_STORAGE_KEY,
    JSON.stringify({
      cartId,
      cartItems: Array.isArray(cartItems) ? cartItems : [],
    }),
  );
}

function updateUIAfterCartChange(isInCart, quantity = 1) {
  const addButton = $("addToCartBtn");
  const removeButton = $("removeFromCartBtn");
  const quantityInput = getQuantityInput();

  if (addButton) {
    addButton.classList.toggle("hidden", isInCart);
  }

  if (removeButton) {
    removeButton.classList.toggle("hidden", !isInCart);
  }

  if (quantityInput) {
    quantityInput.value = String(quantity || 1);
  }
}

async function createCart() {
  const userId = localStorage.getItem("username") || getCookie("id") || "guest";
  const result = await fetchCartApi("create", { userId });

  if (!result.cartId) {
    throw new Error("Cart ID not received from server.");
  }

  cartId = String(result.cartId);
  localStorage.setItem(CART_ID_STORAGE_KEY, cartId);
  return cartId;
}

async function getCart() {
  if (!cartId) {
    return { cartItems: [] };
  }

  try {
    const result = await fetchCartApi("get", { cartId });
    const cartItems = Array.isArray(result.cartItems) ? result.cartItems : [];
    updateLocalCart(cartItems);

    const currentItem = cartItems.find(
      (item) =>
        String(
          item.id || item.partId || item.sparePartId || item.sparePartNum,
        ) === currentPartId,
    );

    if (currentItem) {
      updateUIAfterCartChange(
        true,
        Number(currentItem.quantity || currentItem.qty) || 1,
      );
    }

    return { ...result, cartItems };
  } catch (error) {
    console.error("Failed to retrieve cart:", error);
    return { cartItems: [] };
  }
}

async function ensureCart() {
  if (!cartId) {
    await createCart();
  }

  return getCart();
}

async function updateQuantity(quantity) {
  const safeQuantity = Number(quantity);

  if (!Number.isInteger(safeQuantity) || safeQuantity <= 0) {
    throw new Error("Please select a quantity greater than 0.");
  }

  if (currentStockQty > 0 && safeQuantity > currentStockQty) {
    throw new Error("Selected quantity exceeds available stock.");
  }

  const cart = await ensureCart();
  const itemInCart = cart.cartItems.some(
    (item) =>
      String(
        item.id || item.partId || item.sparePartId || item.sparePartNum,
      ) === currentPartId,
  );

  const result = itemInCart
    ? await fetchCartApi("update", {
        partId: currentPartId,
        quantity: safeQuantity,
      })
    : await fetchCartApi("add", {
        partId: currentPartId,
        quantity: safeQuantity,
      });

  updateLocalCart(result.cartItems);
  updateUIAfterCartChange(true, safeQuantity);
  return result;
}

async function addToCart() {
  try {
    const quantity = getSelectedQuantity();
    await updateQuantity(quantity);
    showInlineMessage("success", "Item added to cart successfully.");
  } catch (error) {
    console.error(`Failed to add item ${currentPartId} to cart:`, error);
    showInlineMessage("error", error.message || "Failed to add item to cart.");
  }
}

async function removeFromCart() {
  if (!cartId) {
    updateUIAfterCartChange(false, 1);
    return null;
  }

  try {
    const result = await fetchCartApi("remove", { partId: currentPartId });
    updateLocalCart(result.cartItems);
    updateUIAfterCartChange(false, 1);
    showInlineMessage("success", "Item removed from cart.");
    return result;
  } catch (error) {
    console.error(`Failed to remove item ${currentPartId} from cart:`, error);
    showInlineMessage(
      "error",
      error.message || "Failed to remove item from cart.",
    );
    return null;
  }
}

async function buyNow() {
  try {
    const quantity = getSelectedQuantity();
    await updateQuantity(quantity);
    window.location.href = "shoppingCart.php";
  } catch (error) {
    console.error("Buy now failed:", error);
    showInlineMessage("error", error.message || "Failed to buy item.");
  }
}

async function initializePage() {
  const sparePartId = getSafePartId();

  if (!sparePartId) {
    setDetailState("empty");
    return;
  }

  currentPartId = sparePartId;
  setDetailState("loading");

  try {
    const sparePart = await fetchSparePartDetail(sparePartId);
    renderSparePart(sparePart);
  } catch (error) {
    console.error("Error loading spare part:", error);
    showPageError(
      error.message || "An error occurred while loading the spare part.",
    );
  }
}

function boot() {
  const container = $("sparePartContainer");

  if (container) {
    setElementVisible(container, false);
  }

  initializePage();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot, { once: true });
} else {
  boot();
}
