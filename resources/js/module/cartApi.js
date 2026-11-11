// // CartApi.js
// const API_URL = '../../resources/php/CartApi.php';
//
// export const CartApi = {
//     async createCart(userId) {
//         return await apiCall('POST', 'create', { userId });
//     },
//
//     async addItem(cartId, itemId, quantity) {
//         return await apiCall('POST', 'add', { cartId, itemId, quantity });
//     },
//
//     async updateQuantity(cartId, itemId, quantity) {
//         return await apiCall('POST', 'update', { cartId, itemId, quantity });
//     },
//
//     async removeItem(cartId, itemId) {
//         return await apiCall('POST', 'remove', { cartId, itemId });
//     },
//
//     async getCart(cartId) {
//         return await apiCall('GET', 'get', { cartId });
//     },
//
//     async checkStock(cartId, itemId) {
//         return await apiCall('GET', 'checkStock', { cartId, itemId });
//     },
//
//     async buyNow(cartId, itemId, quantity) {
//         return await apiCall('POST', 'buyNow', { cartId, itemId, quantity });
//     },
//
//     async clearCart(cartId) {
//         return await apiCall('POST', 'clearCart', { cartId });
//     }
// };
//
// async function apiCall(method, action, params) {
//     const url = new URL(API_URL);
//     url.searchParams.append('action', action);
//
//     const options = {
//         method: method,
//         headers: {
//             'Content-Type': 'application/json',
//         },
//     };
//
//     if (method === 'GET') {
//         Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
//     } else {
//         options.body = JSON.stringify(params);
//     }
//
//     try {
//         const response = await fetch(url, options);
//         const data = await response.json();
//
//         if (!response.ok) {
//             throw new Error(data.message || 'An error occurred');
//         }
//
//         return data;
//     } catch (error) {
//         console.error(`Error in ${action} operation:`, error);
//         throw error;
//     }
// }
