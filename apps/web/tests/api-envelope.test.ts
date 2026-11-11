import { describe, expect, it } from 'vitest';
import { unwrapEnvelope } from '../src/lib/api/client';

describe('unwrapEnvelope', () => {
  it('returns data for successful envelope', () => {
    const result = unwrapEnvelope({ success: true, data: { id: 'x1' } });
    expect(result).toEqual({ id: 'x1' });
  });

  it('throws for failure envelope', () => {
    expect(() => unwrapEnvelope({ success: false, error: 'nope' })).toThrow('nope');
  });

  it('throws when success has no data', () => {
    expect(() => unwrapEnvelope({ success: true })).toThrow('API request failed');
  });
});
