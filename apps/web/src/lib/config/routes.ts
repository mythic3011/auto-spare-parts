export const APP_ROUTES = {
  home: '/',
  login: '/login',
  dealer: {
    orders: '/dealer/orders',
    orderDetail: (orderPublicId: string) => `/dealer/orders/${encodeURIComponent(orderPublicId)}`,
    cart: '/dealer/cart',
    spareParts: '/dealer/spare-parts',
    profile: '/dealer/profile'
  },
  staff: {
    orders: '/staff/orders',
    orderDetail: (orderPublicId: string) => `/staff/orders/${encodeURIComponent(orderPublicId)}`,
    items: '/staff/items',
    newItem: '/staff/items/new',
    spareParts: '/staff/spare-parts',
    profile: '/staff/profile',
    reports: '/staff/reports'
  }
} as const;

export type NavItem = {
  label: string;
  href: string;
};
