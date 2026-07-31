<?php
return [
    'description' => 'Create captcha_bypass table for email addresses exempt from reCAPTCHA scoring',
    'up' => [
        "CREATE TABLE IF NOT EXISTS `captcha_bypass` (
            `id`      int(11) NOT NULL AUTO_INCREMENT,
            `email`   varchar(190) NOT NULL,
            `comment` varchar(200) DEFAULT NULL,
            `created` int(10) unsigned DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `captcha_bypass_key` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Email addresses exempt from reCAPTCHA scoring'",
    ],
    'down' => [
        "DROP TABLE IF EXISTS `captcha_bypass`",
    ],
];
