<?php
return [
    'description' => 'Create user_category table for per-user category access restrictions',
    'up' => [
        "CREATE TABLE IF NOT EXISTS `user_category` (
            `id`          int(11) NOT NULL AUTO_INCREMENT,
            `user_id`     int(11) NOT NULL,
            `category_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_category_key` (`user_id`, `category_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Restricted categories per user'",
    ],
    'down' => [
        "DROP TABLE IF EXISTS `user_category`",
    ],
];
