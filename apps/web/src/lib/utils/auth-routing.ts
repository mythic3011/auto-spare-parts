import type { AuthSession } from '$lib/api/types';
import { APP_ROUTES } from '$lib/config/routes';

export function defaultRouteForRole(role: AuthSession['role']): string {
  return role === 'staff' ? APP_ROUTES.staff.orders : APP_ROUTES.dealer.orders;
}
