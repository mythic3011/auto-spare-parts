import { describe, expect, it } from 'vitest';
import { API_BASE_PATH, API_ENDPOINTS, buildApiUrl } from '../src/lib/config/endpoints';

describe('endpoints config', () => {
  it('uses a single API base path', () => {
    expect(API_BASE_PATH).toBe('/api/v1');
  });

  it('builds stable api urls from base + path', () => {
    expect(buildApiUrl(API_ENDPOINTS.auth.login)).toBe('/api/v1/auth/login');
    expect(buildApiUrl(API_ENDPOINTS.dealer.orders)).toBe('/api/v1/dealer/orders');
  });

  it('supports custom origin and base path', () => {
    expect(buildApiUrl('/auth/login', '/api/v1/', 'http://localhost:8880/')).toBe(
      'http://localhost:8880/api/v1/auth/login'
    );
  });

  it('encodes dynamic ids in detail routes', () => {
    expect(API_ENDPOINTS.dealer.orderDetail('order/123')).toBe('/dealer/orders/order%2F123');
    expect(API_ENDPOINTS.staff.orderDetail('x y')).toBe('/staff/orders/x%20y');
  });
});
