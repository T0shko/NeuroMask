-- ============================================================
-- Neuromax – Stripe Payment Migration
-- Run this in phpMyAdmin or MySQL CLI to add Stripe support.
-- ============================================================

USE `neuromax`;

-- Add stripe_price_id to subscription plans
ALTER TABLE `subscriptions`
    ADD COLUMN IF NOT EXISTS `stripe_price_id` VARCHAR(255) DEFAULT NULL
    COMMENT 'Stripe recurring Price ID (price_xxxx from Stripe Dashboard)'
    AFTER `max_jobs`;

-- Add Stripe tracking columns to user subscriptions
ALTER TABLE `user_subscriptions`
    ADD COLUMN IF NOT EXISTS `stripe_session_id`      VARCHAR(255) DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `stripe_customer_id`     VARCHAR(255) DEFAULT NULL AFTER `stripe_session_id`,
    ADD COLUMN IF NOT EXISTS `stripe_subscription_id` VARCHAR(255) DEFAULT NULL AFTER `stripe_customer_id`;

-- ============================================================
-- ACTION REQUIRED:
-- After running this migration, set your Stripe Price IDs:
--
-- 1. Go to https://dashboard.stripe.com/products
-- 2. Create a product "Neuromax Pro"  → recurring price $19.99/mo
-- 3. Create a product "Neuromax Ultra" → recurring price $49.99/mo
-- 4. Copy the Price IDs (price_xxxx) and update the rows below:
-- ============================================================

-- UPDATE `subscriptions` SET `stripe_price_id` = 'price_YOUR_PRO_PRICE_ID'   WHERE id = 2;
-- UPDATE `subscriptions` SET `stripe_price_id` = 'price_YOUR_ULTRA_PRICE_ID' WHERE id = 3;

-- Example (replace with your real IDs before running):
-- UPDATE `subscriptions` SET `stripe_price_id` = 'price_1RExamplePro'   WHERE id = 2;
-- UPDATE `subscriptions` SET `stripe_price_id` = 'price_1RExampleUltra' WHERE id = 3;
