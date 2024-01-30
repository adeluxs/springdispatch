/* 20-05-2023 */
/*
--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `users` ADD `otp_verify_at` timestamp NULL DEFAULT NULL;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (NULL, '2023_05_05_111042_create_settings_table', '8'), (NULL, '2023_05_19_100843_add_otp_verify_at_in_users_table', '8');
*/
/* 31-01-2023 */
/*
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'all,city_wise',
  `size` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `orders` ADD `vehicle_id` bigint unsigned DEFAULT NULL AFTER `total_parcel`, ADD `vehicle_data` json DEFAULT NULL AFTER `vehicle_id`; 

ALTER TABLE `app_settings` ADD `is_vehicle_in_order` TINYINT NULL DEFAULT '0' AFTER `currency_position`;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (NULL, '2023_01_30_121805_create_vehicles_table', '7'), (NULL, '2023_01_30_131633_add_is_vehicle_in_order_in_app_settings_table', '7'), (NULL, '2023_01_30_132224_add_vehicle_data_in_orders_table', '7');
*/
/* noting to import */
/* 06-01-2023 */
/*
ALTER TABLE `cities` ADD `commission_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'fixed, percentage',
    ADD `admin_commission` double NULL DEFAULT '0'; 

ALTER TABLE `payments` ADD `cancel_charges` double NULL DEFAULT '0',
    ADD `admin_commission` double NULL DEFAULT '0',
    ADD `delivery_man_commission` double NULL DEFAULT '0',
    ADD `received_by` VARCHAR(255) NULL DEFAULT NULL,
    ADD `delivery_man_fee` double NULL DEFAULT '0',
    ADD `delivery_man_tip` double NULL DEFAULT '0';

-- --------------------------------------------------------

--
-- Table structure for table `user_bank_accounts`
--

CREATE TABLE `user_bank_accounts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_holder_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `total_amount` double DEFAULT '0',
  `online_received` double DEFAULT '0',
  `collected_cash` double DEFAULT '0',
  `manual_received` double DEFAULT '0',
  `total_withdrawn` double DEFAULT '0',
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_histories`
--

CREATE TABLE `wallet_histories` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'credit,debit',
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'topup,withdraw,order_fee,admin_commision,correction',
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` double DEFAULT '0',
  `balance` double DEFAULT '0',
  `datetime` datetime DEFAULT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `data` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdraw_requests`
--

CREATE TABLE `withdraw_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `amount` double DEFAULT '0',
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'requested' COMMENT 'requested,approved,decline',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (NULL, '2022_12_05_111707_alter_cities_table', '6'), (NULL, '2022_12_05_140929_create_wallets_table', '6'), (NULL, '2022_12_05_140954_create_wallet_histories_table', '6'), (NULL, '2022_12_05_141107_create_user_bank_accounts_table', '6'), (NULL, '2022_12_06_061753_alter_payments_table', '6'), (NULL, '2022_12_10_054128_create_withdraw_requests_table', '6');

--
-- Indexes for table `user_bank_accounts`
--
ALTER TABLE `user_bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_bank_accounts_user_id_foreign` (`user_id`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wallets_user_id_foreign` (`user_id`);

--
-- Indexes for table `wallet_histories`
--
ALTER TABLE `wallet_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wallet_histories_user_id_foreign` (`user_id`),
  ADD KEY `wallet_histories_order_id_foreign` (`order_id`);

--
-- Indexes for table `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `withdraw_requests_user_id_foreign` (`user_id`);


--
-- AUTO_INCREMENT for table `user_bank_accounts`
--
ALTER TABLE `user_bank_accounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_histories`
--
ALTER TABLE `wallet_histories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for table `user_bank_accounts`
--
ALTER TABLE `user_bank_accounts`
  ADD CONSTRAINT `user_bank_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_histories`
--
ALTER TABLE `wallet_histories`
  ADD CONSTRAINT `wallet_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wallet_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  ADD CONSTRAINT `withdraw_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
*/

/* 20-07-22 */
/*
ALTER TABLE `app_settings` ADD `otp_verify_on_pickup_delivery` TINYINT NULL DEFAULT '1' AFTER `distance`,
                           ADD `currency` VARCHAR(255) NULL DEFAULT NULL AFTER `otp_verify_on_pickup_delivery`,
                           ADD `currency_code` VARCHAR(255) NULL DEFAULT NULL AFTER `currency`,
                           ADD `currency_position` VARCHAR(255) NULL DEFAULT NULL AFTER `currency_code`;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (NULL, '2022_06_27_131039_add_otp_verify_on_pickup_delivery', '5');
*/
/* 23-06-22 */
/* noting to import */
/* 11-06-20222 */
/*
ALTER TABLE `users` ADD `fcm_token` TEXT NULL DEFAULT NULL AFTER `uid`; 

ALTER TABLE `orders` ADD `auto_assign` TINYINT NULL DEFAULT NULL AFTER `total_parcel`,
                     ADD `cancelled_delivery_man_ids` TEXT NULL DEFAULT NULL AFTER `auto_assign`; 

ALTER TABLE `app_settings` ADD `auto_assign` TINYINT NULL DEFAULT '0' AFTER `notification_settings`,
                           ADD `distance_unit` VARCHAR(255) NULL DEFAULT NULL COMMENT 'km, mile' AFTER `auto_assign`,
                           ADD `distance` DOUBLE NULL DEFAULT '0' AFTER `distance_unit`;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (NULL, '2022_05_30_063501_add_fcm_token_to_users_table', '4'), (NULL, '2022_05_31_101332_add_auto_assign_to_orders', '4'), (NULL, '2022_06_02_065520_add_distance_to_app_settings', '4');
*/
/* 23-05-20222*/
/*
ALTER TABLE `orders` ADD `total_parcel` DOUBLE NULL DEFAULT NULL AFTER `pickup_confirm_by_delivery_man`; 

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (NULL, '2022_05_11_080007_add_total_parcel_orders_table', '3');
*/


/* 20-04-20222*/
/*Table structure for table `documents` */
/*
DROP TABLE IF EXISTS `documents`;

CREATE TABLE `documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `is_required` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/
/*Data for the table `documents` */

/*Table structure for table `delivery_man_documents` */
/*
DROP TABLE IF EXISTS `delivery_man_documents`;

CREATE TABLE `delivery_man_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `document_id` bigint unsigned DEFAULT NULL,
  `is_verified` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_man_documents_document_id_foreign` (`document_id`),
  KEY `delivery_man_documents_delivery_man_id_foreign` (`delivery_man_id`),
  CONSTRAINT `delivery_man_documents_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_man_documents_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/
/*Data for the table `delivery_man_documents` */
/*
insert  into `migrations`(`id`,`migration`,`batch`) values (18,'2022_04_14_084202_create_documents_table',2),(19,'2022_04_14_084351_create_delivery_man_documents_table',2);
*/