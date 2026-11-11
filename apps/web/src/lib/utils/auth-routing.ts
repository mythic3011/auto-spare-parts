import type { AuthSession } from '#lib/api/types.js';
import { APP_ROUTES } from '#lib/config/routes.js';

export function defaultRouteForRole(role: AuthSession['role']): string {
  return role === 'staff' ? APP_ROUTES.staff.orders : APP_ROUTES.dealer.orders;
}
