import { writable } from 'svelte/store';
import { probeApiConnection, type ApiConnectionState } from '#lib/api/connection.js';

export type ApiConnectionStoreState = {
  checking: boolean;
  result: ApiConnectionState | null;
};

function createApiConnectionStore() {
  const state = writable<ApiConnectionStoreState>({
    checking: false,
    result: null
  });

  return {
    subscribe: state.subscribe,
    async check() {
      state.update((current) => ({ ...current, checking: true }));
      const result = await probeApiConnection();
      state.set({
        checking: false,
        result
      });
    }
  };
}

export const apiConnectionStore = createApiConnectionStore();
