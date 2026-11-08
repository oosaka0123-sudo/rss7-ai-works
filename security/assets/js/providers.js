// Breach data providers. The demo uses DemoProvider only.
//
// Extension point: a future LiveProvider (e.g. a server-side proxy to a breach
// API) must implement the same async methods and return the same shapes. The
// UI and report layer never need to change. A LiveProvider must NEVER run in
// the browser with an API key; it would call our own server endpoint, which in
// turn requires email ownership verification (see docs/FUTURE_API.md).

import { CATALOG, PERSONAS, DEMO_COMPANY, PASSWORD_SAMPLES } from './demo-data.js';

export class DemoProvider {
  get name() { return 'demo'; }
  get isDemo() { return true; }

  /** @returns {Promise<Array>} breaches for a demo persona id */
  async breachesForPersona(personaId) {
    const persona = PERSONAS.find((p) => p.id === personaId);
    if (!persona) throw new Error('unknown demo persona');
    return persona.breaches
      .map((key) => ({ name: key, ...CATALOG[key], is_demo: true }))
      .sort((a, b) => b.breach_date.localeCompare(a.breach_date));
  }

  /** @returns {Promise<Object>} alias → breach keys for the demo company */
  async breachedAliasesForDomain(domain) {
    if (domain !== DEMO_COMPANY.domain) throw new Error('demo supports the fictional company only');
    return structuredClone(DEMO_COMPANY.aliases);
  }

  /** Fixed fictional count for a demo password sample. */
  async passwordCount(sampleId) {
    const sample = PASSWORD_SAMPLES.find((s) => s.id === sampleId);
    if (!sample) throw new Error('unknown demo password');
    return sample.count;
  }

  catalog() {
    return structuredClone(CATALOG);
  }
}

export class LiveProvider {
  constructor() {
    throw new Error('LiveProvider is not available in the demo. See docs/FUTURE_API.md.');
  }
}

export function createProvider(mode = 'demo') {
  if (mode !== 'demo') throw new Error('Only the demo provider is enabled.');
  return new DemoProvider();
}
