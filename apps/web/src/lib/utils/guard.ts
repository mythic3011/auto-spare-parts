import type { Role } from '#lib/api/types.js';
import { APP_ROUTES } from '#lib/config/routes.js';

export function roleHome(role: Role): string {
  return role === 'staff' ? APP_ROUTES.staff.orders : APP_ROUTES.dealer.orders;
}

export function resolveGuardRedirect(pathname: string, role: Role | null): string | null {
  const inDealer = pathname.startsWith('/dealer');
  const inStaff = pathname.startsWith('/staff');
  const inLogin = pathname === APP_ROUTES.login;

  if (!role) {
    if (inDealer || inStaff) {
      return APP_ROUTES.login;
    }
    return null;
  }

  if (inDealer && role !== 'dealer') {
    return roleHome(role);
  }
  if (inStaff && role !== 'staff') {
    return roleHome(role);
  }
  if (inLogin) {
    return roleHome(role);
  }

  return null;
}
