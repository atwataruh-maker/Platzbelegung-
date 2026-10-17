import { z } from 'zod';

export const categories = ['Handwerk', 'Organisation', 'Sport & Betreuung', 'Garten & Gelände', 'Sonstiges'] as const;
export const createRequestSchema = z.object({
  id: z.string().uuid(),
  title: z.string().trim().min(3, 'Bitte einen Titel mit mindestens 3 Zeichen eingeben.').max(120),
  description: z.string().trim().min(5, 'Bitte die Aufgabe kurz beschreiben.').max(3000),
  category: z.enum(categories),
  hours: z.number().min(0.25).max(10000).nullable(),
  location: z.string().trim().max(160),
  timeframe: z.string().trim().min(1).max(160),
  contact: z.string().trim().max(200),
}).strict();
export const changeStatusSchema = z.object({
  id: z.string().min(1).max(80),
  expectedStatus: z.enum(['open', 'completed']),
  status: z.enum(['open', 'completed']),
}).strict().refine(v => v.status !== v.expectedStatus, 'Der Status ist unverändert.');
export type RequestDraft = z.infer<typeof createRequestSchema>;
export type HelperRequest = RequestDraft & { status: 'open' | 'completed'; is_example: number; created_by: string; created_at: string; completed_at: string | null };
