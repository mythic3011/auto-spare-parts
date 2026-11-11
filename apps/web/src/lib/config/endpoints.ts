export const API_BASE_PATH = import.meta.env.VITE_API_BASE_PATH || '/api/v1';
export const API_ORIGIN = (import.meta.env.VITE_API_ORIGIN || '').trim();

export const API_ENDPOINTS = {
  auth: {
    login: '/auth/login'
  },
  dealer: {
    orders: '/dealer/orders',
    orderDetail: (orderPublicId: string) =>
      `/dealer/orders/${encodeURIComponent(orderPublicId)}`,
    spareParts: '/dealer/spare-parts',
    profile: '/dealer/profile'
  },
  staff: {
    orders: '/staff/orders',
    orderDetail: (orderPublicId: string) =>
      `/staff/orders/${encodeURIComponent(orderPublicId)}`,
    items: '/staff/items',
    itemDetail: (partPublicId: string) =>
      `/staff/items/${encodeURIComponent(partPublicId)}`,
    spareParts: '/staff/spare-parts',
    profile: '/staff/profile',
    reports: '/staff/reports'
  }
} as const;

function normalizePathPrefix(value: string): string {
  if (!value) {
    return '';
  }
  const withLeadingSlash = value.startsWith('/') ? value : `/${value}`;
  return withLeadingSlash.replace(/\/+$/, '');
}

function normalizeOrigin(value: string): string {
  if (!value) {
    return '';
  }
  return value.replace(/\/+$/, '');
}

export function buildApiUrl(path: string, basePath = API_BASE_PATH, origin = API_ORIGIN): string {
  const resolvedBasePath = normalizePathPrefix(basePath);
  const resolvedPath = path.startsWith('/') ? path : `/${path}`;
  const resolvedOrigin = normalizeOrigin(origin);
  return `${resolvedOrigin}${resolvedBasePath}${resolvedPath}`;
}
