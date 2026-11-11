import { derived, writable } from 'svelte/store';
import type { AuthSession } from '$lib/api/types';

type AuthState = {
  session: AuthSession | null;
  ready: boolean;
};

const AUTH_STORAGE_KEY = 'slmc.auth.session';

const initialState: AuthState = {
  session: null,
  ready: false
};

function readPersistedSession(): AuthSession | null {
  if (typeof window === 'undefined') {
    return null;
  }
  const raw = localStorage.getItem(AUTH_STORAGE_KEY);
  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw) as AuthSession;
  } catch {
    localStorage.removeItem(AUTH_STORAGE_KEY);
    return null;
  }
}

function writePersistedSession(session: AuthSession | null): void {
  if (typeof window === 'undefined') {
    return;
  }
  if (!session) {
    localStorage.removeItem(AUTH_STORAGE_KEY);
    return;
  }
  localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(session));
}

function createAuthStore() {
  const state = writable<AuthState>({
    session: readPersistedSession(),
    ready: true
  });

  return {
    subscribe: state.subscribe,
    login: (session: AuthSession) => {
      writePersistedSession(session);
      state.set({ session, ready: true });
    },
    logout: () => {
      writePersistedSession(null);
      state.set({ session: null, ready: true });
    },
    hydrate: () => {
      state.set({
        session: readPersistedSession(),
        ready: true
      });
    }
  };
}

export const authStore = createAuthStore();
export const isAuthenticated = derived(authStore, ($authStore) => $authStore.session !== null);
export const authReady = derived(authStore, ($authStore) => $authStore.ready);
export const currentRole = derived(authStore, ($authStore) => $authStore.session?.role ?? null);
export const currentPermissions = derived(authStore, ($authStore) => $authStore.session?.permissions ?? []);
