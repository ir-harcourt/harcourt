<?php
return [
    'description' => 'Block HBOX (category_id=18) for Spirit AeroSystems and Blue Origin via Design Partner Agreement',
    'up' => [
        "INSERT IGNORE INTO `company_category_block` (`company_name`, `category_id`) VALUES
('Spirit AeroSystems',18),
('Blue Origin',18)",
    ],
    'down' => [
        "DELETE FROM `company_category_block` WHERE `category_id` = 18 AND `company_name` IN ('Spirit AeroSystems','Blue Origin')",
    ],
];
