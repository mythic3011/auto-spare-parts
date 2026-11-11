import { describe, expect, it } from 'vitest';
import { normalizeLoginResponse } from '../src/lib/api/client';

describe('normalizeLoginResponse', () => {
  it('supports shared envelope login response', () => {
    const result = normalizeLoginResponse({
      success: true,
      data: {
        token: 'abc',
        role: 'staff',
        permissions: ['staff:orders:read'],
        userPublicId: 'u-1'
      }
    });

    expect(result).toEqual({
      token: 'abc',
      role: 'staff',
      permissions: ['staff:orders:read'],
      userPublicId: 'u-1'
    });
  });

  it('supports legacy php login response shape', () => {
    const result = normalizeLoginResponse({
      result: {
        result: {
          role: 'Dealer',
          token: 'legacy-token',
          username: 'dealer1'
        }
      }
    });

    expect(result.role).toBe('dealer');
    expect(result.token).toBe('legacy-token');
    expect(result.userPublicId).toBe('dealer1');
    expect(result.permissions).toContain('dealer:orders:read');
  });
});


describe('v1 authentication contract', () => {
  it('preserves the public identifier returned by AuthService', () => {
    expect(normalizeLoginResponse({ success: true, data: {
      token: 'abc', role: 'sales_manager', user_public_id: 'usr-123'
    } }).userPublicId).toBe('usr-123');
  });

  it.each([
    { token: '', role: 'dealer', user_public_id: 'u-1' },
    { token: 'abc', role: 'unknown', user_public_id: 'u-1' },
    { token: 'abc', role: 'dealer' },
    { token: 'abc', role: 'dealer', user_public_id: 12 },
    { token: 'abc', role: 'dealer', user_public_id: 'u-1', permissions: ['staff:items:write'] }
  ])('rejects malformed authentication data: %o', (data) => {
    expect(() => normalizeLoginResponse({ success: true, data })).toThrow();
  });
});
