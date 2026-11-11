import { apiRequest } from "./module/api.js";
import { API_ENDPOINTS, buildApiUrl } from "./module/config/endpoints.js";
import { escapeHtml } from "./module/html.js";

function displayError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.id = 'errorDiv';
    errorDiv.className = 'text-red-500 text-center mt-4';
    errorDiv.textContent = message;
    document.getElementById('orderSummaryDiv').insertAdjacentElement('beforebegin', errorDiv);
}

document.addEventListener('DOMContentLoaded', function () {
    // if (getURLParameter('id') == null || window.SLMC_AUTH.getToken() == null) {
    //     window.location.href = "../../error/403.html";
    // }

    showLoading();
    fetchOrderData()
        .then(orderData => {
            if (!orderData) return;
            console.log(orderData);
            renderOrderSummary(orderData.summary);
            renderOrderItems(orderData.items);
            renderOrderActions(orderData.summary);
            setupEventListeners();
        })
        .catch(error => {
            console.error('Error fetching order data:', error);
            displayError(error.message);
        })
        .finally(() => {
            hideLoading();
        });
});

function getURLParameter(name) {
    return new URLSearchParams(window.location.search).get(name);
}

function fetchOrderData() {
    const orderID = getURLParameter('OrderId') || getURLParameter('orderId') || getURLParameter('id');
    if (!/^\d+$/.test(orderID || '') || !Number.isSafeInteger(Number(orderID)) || Number(orderID) <= 0) {
        const message = document.createElement('p');
        message.className = 'p-6 text-gray-600 dark:text-gray-400';
        message.textContent = 'Open the orders list and select an order to view its details.';
        const link = document.createElement('a');
        link.href = 'order.html';
        link.className = 'ml-2 font-semibold text-blue-600 underline';
        link.textContent = 'Back to orders';
        message.append(link);
        document.querySelector('main')?.replaceChildren(message);
        hideLoading();
        return Promise.resolve();
    }
    const apiUrl = buildApiUrl(API_ENDPOINTS.orderManagerDetail, { id: orderID });
    const AuthToken = window.SLMC_AUTH.getToken();

    return fetch(apiUrl, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${AuthToken}`
        },
        credentials: 'include'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' && data.data) {
                return data.data;
            } else {
                throw new Error(data.message || 'Failed to fetch order details');
            }
        })
        .catch(error => {
            console.error('Error fetching order data:', error);
            // window.location.href = "./viewOrderRecord.html"
            throw error; // Re-throw the error for further handling if needed
        });
}

function formatDate(dateString) {
    // DD/MM/YYYY format
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
        const [day, month, year] = dateString.split('/');
        return `${day}/${month}/${year}`;
    }
    // YYYY-MM-DD format
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        const [year, month, day] = dateString.split('-');
        return `${day}/${month}/${year}`;
    }
    console.error('Invalid date string:', dateString);
    return 'Invalid Date';
}

function formatTime(timeString) {
    if (!timeString || !/^\d{2}:\d{2}(:\d{2})?$/.test(timeString)) {
        console.error('Invalid time string:', timeString);
        return 'Invalid Time';
    }
    const [hours, minutes] = timeString.split(':');
    return `${hours}:${minutes}`;
}

function renderOrderSummary(order) {
    const summaryContainer = document.getElementById('orderSummary');
    summaryContainer.innerHTML = `
        <p><strong>Order Date:</strong> ${escapeHtml(formatDate(order.orderDate))}</p>
        <p><strong>Order Time:</strong> ${escapeHtml(order.orderTime)}</p>
        <p><strong>Order Status:</strong> ${escapeHtml(order.orderStatus)}</p>
        <p><strong>Sales Manager ID:</strong> ${escapeHtml(order.salesManagerId)}</p>
        <p><strong>Sales Manager:</strong> ${escapeHtml(order.salesManager)}</p>
        <p><strong>Contact:</strong> ${escapeHtml(order.salesManagerContact)}</p>
        <p><strong>Delivery Address:</strong> ${escapeHtml(order.deliveryAddress)}</p>
        <p><strong>Delivery Date:</strong> ${escapeHtml(formatDate(order.deliveryDate))}</p>
        <p><strong>Contact Person:</strong> ${escapeHtml(order.contactPerson)}</p>
        <p><strong>Contact Phone:</strong> ${escapeHtml(order.contactPhone)}</p>
        <p><strong>Total Amount:</strong> $${escapeHtml(order.totalAmount.toLocaleString())}</p>
        <p><strong>Total Weight:</strong> ${escapeHtml(order.totalWeight)}kg</p>
        <p><strong>Shipping Cost:</strong> $${escapeHtml(order.shippingCost)} ($${escapeHtml((order.shippingCost / order.totalWeight).toFixed(2))}/kg)</p>
        <p><strong>Total Cost:</strong> $${escapeHtml(order.totalCost.toLocaleString())}</p>
    `;
    document.getElementById('OrderDetails-title').textContent = `Order Details - ${order.id}`;
}

function renderOrderItems(items) {
    const itemsContainer = document.getElementById('order-Details-SpartPart');
    itemsContainer.innerHTML = items.map(item => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">${escapeHtml(item.name)} (${escapeHtml(item.id)})</td>
            <td class="px-6 py-4 whitespace-nowrap">${escapeHtml(item.quantity)}</td>
            <td class="px-6 py-4 whitespace-nowrap">$${escapeHtml(item.price.toLocaleString())}</td>
            <td class="px-6 py-4 whitespace-nowrap">$${escapeHtml((item.quantity * item.price).toLocaleString())}</td>
        </tr>
    `).join('');
}


