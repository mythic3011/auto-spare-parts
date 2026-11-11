import { API_BASE_PATH, API_ORIGIN, buildApiUrl } from '#lib/config/endpoints.js';

export type ApiConnectionState = {
  reachable: boolean;
  statusCode?: number;
  message: string;
  endpoint: string;
};

export async function probeApiConnection(fetchImpl: typeof fetch = fetch): Promise<ApiConnectionState> {
  const probePath = '/health';
  const endpoint = buildApiUrl(probePath, API_BASE_PATH, API_ORIGIN);

  try {
    const response = await fetchImpl(endpoint, {
      method: 'GET',
      headers: {
        Accept: 'application/json'
      }
    });

    return {
      reachable: true,
      statusCode: response.status,
      message: `API reachable (${response.status})`,
      endpoint
    };
  } catch (err) {
    const reason = err instanceof Error ? err.message : 'Unknown network error';
    return {
      reachable: false,
      message: `API unreachable: ${reason}`,
      endpoint
    };
  }
}
