import { describe, expect, it, vi } from 'vitest';
import { probeApiConnection } from '../src/lib/api/connection';

describe('probeApiConnection', () => {
  it('marks API reachable when fetch returns a response', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 404
    });

    const result = await probeApiConnection(fetchMock as unknown as typeof fetch);
    expect(result.reachable).toBe(true);
    expect(result.statusCode).toBe(404);
  });

  it('marks API unreachable on network failure', async () => {
    const fetchMock = vi.fn().mockRejectedValue(new Error('connect ECONNREFUSED'));

    const result = await probeApiConnection(fetchMock as unknown as typeof fetch);
    expect(result.reachable).toBe(false);
    expect(result.message).toContain('ECONNREFUSED');
  });
});