function renderOrderActions(order) {
    const buttons = getActionButtons();
    const next = { Pending: 'confirmBtn', Confirmed: 'shippedBtn', Processing: 'shippedBtn', Shipped: 'deliveredBtn' }[order.orderStatus];
    for (const [key, button] of Object.entries(buttons)) {
        if (!button) continue;
        button.hidden = key !== next && !(key === 'cancelBtn' && ['Pending', 'Confirmed', 'Processing'].includes(order.orderStatus));
        button.style.display = button.hidden ? 'none' : 'inline-flex';
    }
}

function initializeOrderActionButtons() {
    const buttons = getActionButtons();
    for (const [key, status] of Object.entries({ confirmBtn: 'Confirmed', shippedBtn: 'Shipped', deliveredBtn: 'Delivered', cancelBtn: 'Cancelled' })) {
        buttons[key]?.addEventListener('click', () => {
            if (status !== 'Cancelled' || confirm('Cancel this order?')) updateOrderStatus(status);
        });
    }
}

function getActionButtons() {
    return Object.fromEntries(['confirmBtn', 'shippedBtn', 'deliveredBtn', 'cancelBtn'].map(id => [id, document.getElementById(id)]));
}

async function loadOrderData() {
    const data = await fetchOrderData();
    if (!data) return;
    renderOrderSummary(data.summary);
    renderOrderItems(data.items);
    renderOrderActions(data.summary);
}

function updateOrderStatus(status) {
    const orderId = getUrlParameter('orderId') || getUrlParameter('OrderId') || getUrlParameter('id');
    const AuthToken = window.SLMC_AUTH.getToken();
    const apiUrl = buildApiUrl(API_ENDPOINTS.orderUpdateStatus, { id: orderId, status });
    fetch(apiUrl, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${AuthToken}`
        },
        body: JSON.stringify({ id: orderId, status })
    })
        .then(response => {
            if (response.ok) {
                alert(`Order status updated to ${status}.`);
                loadOrderData();
            } else {
                alert(`Failed to ${status.toLowerCase()} order`);
            }
        })
        .catch(error => {
            console.error(`Error ${status.toLowerCase()}ing order:`, error);
            alert(`Failed to ${status.toLowerCase()} order`);
        });
}

function setupEventListeners() {
    document.getElementById('mobileMenuButton').addEventListener('click', toggleMobileMenu);
    document.getElementById('darkModeToggle').addEventListener('click', toggleDarkMode);
    initializeOrderActionButtons();
}

function getUrlParameter(name) {
    return new URLSearchParams(window.location.search).get(name);
}

function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    mobileMenu.style.display = mobileMenu.style.display === 'none' ? 'flex' : 'none';
}

function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
}

function showLoading() {
    document.getElementById('loadingSpinner').classList.remove('hidden');
    document.getElementById('orderSummaryDiv').classList.add('hidden');
    document.getElementById('orderActionsDiv').classList.add('hidden');
    document.getElementById('orderItemsDiv').classList.add('hidden');
    document.getElementById('OrderDetails-title').textContent = 'Order Details - Loading...';
}

function hideLoading() {
    document.getElementById('loadingSpinner').classList.add('hidden');
    document.getElementById('orderSummaryDiv').classList.remove('hidden');
    document.getElementById('orderActionsDiv').classList.remove('hidden');
    document.getElementById('orderItemsDiv').classList.remove('hidden');
}
