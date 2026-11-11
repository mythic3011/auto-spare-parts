import { get } from 'svelte/store';
import { createApiClient } from './client';
import { authStore } from '#lib/stores/auth.js';

export function createAppApiClient() {
  return createApiClient({
    getToken: () => get(authStore).session?.token ?? null
  });
}
