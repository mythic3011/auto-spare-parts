const FALLBACK_IMAGE_URL = "../../resources/image/SLMC_Icon_DM.png";
const CATEGORY_NAMES = {
  A: "Sheet Metal",
  B: "Major Asssemblies",
  C: "Light Components",
  D: "Accessories",
};
const GALLERY_MODE_CLASSES = {
  grid: "space-y-8",
  list: "space-y-4",
  compact: "grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3",
  detail: "space-y-6",
};

const API_ENDPOINTS = window.APP_API_ENDPOINTS || {
  viewSparePart: "/api/spare_part/view",
  cart: "/api/cart/api",
};
const sparePartsApiUrl = API_ENDPOINTS.viewSparePart;
const cartApiUrl = API_ENDPOINTS.cart;

let spareParts = [];
let cartId = localStorage.getItem("cartId") || "";
let cartItems = [];

function getCookie(name) {
  const cookieValue = document.cookie
    .split(";")
    .map((cookie) => cookie.trim())
    .find((cookie) => cookie.startsWith(`${name}=`));

  if (!cookieValue) {
    return null;
  }

  return decodeURIComponent(cookieValue.slice(name.length + 1));
}

function getAuthToken() {
  return localStorage.getItem("token") || getCookie("token") || "";
}

function getCsrfToken() {
  return getCookie("csrf_token") || "";
}

function getGalleryMode() {
  const mode = new URLSearchParams(window.location.search).get("mode");
  return ["grid", "list", "compact", "detail"].includes(
    (mode || "").toLowerCase(),
  )
    ? mode.toLowerCase()
    : "grid";
}

function getCategory(category) {
  const numericCategory = Number.parseInt(String(category), 10);
  if (
    Number.isInteger(numericCategory) &&
    numericCategory >= 1 &&
    numericCategory <= 26
  ) {
    return String.fromCharCode(64 + numericCategory);
  }

  return "N/A";
}

function getCategoryName(category) {
  return CATEGORY_NAMES[category] || "N/A";
}

function getSafeImageUrl(fileName) {
  const rawFileName = typeof fileName === "string" ? fileName.trim() : "";
  if (!rawFileName) {
    return FALLBACK_IMAGE_URL;
  }

  const strippedFileName = rawFileName.split("/").pop().split("\\").pop();
  if (!strippedFileName) {
    return FALLBACK_IMAGE_URL;
  }

  return `../../resources/image/spare/${encodeURIComponent(strippedFileName)}`;
}

