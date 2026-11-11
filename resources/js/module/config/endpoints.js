export const API_ENDPOINTS = Object.freeze({
  order: "/api/orders/dealer-list",
  orderDetail: "/api/orders/dealer-get",
  orderManagerList: "/api/orders/manager-list",
  orderManagerDetail: "/api/orders/manager-get",
  orderUpdateStatus: "/api/orders/update-status",
  sparePart: "/api/spare_part/api",
  viewSparePart: "/api/spare_part/view",
  sparePartDetail: "/api/spare_part/detail",
  cart: "/api/cart/api",
  profile: "/api/profile/api",
  profileLegacy: "/resources/php/profile.php",
  login: "/api/auth/login",
  user: "/api/auth/user",
  manageItem: "/resources/php/ManageItem.php",
  report: "/resources/php/genReport.php",
});

export function buildApiUrl(baseUrl, params = {}) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && String(value).trim() !== "") {
      query.set(key, String(value));
    }
  });

  const serialized = query.toString();
  return serialized ? `${baseUrl}?${serialized}` : baseUrl;
}
