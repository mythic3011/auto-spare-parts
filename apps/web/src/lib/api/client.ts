import type {
  ApiEnvelope,
  AuthSession,
  LoginRequest,
  OrderDetail,
  OrderSummary,
  SparePartSummary,
  StaffItem,
  UserProfile
} from './types';
import { API_BASE_PATH, API_ENDPOINTS, buildApiUrl } from '../config/endpoints';

export function unwrapEnvelope<T>(envelope: ApiEnvelope<T>): T {
  if (envelope.success !== true || envelope.data === undefined || envelope.data === null) {
    const error = envelope.error;
    throw new Error((typeof error === 'object' && error !== null ? error.message : error) ?? envelope.message ?? 'API request failed');
  }
  return envelope.data;
}

function toRole(value: unknown): AuthSession['role'] {
  if (typeof value !== 'string') throw new Error('Invalid login role');
  switch (value.toLowerCase().replace(/[ _-]/g, '')) {
    case 'staff':
    case 'salesmanager': return 'staff';
    case 'dealer': return 'dealer';
    default: throw new Error('Invalid login role');
  }
}

function normalizePermissions(role: AuthSession['role']): AuthSession['permissions'] {
  if (role === 'staff') {
    return ['staff:items:read', 'staff:items:write', 'staff:orders:read', 'staff:orders:write', 'staff:reports:read'];
  }
  return ['dealer:spare_parts:read', 'dealer:orders:read', 'dealer:orders:write'];
}

function requireLoginString(value: unknown): string {
  if (typeof value !== 'string' || value.trim() === '') throw new Error('Invalid login response');
  return value;
}

function loginPermissions(value: unknown, role: AuthSession['role']): AuthSession['permissions'] {
  const allowed = normalizePermissions(role);
  if (value === undefined) return allowed;
  if (!Array.isArray(value) || !value.every((entry) => allowed.includes(entry))) {
    throw new Error('Invalid login permissions');
  }
  return value as AuthSession['permissions'];
}

export function normalizeLoginResponse(raw: unknown): AuthSession {
  if (raw && typeof raw === 'object' && 'success' in raw) {
    const data = unwrapEnvelope((raw as ApiEnvelope<unknown>));
    if (data && typeof data === 'object') {
      const result = data as Record<string, unknown>;
      const role = toRole(result.role);
      return {
        token: requireLoginString(result.token),
        role,
        permissions: loginPermissions(result.permissions, role),
        userPublicId: requireLoginString(result.user_public_id ?? result.userPublicId ?? result.user_id ?? result.id)
      };
    }
  }

  const envelopeResult = (raw as { result?: { result?: Record<string, unknown> } })?.result?.result;
  if (envelopeResult && typeof envelopeResult === 'object') {
    const role = toRole(envelopeResult.role);
    return {
      token: requireLoginString(envelopeResult.token),
      role,
      permissions: normalizePermissions(role),
      userPublicId: requireLoginString(envelopeResult.userPublicId ?? envelopeResult.id ?? envelopeResult.username)
    };
  }

  throw new Error('Invalid login response');
}

export type ApiClientOptions = {
  baseUrl?: string;
  fetchImpl?: typeof fetch;
  getToken?: () => string | null;
};

export function createApiClient(options: ApiClientOptions = {}) {
  const fetchImpl = options.fetchImpl ?? fetch;
  const baseUrl = options.baseUrl ?? API_BASE_PATH;

  async function request<T>(path: string, init?: RequestInit): Promise<T> {
    const headers = new Headers(init?.headers);
    headers.set('Content-Type', 'application/json');

    const token = options.getToken?.();
    if (token) {
      headers.set('Authorization', `Bearer ${token}`);
    }

    const response = await fetchImpl(buildApiUrl(path, baseUrl), {
      ...init,
      headers
    });
    const envelope = (await response.json()) as ApiEnvelope<T>;
    return unwrapEnvelope(envelope);
  }

  async function requestRaw(path: string, init?: RequestInit): Promise<unknown> {
    const headers = new Headers(init?.headers);
    headers.set('Content-Type', 'application/json');

    const token = options.getToken?.();
    if (token) {
      headers.set('Authorization', `Bearer ${token}`);
    }

    const response = await fetchImpl(buildApiUrl(path, baseUrl), {
      ...init,
      headers
    });
    return response.json();
  }

  return {
    async login(payload: LoginRequest): Promise<AuthSession> {
      const raw = await requestRaw(API_ENDPOINTS.auth.login, {
        method: 'POST',
        body: JSON.stringify({
          ...payload,
          username: payload.email
        })
      });
      return normalizeLoginResponse(raw);
    },
    getDealerOrders() {
      return request<OrderSummary[]>(API_ENDPOINTS.dealer.orders);
    },
    getDealerOrderDetail(orderPublicId: string) {
      return request<OrderDetail>(API_ENDPOINTS.dealer.orderDetail(orderPublicId));
    },
    getDealerSpareParts() {
      return request<SparePartSummary[]>(API_ENDPOINTS.dealer.spareParts);
    },
    getDealerProfile() {
      return request<UserProfile>(API_ENDPOINTS.dealer.profile);
    },
    getStaffOrders() {
      return request<OrderSummary[]>(API_ENDPOINTS.staff.orders);
    },
    getStaffOrderDetail(orderPublicId: string) {
      return request<OrderDetail>(API_ENDPOINTS.staff.orderDetail(orderPublicId));
    },
    getStaffItems() {
      return request<StaffItem[]>(API_ENDPOINTS.staff.items);
    },
    getStaffItemDetail(partPublicId: string) {
      return request<StaffItem>(API_ENDPOINTS.staff.itemDetail(partPublicId));
    },
    getStaffSpareParts() {
      return request<SparePartSummary[]>(API_ENDPOINTS.staff.spareParts);
    },
    getStaffProfile() {
      return request<UserProfile>(API_ENDPOINTS.staff.profile);
    }
  };
}
