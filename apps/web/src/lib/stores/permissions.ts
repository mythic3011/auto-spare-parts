import { derived } from 'svelte/store';
import type { Permission } from '#lib/api/types.js';
import { currentPermissions } from './auth';

export const permissionSet = derived(currentPermissions, ($currentPermissions) => new Set($currentPermissions));

export function hasPermission(permissions: Permission[], required: Permission): boolean {
  return permissions.includes(required);
}
