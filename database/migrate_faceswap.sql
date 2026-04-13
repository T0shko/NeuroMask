-- ============================================================
-- Neuromax – Migration: Add source_path to jobs table
-- ============================================================
-- Run this SQL ONLY if you already have the old jobs table.
-- If setting up fresh, use neuromax.sql instead.
-- ============================================================

-- Add source_path column (required for face swap)
ALTER TABLE `jobs`
    ADD COLUMN `source_path` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Source face image (the face to swap in)'
    AFTER `user_id`;

-- Change default effect from 'cyborg' to 'faceswap'
ALTER TABLE `jobs`
    MODIFY COLUMN `effect` VARCHAR(50) NOT NULL DEFAULT 'faceswap';

-- Update subscription features text
UPDATE `subscriptions` SET `features` = '["5 face swaps/month", "Standard quality", "720p output", "Email support"]' WHERE `id` = 1;
UPDATE `subscriptions` SET `features` = '["50 face swaps/month", "HD quality", "1080p output", "Priority support", "Face login"]' WHERE `id` = 2;
UPDATE `subscriptions` SET `features` = '["Unlimited face swaps", "Max quality", "4K output", "24/7 priority support", "Face login", "API access", "Batch processing"]' WHERE `id` = 3;
