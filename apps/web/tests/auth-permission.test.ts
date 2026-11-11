import { get } from 'svelte/store';
import { describe, expect, it } from 'vitest';
import { currentPermissions, currentRole, authStore, isAuthenticated } from '../src/lib/stores/auth';
import { hasPermission } from '../src/lib/stores/permissions';

describe('authStore', () => {
  it('tracks login/logout session state', () => {
    authStore.logout();
    expect(get(isAuthenticated)).toBe(false);

    authStore.login({
      token: 'token-1',
      role: 'dealer',
      permissions: ['dealer:orders:read'],
      userPublicId: 'usr_01'
    });

    expect(get(isAuthenticated)).toBe(true);
    expect(get(currentRole)).toBe('dealer');

    authStore.logout();
    expect(get(isAuthenticated)).toBe(false);
    expect(get(currentRole)).toBeNull();
  });

  it('evaluates permissions', () => {
    authStore.login({
      token: 'token-2',
      role: 'staff',
      permissions: ['staff:orders:read', 'staff:reports:read'],
      userPublicId: 'usr_02'
    });

    const permissions = get(currentPermissions);
    expect(hasPermission(permissions, 'staff:reports:read')).toBe(true);
    expect(hasPermission(permissions, 'staff:items:write')).toBe(false);
  });
});
