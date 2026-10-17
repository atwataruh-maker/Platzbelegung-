import { env } from 'cloudflare:workers';
import type { HelperRequest, RequestDraft } from './helper-contract';

export function helperStore() {
  if (!env.DB) throw new Error('Helper database unavailable');
  return new HelperStore(env.DB);
}

export class HelperStore {
  constructor(private db: D1Database) {}
  async seedExamples() {
    const query = `INSERT OR IGNORE INTO helper_requests (id,title,description,category,hours,location,timeframe,contact,status,is_example,created_by,created_at) VALUES (?,?,?,?,?,?,?,'','open',1,'example',?)`;
    await this.db.batch([
      this.db.prepare(query).bind('example-tiles','Fliesenleger gesucht','Fliesenarbeiten in den Umkleiden. Beispielgesuch aus der ersten App-Version.','Handwerk',8,'Umkleiden','nach Absprache','2026-10-03T08:44:24Z'),
      this.db.prepare(query).bind('example-events','Unterstützung bei Vereinsaktionen','Unterstützung bei der Organisation von Vereinsaktionen. Beispielgesuch aus der ersten App-Version.','Organisation',null,'Vereinsgelände','nach Absprache','2026-10-03T08:44:24Z'),
    ]);
  }
  async list() {
    return (await this.db.prepare('SELECT * FROM helper_requests ORDER BY status DESC, created_at DESC, id').all<HelperRequest>()).results;
  }
  async create(input: RequestDraft, userId: string) {
    const inserted = await this.db.prepare(`INSERT INTO helper_requests (id,title,description,category,hours,location,timeframe,contact,status,is_example,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,'open',0,?,?) ON CONFLICT(id) DO NOTHING RETURNING *`).bind(input.id,input.title,input.description,input.category,input.hours,input.location,input.timeframe,input.contact,userId,new Date().toISOString()).first<HelperRequest>();
    if (inserted) return inserted;
    const existing = await this.db.prepare('SELECT * FROM helper_requests WHERE id = ?').bind(input.id).first<HelperRequest>();
    if (existing && existing.created_by === userId && Object.entries(input).every(([key,value]) => existing[key as keyof HelperRequest] === value)) return existing;
    throw new Error('CREATE_CONFLICT');
  }
  async changeStatus(id: string, expected: string, next: string) {
    const result = await this.db.prepare('UPDATE helper_requests SET status = ?, completed_at = ? WHERE id = ? AND status = ? RETURNING *').bind(next,next === 'completed' ? new Date().toISOString() : null,id,expected).first<HelperRequest>();
    if (result) return result;
    const existing = await this.db.prepare('SELECT * FROM helper_requests WHERE id = ?').bind(id).first<HelperRequest>();
    if (!existing) throw new Error('NOT_FOUND');
    if (existing.status === next) return existing;
    throw new Error('STATUS_CONFLICT');
  }
}
