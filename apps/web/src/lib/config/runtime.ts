const fallbackFlag = import.meta.env.VITE_ENABLE_DEV_FALLBACK;

export const IS_DEV = import.meta.env.DEV;
export const ENABLE_DEV_FALLBACK = IS_DEV && fallbackFlag !== 'false';
