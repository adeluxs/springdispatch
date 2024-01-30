/* 30-10-2023 */

CREATE TABLE `user_addresses` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `country_id` bigint UNSIGNED DEFAULT NULL,
  `city_id` bigint UNSIGNED DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `latitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_addresses_user_id_foreign` (`user_id`);

ALTER TABLE `user_addresses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;


CREATE TABLE `verification_codes` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verification_codes_user_id_foreign` (`user_id`);

ALTER TABLE `verification_codes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `verification_codes`
  ADD CONSTRAINT `verification_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

ALTER TABLE `users` ADD `login_type` VARCHAR(255) NULL DEFAULT NULL;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (NULL, '2023_05_22_101819_create_user_addresses_table', '9'), (NULL, '2023_06_30_054429_create_verification_codes_table', '9'), (NULL, '2023_07_06_114913_add_login_type_in_users_table', '9');

/* noting to import */