function formatMoney(value) {
  const numericValue = Number(value);
  const safeValue = Number.isFinite(numericValue) ? numericValue : 0;

  return `$${safeValue.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

function ensureMessageContainer() {
  const listContainer = document.getElementById("SparePartList");
  if (!listContainer) {
    return null;
  }

  let messageContainer = document.getElementById("SparePartMessage");
  if (!messageContainer) {
    messageContainer = document.createElement("div");
    messageContainer.id = "SparePartMessage";
    messageContainer.className =
      "hidden mb-4 rounded-lg border px-4 py-3 text-sm";
    messageContainer.setAttribute("role", "alert");
    listContainer.parentNode.insertBefore(messageContainer, listContainer);
  }

  return messageContainer;
}

function showMessage(type, message) {
  const messageContainer = ensureMessageContainer();
  if (!messageContainer) {
    return;
  }

  if (!message) {
    messageContainer.textContent = "";
    messageContainer.className =
      "hidden mb-4 rounded-lg border px-4 py-3 text-sm";
    messageContainer.dataset.messageType = "";
    return;
  }

  const variants = {
    error:
      "border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-200",
    success:
      "border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-200",
    info: "border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200",
  };

  messageContainer.textContent = message;
  messageContainer.className = `mb-4 rounded-lg border px-4 py-3 text-sm ${variants[type] || variants.info}`;
  messageContainer.dataset.messageType = type || "info";
}

function mapSparePartData(item) {
  const stockItemQty = Math.max(0, Number.parseInt(item.stockItemQty, 10) || 0);

  return {
    id: String(item.sparePartNum ?? ""),
    name: String(item.sparePartName ?? ""),
    category: getCategory(item.sparePartCategory),
    price: Number(item.price) || 0,
    imageUrl: getSafeImageUrl(item.sparePartImage),
    stockItemQty,
    maxQuantity: Math.min(stockItemQty, 99),
  };
}

async function fetchSpareParts() {
  const response = await fetch(sparePartsApiUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: getAuthToken(),
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-Token": getCsrfToken(),
    },
    body: JSON.stringify({
      Token: getAuthToken(),
      csrf_token: getCsrfToken(),
    }),
  });

  if (!response.ok) {
    throw new Error(`Failed to load spare parts (${response.status})`);
  }

  const payload = await response.json();
  const rawParts = Array.isArray(payload.data) ? payload.data : [];

  spareParts = rawParts.map(mapSparePartData);
  populateCategoryFilter(spareParts);
  applyFiltersAndSort();

  return spareParts;
}

function populateCategoryFilter(parts) {
  const categoryFilter = document.getElementById("CategoryFilter");
  if (!categoryFilter) {
    return;
  }

  const currentSelection = categoryFilter.value || "All";
  const categories = [
    ...new Set(parts.map((part) => part.category).filter(Boolean)),
  ];

  categoryFilter.replaceChildren();

  const defaultOption = document.createElement("option");
  defaultOption.value = "All";
  defaultOption.textContent = "All Categories";
  categoryFilter.appendChild(defaultOption);

  categories.forEach((category) => {
    const option = document.createElement("option");
    option.value = category;
    option.textContent = `Category ${category}`;
    categoryFilter.appendChild(option);
  });

  const availableOptions = ["All", ...categories];
  categoryFilter.value = availableOptions.includes(currentSelection)
    ? currentSelection
    : "All";
}

function getFilteredAndSortedParts() {
  const searchInput = document.getElementById("sparePartSearch");
  const sortingSelect = document.getElementById("SparePartSorting");
  const categoryFilter = document.getElementById("CategoryFilter");

  const searchTerm = (searchInput?.value || "").trim().toLowerCase();
  const sortBy = sortingSelect?.value || "None";
  const selectedCategory = categoryFilter?.value || "All";

  const filteredParts = spareParts.filter((part) => {
    const matchesSearch =
      !searchTerm ||
      part.name.toLowerCase().includes(searchTerm) ||
      part.id.toLowerCase().includes(searchTerm);
    const matchesCategory =
      selectedCategory === "All" || part.category === selectedCategory;

    return matchesSearch && matchesCategory;
  });

  const compareIds = (left, right) => {
    const leftValue = Number.parseInt(String(left.id).replace(/\D+/g, ""), 10);
    const rightValue = Number.parseInt(
      String(right.id).replace(/\D+/g, ""),
      10,
    );

    if (Number.isFinite(leftValue) && Number.isFinite(rightValue)) {
      return leftValue - rightValue;
    }

    return String(left.id).localeCompare(String(right.id));
  };

  const sortedParts = [...filteredParts];
  switch (sortBy) {
    case "Price-Low-to-High":
      sortedParts.sort((left, right) => left.price - right.price);
      break;
    case "Price-High-to-Low":
      sortedParts.sort((left, right) => right.price - left.price);
      break;
    case "Category":
      sortedParts.sort(
        (left, right) =>
          left.category.localeCompare(right.category) ||
          compareIds(left, right),
      );
      break;
    default:
      sortedParts.sort(compareIds);
      break;
  }

  return sortedParts;
}

function applyFiltersAndSort() {
  const sortingSelect = document.getElementById("SparePartSorting");
  const categoryFilterContainer = document.getElementById(
    "CategoryFilterContainer",
  );

  if (categoryFilterContainer) {
    categoryFilterContainer.style.display =
      sortingSelect?.value === "Category" ? "block" : "none";
  }

  renderSpareParts(getFilteredAndSortedParts());
}

function renderSpareParts(parts) {
  const container = document.getElementById("SparePartList");
  if (!container) {
    return;
  }

  const mode = getGalleryMode();
  container.dataset.galleryMode = mode;
  container.className = GALLERY_MODE_CLASSES[mode] || GALLERY_MODE_CLASSES.grid;
  container.replaceChildren();

  if (!parts.length) {
    showMessage("info", "No spare parts match your filters.");
    return;
  }

  const messageContainer = document.getElementById("SparePartMessage");
  if (
    messageContainer &&
    messageContainer.dataset.messageType === "info" &&
    messageContainer.textContent === "No spare parts match your filters."
  ) {
    showMessage("", "");
  }

  if (mode === "grid") {
    renderGroupedGrid(container, parts);
  } else {
    parts.forEach((part) => {
      container.appendChild(createSparePartCard(part, mode));
    });
  }

  syncCartStateToRenderedParts();
}

function renderGroupedGrid(container, parts) {
  const groupedParts = parts.reduce((groups, part) => {
    if (!groups.has(part.category)) {
      groups.set(part.category, []);
    }

    groups.get(part.category).push(part);
    return groups;
  }, new Map());

  groupedParts.forEach((categoryParts, category) => {
    const section = document.createElement("section");
    section.className = "space-y-4";
    section.id = `SparePartList-${category}`;

    const heading = document.createElement("h2");
    heading.className = "text-2xl font-semibold text-gray-900 dark:text-white";
    heading.textContent = `${category} - ${getCategoryName(category)}`;
    section.appendChild(heading);

    const grid = document.createElement("div");
    grid.className = "grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3";

    categoryParts.forEach((part) => {
      grid.appendChild(createSparePartCard(part, "grid"));
    });

    section.appendChild(grid);
    container.appendChild(section);
  });
}

function createSparePartCard(part, mode) {
  switch (mode) {
    case "list":
      return createListCard(part);
    case "compact":
      return createCompactCard(part);
    case "detail":
      return createDetailCard(part);
    case "grid":
    default:
      return createGridCard(part);
  }
}

function createGridCard(part) {
  const card = document.createElement("article");
  card.className =
    "overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800";

  const image = document.createElement("img");
  image.className = "h-48 w-full object-cover";
  image.src = part.imageUrl;
  image.alt = part.name;
  card.appendChild(image);

  const content = document.createElement("div");
  content.className = "space-y-3 p-4";

  const title = document.createElement("h3");
  title.className = "text-lg font-semibold text-gray-900 dark:text-white";
  title.textContent = part.name;
  content.appendChild(title);

  const partNumber = document.createElement("p");
  partNumber.className = "text-sm text-gray-600 dark:text-gray-300";
  partNumber.textContent = `Spare Part No. ${part.id}`;
  content.appendChild(partNumber);

  const stock = document.createElement("p");
  stock.className =
    part.stockItemQty > 0
      ? "text-sm text-gray-600 dark:text-gray-300"
      : "text-sm font-medium text-red-600 dark:text-red-300";
  stock.textContent = `Stock: ${part.stockItemQty}`;
  content.appendChild(stock);

  const price = document.createElement("p");
  price.className = "text-sm text-gray-600 dark:text-gray-300";
  price.textContent = `Unit Price: ${formatMoney(part.price)}`;
  content.appendChild(price);

  content.appendChild(createActions(part));
  card.appendChild(content);

  return card;
}

function createListCard(part) {
  const card = document.createElement("article");
  card.className =
    "flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:flex-row dark:border-gray-700 dark:bg-gray-800";

  const image = document.createElement("img");
  image.className =
    "h-48 w-full rounded-md object-cover md:h-40 md:w-40 md:flex-none";
  image.src = part.imageUrl;
  image.alt = part.name;
  card.appendChild(image);

  const content = document.createElement("div");
  content.className = "flex min-w-0 flex-1 flex-col justify-between gap-4";

  const textBlock = document.createElement("div");
  textBlock.className = "space-y-2";

  const title = document.createElement("h3");
  title.className = "text-lg font-semibold text-gray-900 dark:text-white";
  title.textContent = part.name;
  textBlock.appendChild(title);

  const partNumber = document.createElement("p");
  partNumber.className = "text-sm text-gray-600 dark:text-gray-300";
  partNumber.textContent = `Spare Part No. ${part.id}`;
  textBlock.appendChild(partNumber);

  const category = document.createElement("p");
  category.className = "text-sm text-gray-600 dark:text-gray-300";
  category.textContent = `Category: ${part.category} - ${getCategoryName(part.category)}`;
  textBlock.appendChild(category);

  const stock = document.createElement("p");
  stock.className =
    part.stockItemQty > 0
      ? "text-sm text-gray-600 dark:text-gray-300"
      : "text-sm font-medium text-red-600 dark:text-red-300";
  stock.textContent = `Stock: ${part.stockItemQty}`;
  textBlock.appendChild(stock);

  const price = document.createElement("p");
  price.className = "text-sm text-gray-600 dark:text-gray-300";
  price.textContent = `Unit Price: ${formatMoney(part.price)}`;
  textBlock.appendChild(price);

  content.appendChild(textBlock);
  content.appendChild(createActions(part));
  card.appendChild(content);

  return card;
}

function createCompactCard(part) {
  const card = document.createElement("article");
  card.className =
    "flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800";

  const image = document.createElement("img");
  image.className = "h-16 w-16 flex-none rounded-md object-cover";
  image.src = part.imageUrl;
  image.alt = part.name;
  card.appendChild(image);

  const content = document.createElement("div");
  content.className = "min-w-0 flex-1 space-y-1";

  const title = document.createElement("h3");
  title.className =
    "truncate text-sm font-semibold text-gray-900 dark:text-white";
  title.textContent = part.name;
  content.appendChild(title);

  const partNumber = document.createElement("p");
  partNumber.className = "text-xs text-gray-600 dark:text-gray-300";
  partNumber.textContent = `No. ${part.id}`;
  content.appendChild(partNumber);

  const price = document.createElement("p");
  price.className = "text-xs text-gray-600 dark:text-gray-300";
  price.textContent = `Price: ${formatMoney(part.price)}`;
  content.appendChild(price);

  const stock = document.createElement("p");
  stock.className =
    part.stockItemQty > 0
      ? "text-xs text-gray-600 dark:text-gray-300"
      : "text-xs font-medium text-red-600 dark:text-red-300";
  stock.textContent = `Stock: ${part.stockItemQty}`;
  content.appendChild(stock);

  card.appendChild(content);
  card.appendChild(createDetailsLink(part));

  return card;
}

function createDetailCard(part) {
  const card = document.createElement("article");
  card.className =
    "grid grid-cols-1 gap-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm lg:grid-cols-2 dark:border-gray-700 dark:bg-gray-800";

  const image = document.createElement("img");
  image.className = "h-72 w-full rounded-lg object-cover";
  image.src = part.imageUrl;
  image.alt = part.name;
  card.appendChild(image);

  const content = document.createElement("div");
  content.className = "space-y-4";

  const title = document.createElement("h3");
  title.className = "text-2xl font-semibold text-gray-900 dark:text-white";
  title.textContent = part.name;
  content.appendChild(title);

  const partNumber = document.createElement("p");
  partNumber.className = "text-sm text-gray-600 dark:text-gray-300";
  partNumber.textContent = `Spare Part No. ${part.id}`;
  content.appendChild(partNumber);

  const category = document.createElement("p");
  category.className = "text-sm text-gray-600 dark:text-gray-300";
  category.textContent = `Category: ${part.category} - ${getCategoryName(part.category)}`;
  content.appendChild(category);

  const stock = document.createElement("p");
  stock.className =
    part.stockItemQty > 0
      ? "text-sm text-gray-600 dark:text-gray-300"
      : "text-sm font-medium text-red-600 dark:text-red-300";
  stock.textContent = `Stock: ${part.stockItemQty}`;
  content.appendChild(stock);

  const price = document.createElement("p");
  price.className = "text-sm text-gray-600 dark:text-gray-300";
  price.textContent = `Unit Price: ${formatMoney(part.price)}`;
  content.appendChild(price);

  content.appendChild(createActions(part));
  card.appendChild(content);

  return card;
}

function createDetailsLink(part) {
  const detailsLink = document.createElement("a");
  detailsLink.href = `sparePart-template.html?id=${encodeURIComponent(part.id)}`;
  detailsLink.className =
    "inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700";
  detailsLink.textContent = "View Details";
  return detailsLink;
}

function createActions(part) {
  const actions = document.createElement("div");
  actions.className = "space-y-3";

  const topRow = document.createElement("div");
  topRow.className = "flex flex-wrap items-center gap-2";
  topRow.appendChild(createDetailsLink(part));

  const quantityControl = createQuantityControl(part);
  topRow.appendChild(quantityControl);
  actions.appendChild(topRow);

  const buttonRow = document.createElement("div");
  buttonRow.className = "flex flex-wrap gap-2";

  const addToCartBtn = document.createElement("button");
  addToCartBtn.type = "button";
  addToCartBtn.id = `addToCartBtn-${part.id}`;
  addToCartBtn.className =
    "rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50";
  addToCartBtn.textContent = "Add to Cart";
  addToCartBtn.addEventListener("click", () => addToCart(part.id));
  buttonRow.appendChild(addToCartBtn);

  const removeFromCartBtn = document.createElement("button");
  removeFromCartBtn.type = "button";
  removeFromCartBtn.id = `removeFromCartBtn-${part.id}`;
  removeFromCartBtn.className =
    "hidden rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50";
  removeFromCartBtn.textContent = "Remove from Cart";
  removeFromCartBtn.addEventListener("click", () => removeFromCart(part.id));
  buttonRow.appendChild(removeFromCartBtn);

  actions.appendChild(buttonRow);

  if (part.stockItemQty <= 0) {
    addToCartBtn.disabled = true;
  }

  return actions;
}

function createQuantityControl(part) {
  const wrapper = document.createElement("div");
  wrapper.className =
    "inline-flex items-center overflow-hidden rounded-md border border-gray-300 dark:border-gray-600";

  const decrementButton = document.createElement("button");
  decrementButton.type = "button";
  decrementButton.className =
    "flex h-8 w-8 items-center justify-center bg-gray-100 text-gray-700 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600";
  decrementButton.textContent = "-";
  decrementButton.addEventListener("click", () => decrementQuantity(part.id));
  wrapper.appendChild(decrementButton);

  const input = document.createElement("input");
  input.type = "number";
  input.id = `quantity-${part.id}`;
  input.value = "0";
  input.min = "0";
  input.max = String(part.maxQuantity);
  input.step = "1";
  input.inputMode = "numeric";
  input.className =
    "h-8 w-16 border-x border-gray-300 bg-white text-center text-sm text-gray-900 [appearance:textfield] focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none";
  input.addEventListener("input", () =>
    setSelectedQuantity(part.id, input.value),
  );
  input.addEventListener("change", () => updateQuantity(part.id, input.value));
  wrapper.appendChild(input);

  const incrementButton = document.createElement("button");
  incrementButton.type = "button";
  incrementButton.className =
    "flex h-8 w-8 items-center justify-center bg-gray-100 text-gray-700 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600";
  incrementButton.textContent = "+";
  incrementButton.addEventListener("click", () => incrementQuantity(part.id));
  wrapper.appendChild(incrementButton);

  if (part.maxQuantity <= 0) {
    decrementButton.disabled = true;
    incrementButton.disabled = true;
    input.disabled = true;
    input.value = "0";
  }

  return wrapper;
}

function getPartById(partId) {
  return spareParts.find((part) => String(part.id) === String(partId)) || null;
}

function getQuantityInput(partId) {
  return document.getElementById(`quantity-${partId}`);
}

function getSelectedQuantity(partId) {
  const input = getQuantityInput(partId);
  if (!input) {
    return 0;
  }

  const parsedValue = Number.parseInt(input.value, 10);
  return Number.isInteger(parsedValue) && parsedValue >= 0 ? parsedValue : 0;
}

function setSelectedQuantity(partId, quantity) {
  const part = getPartById(partId);
  const input = getQuantityInput(partId);
  if (!input) {
    return 0;
  }

  const parsedValue = Number.parseInt(String(quantity), 10);
  const integerValue = Number.isInteger(parsedValue) ? parsedValue : 0;
  const maxQuantity = part
    ? part.maxQuantity
    : Number.parseInt(input.max, 10) || 0;
  const clampedQuantity = Math.max(0, Math.min(integerValue, maxQuantity));

  input.min = "0";
  input.max = String(maxQuantity);
  input.value = String(clampedQuantity);
  input.disabled = maxQuantity <= 0;

  return clampedQuantity;
}

function decrementQuantity(partId) {
  const nextQuantity = Math.max(0, getSelectedQuantity(partId) - 1);
  setSelectedQuantity(partId, nextQuantity);
  updateQuantity(partId, nextQuantity);
}

function incrementQuantity(partId) {
  const part = getPartById(partId);
  const maxQuantity = part
    ? part.maxQuantity
    : Number.parseInt(getQuantityInput(partId)?.max || "0", 10) || 0;
  const nextQuantity = Math.min(maxQuantity, getSelectedQuantity(partId) + 1);

  setSelectedQuantity(partId, nextQuantity);
  updateQuantity(partId, nextQuantity);
}

async function fetchCartApi(action, data = {}) {
  const requestUrl = new URL(cartApiUrl, window.location.href);
  requestUrl.searchParams.set("action", action);

  const requestMethod = action === "get" ? "GET" : "POST";
  const options = {
    method: requestMethod,
    headers: {
      Authorization: getAuthToken(),
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-Token": getCsrfToken(),
    },
  };

  if (requestMethod === "GET") {
    const requestCartId = data.cartId || cartId;
    if (requestCartId) {
      requestUrl.searchParams.set("cartId", requestCartId);
    }
    if (getCsrfToken()) {
      requestUrl.searchParams.set("csrf_token", getCsrfToken());
    }
  } else {
    const requestCartId = data.cartId || cartId;
    options.headers["Content-Type"] = "application/json";
    options.body = JSON.stringify({
      cartId: requestCartId,
      csrf_token: getCsrfToken(),
      ...data,
    });
  }

  const response = await fetch(requestUrl.href, options);
  const result = await response.json();

  if (!response.ok || result.status === "error") {
    throw new Error(result.message || `Cart ${action} failed`);
  }

  return result;
}

function updateLocalCart(items) {
  cartItems = Array.isArray(items) ? items : [];
  localStorage.setItem(
    "cart",
    JSON.stringify({
      cartId,
      cartItems,
    }),
  );
}

function updateUIAfterCartChange(partId, isInCart, quantity) {
  const addBtn = document.getElementById(`addToCartBtn-${partId}`);
  const removeBtn = document.getElementById(`removeFromCartBtn-${partId}`);
  const quantityInput = getQuantityInput(partId);
  const part = getPartById(partId);
  const maxQuantity = part
    ? part.maxQuantity
    : Number.parseInt(quantityInput?.max || "0", 10) || 0;
  const isOutOfStock = maxQuantity <= 0;

  if (quantityInput) {
    quantityInput.disabled = isOutOfStock;
    quantityInput.max = String(maxQuantity);
    if (typeof quantity !== "undefined") {
      quantityInput.value = String(
        Math.max(
          0,
          Math.min(Number.parseInt(String(quantity), 10) || 0, maxQuantity),
        ),
      );
    } else if (isOutOfStock) {
      quantityInput.value = "0";
    }
  }

  if (addBtn) {
    addBtn.disabled = isOutOfStock;
    addBtn.classList.toggle("hidden", false);
  }

  if (removeBtn) {
    removeBtn.classList.toggle("hidden", !isInCart);
  }

  if (isInCart) {
    if (addBtn) {
      addBtn.classList.add("hidden");
    }
    if (removeBtn) {
      removeBtn.classList.remove("hidden");
    }
  } else {
    if (addBtn) {
      addBtn.classList.remove("hidden");
    }
    if (removeBtn) {
      removeBtn.classList.add("hidden");
    }
  }
}

function syncCartStateToRenderedParts() {
  cartItems.forEach((item) => {
    updateUIAfterCartChange(item.id, true, item.quantity);
  });
}

async function createCart() {
  const userId = localStorage.getItem("username");
  if (!userId) {
    throw new Error("User ID is required to create a cart");
  }

  const result = await fetchCartApi("create", { userId });
  if (!result.cartId) {
    throw new Error("Cart ID not received from server");
  }

  cartId = result.cartId;
  localStorage.setItem("cartId", cartId);
  return cartId;
}

async function getCart() {
  if (!cartId) {
    return { cartItems: [] };
  }

  const result = await fetchCartApi("get", { cartId });
  const items = Array.isArray(result.cartItems) ? result.cartItems : [];

  if (result.cartId) {
    cartId = result.cartId;
    localStorage.setItem("cartId", cartId);
  }

  updateLocalCart(items);

  items.forEach((item) => {
    updateUIAfterCartChange(item.id, true, item.quantity);
  });

  return {
    ...result,
    cartItems: items,
  };
}

async function initializeCart() {
  if (!cartId) {
    await createCart();
  }

  try {
    await getCart();
  } catch (error) {
    showMessage("error", error.message || "Unable to load your cart.");
    throw error;
  }
}

async function addToCart(partId) {
  const quantity = setSelectedQuantity(partId, getSelectedQuantity(partId));
  if (quantity <= 0) {
    showMessage("error", "Please select a quantity greater than 0.");
    return;
  }

  await updateQuantity(partId, quantity);
}

async function removeFromCart(partId) {
  try {
    const result = await fetchCartApi("remove", { partId });
    const items = Array.isArray(result.cartItems) ? result.cartItems : [];

    updateLocalCart(items);
    updateUIAfterCartChange(partId, false, 0);
    setSelectedQuantity(partId, 0);
    syncCartStateToRenderedParts();

    return result;
  } catch (error) {
    showMessage(
      "error",
      error.message || `Failed to remove item ${partId} from cart.`,
    );
    throw error;
  }
}

async function updateQuantity(partId, quantity) {
  const part = getPartById(partId);
  if (!part) {
    return;
  }

  const normalizedQuantity = setSelectedQuantity(partId, quantity);
  const currentCart = cartItems;
  const itemInCart = currentCart.some(
    (item) => String(item.id) === String(partId),
  );

  if (normalizedQuantity <= 0) {
    if (itemInCart) {
      return removeFromCart(partId);
    }

    updateUIAfterCartChange(partId, false, 0);
    return null;
  }

  try {
    const action = itemInCart ? "update" : "add";
    const result = await fetchCartApi(action, {
      partId,
      quantity: normalizedQuantity,
    });

    const items = Array.isArray(result.cartItems) ? result.cartItems : [];
    updateLocalCart(items);
    updateUIAfterCartChange(partId, true, normalizedQuantity);
    syncCartStateToRenderedParts();

    return result;
  } catch (error) {
    showMessage(
      "error",
      error.message || `Failed to update quantity for part ${partId}.`,
    );
    throw error;
  }
}

function bindControls() {
  const sortingSelect = document.getElementById("SparePartSorting");
  const categoryFilter = document.getElementById("CategoryFilter");
  const searchInput = document.getElementById("sparePartSearch");
  const categoryFilterContainer = document.getElementById(
    "CategoryFilterContainer",
  );

  if (sortingSelect) {
    sortingSelect.addEventListener("change", () => {
      if (categoryFilterContainer) {
        categoryFilterContainer.style.display =
          sortingSelect.value === "Category" ? "block" : "none";
      }

      applyFiltersAndSort();
    });

    if (categoryFilterContainer) {
      categoryFilterContainer.style.display =
        sortingSelect.value === "Category" ? "block" : "none";
    }
  }

  if (categoryFilter) {
    categoryFilter.addEventListener("change", () => {
      applyFiltersAndSort();
    });
  }

  if (searchInput) {
    searchInput.addEventListener("input", () => {
      applyFiltersAndSort();
    });
  }
}

async function initializePage() {
  const listContainer = document.getElementById("SparePartList");
  if (!listContainer) {
    return;
  }

  bindControls();

  try {
    await initializeCart();
  } catch (error) {
    // Cart initialization errors are already surfaced inline.
  }

  try {
    await fetchSpareParts();
  } catch (error) {
    showMessage("error", error.message || "Unable to load spare parts.");
  }
}

document.addEventListener("DOMContentLoaded", initializePage);
