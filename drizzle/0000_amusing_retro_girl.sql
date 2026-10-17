CREATE TABLE `helper_requests` (
	`id` text PRIMARY KEY NOT NULL,
	`title` text NOT NULL,
	`description` text NOT NULL,
	`category` text NOT NULL,
	`hours` real,
	`location` text DEFAULT '' NOT NULL,
	`timeframe` text DEFAULT 'nach Absprache' NOT NULL,
	`contact` text DEFAULT '' NOT NULL,
	`status` text DEFAULT 'open' NOT NULL,
	`is_example` integer DEFAULT 0 NOT NULL,
	`created_by` text NOT NULL,
	`created_at` text NOT NULL,
	`completed_at` text
);
--> statement-breakpoint
CREATE INDEX `idx_helper_requests_status_created` ON `helper_requests` (`status`,`created_at`);