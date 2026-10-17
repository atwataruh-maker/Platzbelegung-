CREATE TABLE `club_access` (
	`email` text PRIMARY KEY NOT NULL,
	`role` text NOT NULL,
	`areas` text DEFAULT '[]' NOT NULL
);
--> statement-breakpoint
CREATE TABLE `club_comments` (
	`id` text PRIMARY KEY NOT NULL,
	`record_id` text NOT NULL,
	`user_id` text NOT NULL,
	`name` text NOT NULL,
	`body` text NOT NULL,
	`created_at` text NOT NULL
);
--> statement-breakpoint
CREATE INDEX `idx_club_comments_record` ON `club_comments` (`record_id`);--> statement-breakpoint
CREATE TABLE `club_deliveries` (
	`id` text PRIMARY KEY NOT NULL,
	`record_id` text NOT NULL,
	`endpoint` text NOT NULL,
	`status` text NOT NULL,
	`updated_at` text NOT NULL
);
--> statement-breakpoint
CREATE TABLE `club_files` (
	`id` text PRIMARY KEY NOT NULL,
	`record_id` text NOT NULL,
	`name` text NOT NULL,
	`type` text NOT NULL,
	`size` integer NOT NULL,
	`created_at` text NOT NULL
);
--> statement-breakpoint
CREATE INDEX `idx_club_files_record` ON `club_files` (`record_id`);--> statement-breakpoint
CREATE TABLE `club_profiles` (
	`user_id` text PRIMARY KEY NOT NULL,
	`email` text NOT NULL,
	`name` text NOT NULL,
	`topics` text DEFAULT '[]' NOT NULL
);
--> statement-breakpoint
CREATE TABLE `club_push` (
	`endpoint` text PRIMARY KEY NOT NULL,
	`user_id` text NOT NULL,
	`subscription` text NOT NULL
);
--> statement-breakpoint
CREATE TABLE `club_records` (
	`id` text PRIMARY KEY NOT NULL,
	`kind` text NOT NULL,
	`area` text NOT NULL,
	`title` text NOT NULL,
	`body` text DEFAULT '' NOT NULL,
	`audience` text DEFAULT 'public' NOT NULL,
	`project_id` text DEFAULT '' NOT NULL,
	`status` text DEFAULT 'Offen' NOT NULL,
	`data` text DEFAULT '{}' NOT NULL,
	`author` text NOT NULL,
	`created_at` text NOT NULL,
	`updated_at` text NOT NULL,
	`revision` integer DEFAULT 1 NOT NULL
);
--> statement-breakpoint
CREATE INDEX `idx_club_records_kind_area` ON `club_records` (`kind`,`area`);--> statement-breakpoint
CREATE INDEX `idx_club_records_project` ON `club_records` (`project_id`);--> statement-breakpoint
CREATE TABLE `club_registrations` (
	`id` text PRIMARY KEY NOT NULL,
	`record_id` text NOT NULL,
	`user_id` text NOT NULL,
	`name` text NOT NULL,
	`email` text NOT NULL,
	`answers` text DEFAULT '{}' NOT NULL,
	`created_at` text NOT NULL
);
--> statement-breakpoint
CREATE INDEX `idx_club_registrations_record` ON `club_registrations` (`record_id`);--> statement-breakpoint
CREATE TABLE `club_settings` (
	`key` text PRIMARY KEY NOT NULL,
	`value` text NOT NULL
);
