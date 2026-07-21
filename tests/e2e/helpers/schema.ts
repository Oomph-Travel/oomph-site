import { Page, expect } from '@playwright/test';

/**
 * Collect every @type declared in the page's JSON-LD.
 *
 * The plugin emits a single <script type="application/ld+json"> containing a
 * combined @graph (see plugins/oomph-travel-core/includes/class-schema.php),
 * but this walks *all* ld+json blocks and flattens nested nodes/@graph/arrays
 * so the tests stay robust if a second block is ever added.
 */
export async function getJsonLdTypes(page: Page): Promise<string[]> {
  const blocks = await page
    .locator('script[type="application/ld+json"]')
    .allTextContents();

  const types = new Set<string>();

  const collect = (node: unknown): void => {
    if (Array.isArray(node)) {
      node.forEach(collect);
      return;
    }
    if (node && typeof node === 'object') {
      const obj = node as Record<string, unknown>;
      const t = obj['@type'];
      if (typeof t === 'string') types.add(t);
      if (Array.isArray(t)) t.forEach((x) => typeof x === 'string' && types.add(x));
      // Recurse into @graph and any nested objects (subEvent, review, etc.).
      Object.values(obj).forEach(collect);
    }
  };

  for (const raw of blocks) {
    if (!raw.trim()) continue;
    try {
      collect(JSON.parse(raw));
    } catch {
      // A malformed JSON-LD block is itself a defect worth surfacing.
      throw new Error(`Invalid JSON-LD on page: ${raw.slice(0, 200)}…`);
    }
  }

  return [...types];
}

/** Assert the page's JSON-LD contains every expected @type. */
export async function expectSchemaTypes(page: Page, expected: string[]): Promise<void> {
  const found = await getJsonLdTypes(page);
  for (const type of expected) {
    expect(found, `expected JSON-LD @type "${type}" (found: ${found.join(', ')})`).toContain(type);
  }
}
