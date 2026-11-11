import { API_ENDPOINTS, buildApiUrl } from "./config/endpoints.js";

class OrderActions {
    constructor() {
        this.actionButtons = this.getActionButtons();
        this.statusActions = {
            'Wait for confirmation': { print: false, download: false },
            'Shipping': { print: true, download: false },
            'Preparing': { print: true, download: true },
            'Shipped': { print: true, download: true },
            'Cancelled': { print: false, download: false }
        };
        this.initializeOrderActionButtons();
    }

    renderOrderActions(order) {
        const actions = this.statusActions[order.orderStatus] || this.statusActions['Cancelled'];
        this.setActionVisibility(actions.print, actions.download);
    }

    setActionVisibility(print, download) {
        Object.entries(this.actionButtons).forEach(([key, button]) => {
            button.style.display = (key === 'confirmBtn' ? print : download) ? 'block' : 'none';
        });
    }

    initializeOrderActionButtons() {
        const statusMap = {
            'confirmBtn': 'confirm',
            'cancelBtn': 'cancel',
            'processingBtn': 'processing',
            'shippingBtn': 'shipping',
            'shippedBtn': 'shipped'
        };

        Object.entries(this.actionButtons).forEach(([id, button]) => {
            button.addEventListener('click', () => {
                if (id === 'cancelBtn' && !confirm('Are you sure you want to cancel the order?')) {
                    return;
                }
                this.updateOrderStatus(statusMap[id]);
            });
        });
    }

    getActionButtons() {
        return ['confirm', 'processing', 'shipping', 'shipped', 'cancel'].reduce((acc, status) => {
            acc[`${status}Btn`] = document.getElementById(`${status}Btn`);
            return acc;
        }, {});
    }

    async updateOrderStatus(status) {
        const orderId = this.getUrlParameter('orderId');
        const AuthToken = localStorage.getItem('userToken');
        const apiUrl = buildApiUrl(API_ENDPOINTS.orderUpdateStatus, { id: orderId, status });
        try {
            const response = await this.apiRequest('POST', apiUrl, {
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${AuthToken}`
                }
            });
            if (response.ok) {
                alert(`Order ${status}ed successfully`);
                this.loadOrderData();
            } else {
                throw new Error(`Failed to ${status} order`);
            }
        } catch (error) {
            console.error(`Error ${status}ing order:`, error);
            alert(error.message);
        }
    }

    getUrlParameter(name) {
        return new URLSearchParams(window.location.search).get(name);
    }

    apiRequest(method, url, options = {}) {
        return fetch(url, { method, ...options });
    }

    loadOrderData() {
        const orderId = this.getUrlParameter('orderId');
        const username = localStorage.getItem('username') || '';
        const actionEndpoint = username.toLowerCase().includes('manager')
            ? API_ENDPOINTS.orderManagerDetail
            : API_ENDPOINTS.orderDetail;
        const apiUrl = buildApiUrl(actionEndpoint, { id: orderId });
        const AuthToken = localStorage.getItem('userToken');

        return fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${AuthToken}`
            },
            credentials: 'include'
        });
    }
}

function setupEventListeners() {
    document.getElementById('mobileMenuButton').addEventListener('click', toggleMobileMenu);
    document.getElementById('darkModeToggle').addEventListener('click', toggleDarkMode);
    new OrderActions();
}

// Call setupEventListeners when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', setupEventListeners);
