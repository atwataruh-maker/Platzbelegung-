import {apiFetch as fetch,appPath} from '@/frontend/api';
'use client';
import { useEffect, useState, type FormEvent } from 'react';
import { ArrowLeft, HandHelping, Plus, Check, RotateCcw, RefreshCw, Clock3, MapPin, UserRound, CalendarDays, LoaderCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { categories, createRequestSchema, type HelperRequest } from '@/lib/helper-contract';
import './helpers.css';

type Entry = Omit<HelperRequest, 'created_by'>;
const blank = { id: '', title: '', description: '', category: 'Handwerk', hours: '', location: '', timeframe: 'nach Absprache', contact: '' };
async function callApi<T>(method = 'GET', body?: unknown, signal?: AbortSignal): Promise<T> {
  const response = await fetch('/api/helfer', { method, cache: 'no-store', signal, headers: body ? { 'Content-Type': 'application/json' } : undefined, body: body ? JSON.stringify(body) : undefined });
  const payload: unknown = await response.json().catch(() => ({ error: 'Die Verbindung ist gerade gestört. Bitte erneut versuchen.' }));
  if (!response.ok) throw new Error(payload && typeof payload === 'object' && 'error' in payload && typeof payload.error === 'string' ? payload.error : 'Die Anfrage ist fehlgeschlagen.');
  return payload as T;
}
export default function Ehrenamt() {
  const [entries, setEntries] = useState<Entry[]>([]);
  const [canManage, setCanManage] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [tab, setTab] = useState('open');
  const [dialog, setDialog] = useState(false);
  const [draft, setDraft] = useState(blank);
  const [formError, setFormError] = useState('');
  const [saving, setSaving] = useState(false);
  const [busyId, setBusyId] = useState('');
  async function load(signal?: AbortSignal) {
    setLoading(true); setError('');
    try {
      const result = await callApi<{ records: Entry[]; canManage: boolean }>('GET', undefined, signal);
      if (!signal?.aborted) { setEntries(result.records); setCanManage(result.canManage); }
    } catch (e) { if (!signal?.aborted) setError(e instanceof Error ? e.message : 'Gesuche konnten nicht geladen werden.'); }
    finally { if (!signal?.aborted) setLoading(false); }
  }
  useEffect(() => { const controller = new AbortController(); void load(controller.signal); return () => controller.abort(); }, []);
  function openForm() {
    if (!draft.id) setDraft(v => ({ ...v, id: crypto.randomUUID() }));
    setFormError(''); setDialog(true);
  }
  async function submit(event: FormEvent) {
    event.preventDefault();
    const parsed = createRequestSchema.safeParse({ ...draft, hours: draft.hours === '' ? null : Number(draft.hours) });
    if (!parsed.success) { setFormError(parsed.error.issues[0].message); return; }
    setSaving(true); setFormError('');
    try {
      const result = await callApi<{ record: Entry }>('POST', parsed.data);
      setEntries(v => [result.record, ...v.filter(item => item.id !== result.record.id)]);
      setDraft(blank); setDialog(false); setTab('open'); setNotice('Dein Gesuch wurde gespeichert.');
    } catch (e) { setFormError(e instanceof Error ? e.message : 'Speichern fehlgeschlagen. Deine Eingaben bleiben erhalten.'); }
    finally { setSaving(false); }
  }
  async function changeStatus(entry: Entry) {
    setBusyId(entry.id); setError(''); setNotice('');
    const status = entry.status === 'open' ? 'completed' : 'open';
    try {
      const result = await callApi<{ record: Entry }>('PATCH', { id: entry.id, expectedStatus: entry.status, status });
      setEntries(v => v.map(item => item.id === entry.id ? result.record : item));
      setNotice(status === 'completed' ? 'Gesuch abgeschlossen. Du findest es unter „Abgeschlossen“.' : 'Das Gesuch ist wieder offen.');
    } catch (e) { setError(e instanceof Error ? e.message : 'Der Status konnte nicht gespeichert werden.'); }
    finally { setBusyId(''); }
  }
  const visible = entries.filter(item => item.status === tab);
  return <div className="subpage-shell">
    <header className="club-header"><div className="header-inner"><a className="club-brand" href={appPath('/')}><img className="club-logo" src="https://www.vfl-ramsdorf.de/img/VfLRamsdorf.png" alt="VfL Ramsdorf Logo"/><span className="club-name">RAMSDORF<small>Vereins-App</small></span></a><span className="header-actions"><span className="preview-badge">Version 0.10.2</span><a className="back-link" href={appPath('/')}><ArrowLeft size={16}/> Übersicht</a></span></div></header>
    <main className="vol-main helper-main">
      <div className="helper-heading"><div><span className="vol-kicker"><HandHelping size={16}/> Miteinander</span><h1>Ehrenamt & Helfen</h1><p>Eine Aufgabe. Dein Können. Unser Verein.</p></div>{canManage ? <Button className="add-request" onClick={openForm}><Plus/> Helfer suchen</Button> : <a className="admin-go" href={appPath('/admin')}>Anmelden & Gesuch erstellen →</a>}</div>
      {!loading && !canManage && <p className="helper-footnote">Zum Anlegen und Abschließen von Gesuchen bitte mit dem Vereins-Admin-Konto anmelden. Die Zugangsdaten erhältst du vom Webseiten-Admin.</p>}
      {notice && <p className="helper-success" role="status"><Check size={18}/>{notice}</p>}
      {error && <div className="helper-error" role="alert"><p>{error}</p><Button variant="outline" onClick={() => void load()} disabled={loading}>Erneut laden</Button></div>}
      <Tabs value={tab} onValueChange={setTab} className="helper-tabs">
        <div className="helper-toolbar"><TabsList aria-label="Status der Gesuche"><TabsTrigger value="open">Offen <span className="helper-count">{entries.filter(e => e.status === 'open').length}</span></TabsTrigger><TabsTrigger value="completed">Abgeschlossen <span className="helper-count">{entries.filter(e => e.status === 'completed').length}</span></TabsTrigger></TabsList><Button variant="ghost" size="icon" onClick={() => void load()} disabled={loading || Boolean(busyId)} aria-label="Gesuche aktualisieren"><RefreshCw size={18} className={loading ? 'spin' : ''}/></Button></div>
        <TabsContent value={tab}>
          {loading && entries.length === 0 ? <div className="helper-empty" role="status"><LoaderCircle className="spin"/><h2>Gesuche werden geladen</h2></div> : !error && visible.length === 0 ? <div className="helper-empty"><HandHelping size={35}/><h2>{tab === 'open' ? 'Noch keine offenen Gesuche' : 'Noch keine abgeschlossenen Gesuche'}</h2><p>{tab === 'open' ? 'Hier ist Platz für das nächste Vereinsprojekt.' : 'Erledigte Aufgaben bleiben hier für dich sichtbar.'}</p>{canManage && tab === 'open' && <Button onClick={openForm}><Plus/> Erstes Gesuch anlegen</Button>}</div> : <section className="request-list" aria-label={tab === 'open' ? 'Offene Gesuche' : 'Abgeschlossene Gesuche'}>
            {visible.map(entry => <article key={entry.id} className={'request-card helper-card ' + (entry.status === 'completed' ? 'is-completed' : 'orange')}>
              <div className="helper-card-tags"><span className="request-tag">{entry.category}</span>{entry.is_example === 1 && <span className="example-tag">Beispiel</span>}<span className={'status-tag ' + entry.status}>{entry.status === 'completed' ? 'Abgeschlossen' : 'Offen'}</span></div>
              <h3>{entry.title}</h3><p className="request-text">{entry.description}</p>
              <dl className="helper-details"><div><Clock3 size={16}/><dt>Zeitaufwand</dt><dd>{entry.hours === null ? 'nach Absprache' : entry.hours.toLocaleString('de-DE') + ' Stunden'}</dd></div><div><CalendarDays size={16}/><dt>Zeitraum</dt><dd>{entry.timeframe}</dd></div>{entry.location && <div><MapPin size={16}/><dt>Ort</dt><dd>{entry.location}</dd></div>}{entry.contact && <div><UserRound size={16}/><dt>Kontakt</dt><dd>{entry.contact}</dd></div>}</dl>
              {entry.completed_at && <p className="closed-date">Abgeschlossen am {new Date(entry.completed_at).toLocaleDateString('de-DE', { timeZone: 'Europe/Berlin' })}</p>}
              {canManage && <Button variant={entry.status === 'open' ? 'default' : 'outline'} className="status-action" disabled={Boolean(busyId)} onClick={() => void changeStatus(entry)}>{busyId === entry.id ? <LoaderCircle className="spin"/> : entry.status === 'open' ? <Check/> : <RotateCcw/>}{entry.status === 'open' ? 'Als abgeschlossen markieren' : 'Wieder öffnen'}</Button>}
            </article>)}
          </section>}
        </TabsContent>
      </Tabs>
      <p className="helper-footnote">{canManage ? 'Du verwaltest die Gesuche. Neue Einträge und Statusänderungen werden dauerhaft gespeichert.' : 'Hier findest du offene Aufgaben und bereits abgeschlossene Projekte des Vereins.'}</p>
      <footer className="footer"><a href={appPath('/')}>Zur Übersicht</a><a href="https://www.vfl-ramsdorf.de/" target="_blank" rel="noopener noreferrer">VfL-Website</a></footer>
    </main>
    <Dialog open={dialog} onOpenChange={value => { if (!saving) setDialog(value); }}>
      <DialogContent className="helper-dialog" showCloseButton={!saving} onInteractOutside={e => e.preventDefault()}>
        <DialogTitle>Helfer suchen</DialogTitle><DialogDescription>Beschreibe die Aufgabe und den gewünschten Einsatz. Mit dem Speichern erscheint das Gesuch als offen.</DialogDescription>
        <form onSubmit={submit} className="helper-form">
          <fieldset disabled={saving}><Label htmlFor="helper-title">Was wird gesucht? *</Label><Input id="helper-title" required minLength={3} maxLength={120} value={draft.title} onChange={e => setDraft(v => ({ ...v, title: e.target.value }))} placeholder="z. B. Fliesenleger für die Umkleiden"/>
          <Label htmlFor="helper-description">Beschreibung *</Label><Textarea id="helper-description" required minLength={5} maxLength={3000} rows={3} value={draft.description} onChange={e => setDraft(v => ({ ...v, description: e.target.value }))} placeholder="Was ist zu tun? Welche Erfahrung wird benötigt?"/>
          <div className="form-grid"><div><Label htmlFor="helper-category">Bereich</Label><Select value={draft.category} onValueChange={value => setDraft(v => ({ ...v, category: value }))} disabled={saving}><SelectTrigger id="helper-category"><SelectValue/></SelectTrigger><SelectContent>{categories.map(category => <SelectItem value={category} key={category}>{category}</SelectItem>)}</SelectContent></Select></div><div><Label htmlFor="helper-hours">Zeitaufwand in Stunden</Label><Input id="helper-hours" type="number" inputMode="decimal" min="0.25" max="10000" step="0.25" value={draft.hours} onChange={e => setDraft(v => ({ ...v, hours: e.target.value }))} placeholder="z. B. 8"/></div></div>
          <Label htmlFor="helper-location">Ort</Label><Input id="helper-location" maxLength={160} value={draft.location} onChange={e => setDraft(v => ({ ...v, location: e.target.value }))} placeholder="z. B. Umkleiden"/>
          <Label htmlFor="helper-timeframe">Wann? *</Label><Input id="helper-timeframe" required maxLength={160} value={draft.timeframe} onChange={e => setDraft(v => ({ ...v, timeframe: e.target.value }))}/>
          <Label htmlFor="helper-contact">Ansprechpartner / Kontakt</Label><Input id="helper-contact" maxLength={200} value={draft.contact} onChange={e => setDraft(v => ({ ...v, contact: e.target.value }))} placeholder="Name, Telefonnummer oder E-Mail"/><small>Wird zusammen mit dem Gesuch angezeigt. Nur einen für diesen Zweck freigegebenen Kontakt eintragen.</small></fieldset>
          {formError && <p className="form-error" role="alert">{formError}</p>}
          <div className="helper-form-actions"><Button type="button" variant="outline" disabled={saving} onClick={() => setDialog(false)}>Zurück</Button><Button type="submit" disabled={saving}>{saving ? <LoaderCircle className="spin"/> : <Plus/>}{saving ? 'Wird gespeichert …' : 'Gesuch speichern'}</Button></div>
        </form>
      </DialogContent>
    </Dialog>
  </div>;
}
