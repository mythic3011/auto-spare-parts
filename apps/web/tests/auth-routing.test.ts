import { describe, expect, it } from 'vitest';
import { resolveGuardRedirect } from '../src/lib/utils/guard';

describe('resolveGuardRedirect', () => {
  it('redirects unauthenticated users away from protected routes', () => {
    expect(resolveGuardRedirect('/dealer/orders', null)).toBe('/login');
    expect(resolveGuardRedirect('/staff/items', null)).toBe('/login');
  });

  it('keeps unauthenticated users on public routes', () => {
    expect(resolveGuardRedirect('/', null)).toBeNull();
    expect(resolveGuardRedirect('/login', null)).toBeNull();
  });

  it('redirects role mismatches to role home', () => {
    expect(resolveGuardRedirect('/dealer/orders', 'staff')).toBe('/staff/orders');
    expect(resolveGuardRedirect('/staff/orders', 'dealer')).toBe('/dealer/orders');
  });

  it('redirects logged-in users away from login page', () => {
    expect(resolveGuardRedirect('/login', 'staff')).toBe('/staff/orders');
    expect(resolveGuardRedirect('/login', 'dealer')).toBe('/dealer/orders');
  });
});
