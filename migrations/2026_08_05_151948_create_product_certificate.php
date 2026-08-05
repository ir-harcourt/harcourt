<?php
return [
    'description' => 'Create product_certificate table for HCERT certificate data imported from CSV',
    'up' => [
        "CREATE TABLE IF NOT EXISTS `product_certificate` (
            `id`                            int(10) unsigned NOT NULL AUTO_INCREMENT,
            `sku`                           varchar(80) NOT NULL DEFAULT '',
            `manufacturing_date`            date DEFAULT NULL,
            `testing_date`                  date DEFAULT NULL,
            `product_family`                varchar(80) NOT NULL DEFAULT '',
            `product_description`           varchar(255) NOT NULL DEFAULT '',
            `product_weight`                varchar(40) NOT NULL DEFAULT '',
            `rated_load`                    varchar(40) NOT NULL DEFAULT '',
            `conversion`                    varchar(40) NOT NULL DEFAULT '',
            `test_machine`                  varchar(255) NOT NULL DEFAULT '',
            `test_machine_calibration_date` date DEFAULT NULL,
            `update_timestamp`              int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `sku_key` (`sku`),
            FULLTEXT KEY `search_key` (`sku`,`product_description`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='HCERT product certificate data (08/05/2026)'",
    ],
    'down' => [
        "DROP TABLE IF EXISTS `product_certificate`",
    ],
];
