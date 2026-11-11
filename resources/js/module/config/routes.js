export const APP_ROUTES = Object.freeze({
  login: "../../index.php",
  profileSetting: "profileSetting.html",
  orderDetails: "OrderDetails.html",
});

export function buildPageUrl(path, params = {}) {
  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && String(value).trim() !== "") {
      query.set(key, String(value));
    }
  });

  const serialized = query.toString();
  return serialized ? `${path}?${serialized}` : path;
}
