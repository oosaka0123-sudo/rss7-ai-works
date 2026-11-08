// Password exposure check using k-anonymity.
// The password NEVER leaves the browser: only the first 5 hex characters of
// its SHA-1 hash are sent; the remaining 35 are compared locally.

export const PREFIX_LENGTH = 5;

export async function sha1Hex(text, subtle = globalThis.crypto.subtle) {
  const bytes = new TextEncoder().encode(text);
  const digest = await subtle.digest('SHA-1', bytes);
  return Array.from(new Uint8Array(digest), (b) => b.toString(16).padStart(2, '0')).join('').toUpperCase();
}

export function splitHash(hash) {
  if (!/^[0-9A-F]{40}$/.test(hash)) throw new Error('invalid SHA-1 hash');
  return { prefix: hash.slice(0, PREFIX_LENGTH), suffix: hash.slice(PREFIX_LENGTH) };
}

// Parses "SUFFIX:COUNT" lines. Padding entries (count 0) are discarded.
export function findCount(body, suffix) {
  for (const raw of body.split(/\r?\n/)) {
    const line = raw.trim();
    if (!line) continue;
    const [s, c] = line.split(':');
    if (s && s.toUpperCase() === suffix) {
      const count = parseInt(c, 10) || 0;
      return count > 0 ? count : 0;
    }
  }
  return 0;
}

// fetchRange(prefix) must return the response text for that prefix only.
export async function checkPassword(password, fetchRange, subtle) {
  if (typeof password !== 'string' || password.length === 0) {
    throw new Error('empty password');
  }
  const hash = await sha1Hex(password, subtle);
  const { prefix, suffix } = splitHash(hash);
  const body = await fetchRange(prefix);
  return { count: findCount(body, suffix), prefixSent: prefix };
}
