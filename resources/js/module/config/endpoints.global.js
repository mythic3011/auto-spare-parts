(function attachApiEndpointConfig() {
  if (typeof window === "undefined") {
    return;
  }

  window.APP_API_ENDPOINTS = Object.assign(
    {
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
    },
    window.APP_API_ENDPOINTS || {},
  );
})();
