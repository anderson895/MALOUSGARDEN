-- =====================================================================
-- Malou's Garden - panel revision migration
--
-- Run ONCE after importing malousdb.sql:
--   phpMyAdmin > malousdb > Import > malousdb-revision.sql
--   or: mysql -u root malousdb < malousdb-revision.sql
--
-- Adds down payment / balance tracking, payment confirmations,
-- the customer order timeline and the customer address book
-- (address lines + GPS pin, one default address).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Orders: total, shipping fee, payment option, shipping snapshot.
--    paid_amount now means "amount confirmed as paid so far".
-- ---------------------------------------------------------------------
ALTER TABLE `tbl_payment`
  MODIFY `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  ADD `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `paid_amount`,
  ADD `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`,
  ADD `payment_option` varchar(30) NOT NULL DEFAULT 'Full Payment' AFTER `payment_method`,
  ADD `shipping_name` varchar(100) NOT NULL DEFAULT '',
  ADD `shipping_phone` varchar(50) NOT NULL DEFAULT '',
  ADD `shipping_address` text DEFAULT NULL,
  ADD `shipping_lat` decimal(10,7) DEFAULT NULL,
  ADD `shipping_lng` decimal(10,7) DEFAULT NULL;

UPDATE `tbl_payment` SET `total_amount` = `paid_amount`;

-- Shipping fee of existing online orders = order total - item lines.
UPDATE `tbl_payment` p
  JOIN (SELECT `payment_id`,
               SUM(CAST(`quantity` AS DECIMAL(10,2)) * CAST(`unit_price` AS DECIMAL(10,2))) AS items_total
          FROM `tbl_order` GROUP BY `payment_id`) o ON o.`payment_id` = p.`payment_id`
   SET p.`shipping_fee` = GREATEST(p.`total_amount` - o.`items_total`, 0)
 WHERE p.`payment_id` NOT LIKE 'TXN-%';

-- Shipping address snapshot of existing online orders.
UPDATE `tbl_payment` p
  JOIN `tbl_customer` c ON c.`cust_id` = p.`customer_id`
   SET p.`shipping_name` = c.`cust_s_name`,
       p.`shipping_phone` = c.`cust_s_phone`,
       p.`shipping_address` = CONCAT_WS(', ', NULLIF(c.`cust_s_address`, ''), NULLIF(c.`cust_s_city`, ''),
                                        NULLIF(c.`cust_s_state`, ''), NULLIF(c.`cust_s_zip`, ''))
 WHERE p.`payment_id` NOT LIKE 'TXN-%';

-- ---------------------------------------------------------------------
-- 2) Payment submissions (GCash proofs, cash collected) and their
--    confirmation by the admin.
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_payment_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` varchar(30) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `reference_no` varchar(50) NOT NULL DEFAULT '',
  `transaction_image` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `remarks` varchar(255) NOT NULL DEFAULT '',
  `submitted_at` datetime NOT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tbl_payment_history`
       (`payment_id`, `amount`, `payment_type`, `payment_method`, `transaction_image`, `status`, `submitted_at`, `confirmed_at`)
SELECT `payment_id`, `paid_amount`, 'Full Payment', `payment_method`, `transaction_image`,
       IF(`payment_status` = 'Completed', 'Confirmed', 'Pending'),
       `payment_date`, IF(`payment_status` = 'Completed', `payment_date`, NULL)
  FROM `tbl_payment`
 WHERE `payment_id` NOT LIKE 'TXN-%';

-- Online orders that were never confirmed have not been paid yet.
UPDATE `tbl_payment` SET `paid_amount` = 0
 WHERE `payment_status` <> 'Completed' AND `payment_id` NOT LIKE 'TXN-%';

-- ---------------------------------------------------------------------
-- 3) Order timeline shown to the customer.
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_order_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tbl_order_timeline` (`payment_id`, `title`, `description`, `created_at`)
SELECT `payment_id`, 'Order Placed', 'Your order has been placed.', MIN(`payment_date`)
  FROM `tbl_payment` GROUP BY `payment_id`;

INSERT INTO `tbl_order_timeline` (`payment_id`, `title`, `description`, `created_at`)
SELECT `payment_id`, 'Payment Submitted', 'Payment confirmation submitted for verification.', MIN(`payment_date`)
  FROM `tbl_payment` WHERE `payment_id` NOT LIKE 'TXN-%' GROUP BY `payment_id`;

INSERT INTO `tbl_order_timeline` (`payment_id`, `title`, `description`, `created_at`)
SELECT `payment_id`, 'Payment Confirmed', 'Payment received. Your order is fully paid.', MIN(`payment_date`)
  FROM `tbl_payment` WHERE `payment_status` = 'Completed' GROUP BY `payment_id`;

INSERT INTO `tbl_order_timeline` (`payment_id`, `title`, `description`, `created_at`)
SELECT `payment_id`, 'Preparing to Ship', 'Your order is packed and ready to ship.', MIN(`payment_date`)
  FROM `tbl_payment` WHERE `shipping_status` = 'To-Ship' GROUP BY `payment_id`;

INSERT INTO `tbl_order_timeline` (`payment_id`, `title`, `description`, `created_at`)
SELECT `payment_id`, 'Delivered', 'Your order has been delivered.', MIN(`payment_date`)
  FROM `tbl_payment` WHERE `shipping_status` = 'Completed' GROUP BY `payment_id`;

-- ---------------------------------------------------------------------
-- 4) Customer address book (address lines + GPS pin, one default).
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_customer_address` (
  `addr_id` int(11) NOT NULL AUTO_INCREMENT,
  `cust_id` int(11) NOT NULL,
  `label` varchar(30) NOT NULL DEFAULT 'Home',
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) NOT NULL DEFAULT '',
  `barangay` varchar(100) NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `province` varchar(100) NOT NULL DEFAULT '',
  `zip` varchar(30) NOT NULL DEFAULT '',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`addr_id`),
  KEY `cust_id` (`cust_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Existing shipping info becomes the default address.
INSERT INTO `tbl_customer_address`
       (`cust_id`, `label`, `full_name`, `phone`, `address_line1`, `city`, `province`, `zip`, `is_default`, `created_at`)
SELECT `cust_id`, 'Home',
       IF(`cust_s_name` <> '', `cust_s_name`, `cust_name`),
       IF(`cust_s_phone` <> '', `cust_s_phone`, `cust_phone`),
       `cust_s_address`, `cust_s_city`, `cust_s_state`, `cust_s_zip`, 1, NOW()
  FROM `tbl_customer` WHERE TRIM(`cust_s_address`) <> '';

-- Customers without shipping info: use the address from registration.
INSERT INTO `tbl_customer_address`
       (`cust_id`, `label`, `full_name`, `phone`, `address_line1`, `city`, `province`, `zip`, `is_default`, `created_at`)
SELECT `cust_id`, 'Home', `cust_name`, `cust_phone`, `cust_address`, `cust_city`, `cust_state`, `cust_zip`, 1, NOW()
  FROM `tbl_customer` WHERE TRIM(`cust_s_address`) = '' AND TRIM(`cust_address`) <> '';

UPDATE `tbl_customer`
   SET `cust_s_name` = `cust_name`, `cust_s_phone` = `cust_phone`, `cust_s_address` = `cust_address`,
       `cust_s_city` = `cust_city`, `cust_s_state` = `cust_state`, `cust_s_zip` = `cust_zip`
 WHERE TRIM(`cust_s_address`) = '' AND TRIM(`cust_address`) <> '';
