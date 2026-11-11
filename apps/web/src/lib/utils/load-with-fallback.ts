import { ENABLE_DEV_FALLBACK } from '#lib/config/runtime.js';

export type LoadWithFallbackResult<T> = {
  data: T;
  error: string;
  usedFallback: boolean;
};

export async function loadWithFallback<T>(
  loader: () => Promise<T>,
  fallbackData: T,
  failureMessage: string
): Promise<LoadWithFallbackResult<T>> {
  try {
    const data = await loader();
    return {
      data,
      error: '',
      usedFallback: false
    };
  } catch (err) {
    const reason = err instanceof Error ? err.message : failureMessage;
    if (!ENABLE_DEV_FALLBACK) {
      throw new Error(reason);
    }
    return {
      data: fallbackData,
      error: reason,
      usedFallback: true
    };
  }
}
