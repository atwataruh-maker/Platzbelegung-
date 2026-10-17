/** Read the active supplied season plan first. The identical bundled copy is a fallback. */
export async function loadPlanBytes(signal: AbortSignal): Promise<{ bytes: ArrayBuffer; isSnapshot: boolean }> {
  for (const [index, url] of ['/api/plan', '/plan-snapshot.pdf'].entries()) {
    try {
      const response = await fetch(url, { cache: 'no-store', signal });
      if (!response.ok) throw new Error(`Plan HTTP ${response.status}`);
      const bytes = await response.arrayBuffer();
      if (bytes.byteLength > 8_000_000 || new TextDecoder().decode(bytes.slice(0, 5)) !== '%PDF-') throw new Error('Invalid PDF');
      return { bytes, isSnapshot: index === 1 };
    } catch {
      if (signal.aborted) throw new DOMException('Aborted', 'AbortError');
    }
  }
  throw new Error('Auch die gesicherte Kopie konnte nicht geladen werden. Bitte prüfe deine Internetverbindung oder öffne das Original.');
}
