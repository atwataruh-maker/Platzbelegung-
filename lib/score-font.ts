// Decode the public font's Unicode map using its PostScript digit names.
// Unknown glyphs stay unknown: never guess a score.
export function scoreFontMap(bytes: ArrayBuffer): Record<string, string> {
  if (bytes.byteLength > 1000000) throw new Error('Score font too large');
  const v = new DataView(bytes), u16 = (p: number) => v.getUint16(p), u32 = (p: number) => v.getUint32(p);
  const tables: Record<string, number> = {};
  for (let i = 0; i < u16(4); i++) { const p = 12 + i * 16; tables[String.fromCharCode(...new Uint8Array(bytes, p, 4))] = u32(p + 8); }
  const post = tables.post, cmap = tables.cmap;
  if (!post || !cmap || u32(post) !== 0x00020000) throw new Error('Unsupported score font');
  const count = u16(post + 32), names: Record<number, string> = { 16: '-' };
  for (let i = 0; i <= 9; i++) names[19 + i] = String(i);
  const custom: string[] = [];
  let p = post + 34 + count * 2;
  while (p < bytes.byteLength && custom.length < count) { const n = v.getUint8(p++); if (p + n > bytes.byteLength) break; custom.push(new TextDecoder().decode(new Uint8Array(bytes, p, n))); p += n; }
  const digits = ['zero','one','two','three','four','five','six','seven','eight','nine'];
  const value = (glyph: number) => { if (glyph >= count) return undefined; const index = u16(post + 34 + glyph * 2); if (index < 258) return names[index]; const name = custom[index - 258]; return name === 'hyphen' ? '-' : digits.includes(name) ? String(digits.indexOf(name)) : undefined; };
  let sub = 0;
  for (let i = 0; i < u16(cmap + 2); i++) { const c = cmap + u32(cmap + 8 + i * 8); if (u16(c) === 4) { sub = c; break; } }
  if (!sub) throw new Error('Unsupported score character map');
  const n = u16(sub + 6) / 2, ends = sub + 14, starts = ends + n * 2 + 2, deltas = starts + n * 2, ranges = deltas + n * 2;
  const result: Record<string, string> = {};
  for (let i = 0; i < n; i++) {
    const start = u16(starts + i * 2), end = u16(ends + i * 2), delta = v.getInt16(deltas + i * 2), offset = u16(ranges + i * 2);
    for (let code = start; code <= end && code < 65535; code++) {
      let glyph = offset ? u16(ranges + i * 2 + offset + (code - start) * 2) : code;
      if (!offset || glyph) glyph = (glyph + delta) & 65535;
      const digit = value(glyph); if (digit !== undefined) result[String.fromCharCode(code)] = digit;
    }
  }
  if (!Object.keys(result).length) throw new Error('Empty score font');
  return result;
}
export async function loadScoreFonts(html: string) {
  const ids = [...new Set([...html.matchAll(/<span\b[^>]*data-obfuscation="([a-z0-9]+)"[^>]*class="score-(?:left|right)"/g)].map(m => m[1]))];
  if (ids.length > 8) throw new Error('Unexpected score font count');
  const results = await Promise.allSettled(ids.map(async id => {
    const response = await fetch(`https://www.fussball.de/export.fontface/-/format/ttf/id/${id}/type/font`, { redirect: 'manual', signal: AbortSignal.timeout(8000) });
    if (!response.ok) throw new Error('Score font unavailable');
    return [id, scoreFontMap(await response.arrayBuffer())] as const;
  }));
  return Object.fromEntries(results.flatMap(r => r.status === 'fulfilled' ? [r.value] : []));
}
