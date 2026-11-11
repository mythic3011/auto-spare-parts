import { writable } from 'svelte/store';
import { loadWithFallback } from '$lib/utils/load-with-fallback';

export type PageResourceState<T> = {
  loading: boolean;
  error: string;
  data: T | null;
};

export function createPageResource<T>() {
  const state = writable<PageResourceState<T>>({
    loading: true,
    error: '',
    data: null
  });

  return {
    subscribe: state.subscribe,
    async load(loader: () => Promise<T>, fallbackData: T, failureMessage: string) {
      state.set({ loading: true, error: '', data: null });
      const result = await loadWithFallback(loader, fallbackData, failureMessage);
      state.set({
        loading: false,
        error: result.usedFallback ? result.error : '',
        data: result.data
      });
    }
  };
}
