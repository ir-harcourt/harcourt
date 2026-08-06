<?php
return [
    'description' => 'Create company_category_block table for per-company category access restrictions (Design Partner Agreement)',
    'up' => [
        "CREATE TABLE IF NOT EXISTS `company_category_block` (
            `id`           int(11) NOT NULL AUTO_INCREMENT,
            `company_name` varchar(100) NOT NULL,
            `category_id`  int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `company_category_block_key` (`company_name`, `category_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Restricted categories per company (Design Partner Agreement)'",
    ],
    'down' => [
        "DROP TABLE IF EXISTS `company_category_block`",
    ],
];
