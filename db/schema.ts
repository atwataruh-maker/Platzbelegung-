import { sqliteTable, text, real, integer, index } from 'drizzle-orm/sqlite-core';

export const helperRequests = sqliteTable('helper_requests', {
  id: text('id').primaryKey(),
  title: text('title').notNull(),
  description: text('description').notNull(),
  category: text('category').notNull(),
  hours: real('hours'),
  location: text('location').notNull().default(''),
  timeframe: text('timeframe').notNull().default('nach Absprache'),
  contact: text('contact').notNull().default(''),
  status: text('status', { enum: ['open', 'completed'] }).notNull().default('open'),
  isExample: integer('is_example').notNull().default(0),
  createdBy: text('created_by').notNull(),
  createdAt: text('created_at').notNull(),
  completedAt: text('completed_at'),
}, table => [index('idx_helper_requests_status_created').on(table.status, table.createdAt)]);

export const clubRecords = sqliteTable('club_records', {
 id: text('id').primaryKey(), kind: text('kind').notNull(), area: text('area').notNull(), title: text('title').notNull(), body: text('body').notNull().default(''), audience: text('audience').notNull().default('public'), projectId: text('project_id').notNull().default(''), status: text('status').notNull().default('Offen'), data: text('data').notNull().default('{}'), author: text('author').notNull(), createdAt: text('created_at').notNull(), updatedAt: text('updated_at').notNull(), revision: integer('revision').notNull().default(1),
}, t => [index('idx_club_records_kind_area').on(t.kind,t.area),index('idx_club_records_project').on(t.projectId)]);
export const clubProfiles = sqliteTable('club_profiles', { userId:text('user_id').primaryKey(),email:text('email').notNull(),name:text('name').notNull(),topics:text('topics').notNull().default('[]') });
export const clubAccess = sqliteTable('club_access', { email:text('email').primaryKey(),role:text('role').notNull(),areas:text('areas').notNull().default('[]') });
export const clubRegistrations = sqliteTable('club_registrations', { id:text('id').primaryKey(),recordId:text('record_id').notNull(),userId:text('user_id').notNull(),name:text('name').notNull(),email:text('email').notNull(),answers:text('answers').notNull().default('{}'),createdAt:text('created_at').notNull() },t=>[index('idx_club_registrations_record').on(t.recordId)]);
export const clubComments = sqliteTable('club_comments', { id:text('id').primaryKey(),recordId:text('record_id').notNull(),userId:text('user_id').notNull(),name:text('name').notNull(),body:text('body').notNull(),createdAt:text('created_at').notNull() },t=>[index('idx_club_comments_record').on(t.recordId)]);
export const clubFiles = sqliteTable('club_files', { id:text('id').primaryKey(),recordId:text('record_id').notNull(),name:text('name').notNull(),type:text('type').notNull(),size:integer('size').notNull(),createdAt:text('created_at').notNull() },t=>[index('idx_club_files_record').on(t.recordId)]);
export const clubPush = sqliteTable('club_push', { endpoint:text('endpoint').primaryKey(),userId:text('user_id').notNull(),subscription:text('subscription').notNull() });
export const clubSettings = sqliteTable('club_settings',{ key:text('key').primaryKey(), value:text('value').notNull() });
export const clubDeliveries = sqliteTable('club_deliveries',{ id:text('id').primaryKey(),recordId:text('record_id').notNull(),endpoint:text('endpoint').notNull(),status:text('status').notNull(),updatedAt:text('updated_at').notNull() });
