// Clear stale client cart views only after a server-confirmed checkout.
for (const key of ['cart', 'cartItems', 'shoppingCart', 'cartId']) {
  localStorage.removeItem(key);
  sessionStorage.removeItem(key);
}
const destination = document.getElementById('checkoutDestination');
if (destination instanceof HTMLAnchorElement) {
  window.setTimeout(() => window.location.replace(destination.href), 1400);
}
