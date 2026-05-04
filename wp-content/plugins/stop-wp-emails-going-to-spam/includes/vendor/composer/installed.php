<?php return array(
    'root' => array(
        'name' => 'fullworks/stop-wp-emails-going-to-spam',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => 'b2f5419f6acbcc39e1983a1a71cc6ffae3aee9d6',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'alanef/free_plugin_lib' => array(
            'pretty_version' => 'v1.0.1',
            'version' => '1.0.1.0',
            'reference' => 'ed52c33c73999a1fe1ee5a1b00581b9462111ac8',
            'type' => 'library',
            'install_path' => __DIR__ . '/../alanef/free_plugin_lib',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'alanef/wp_autoloader' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => 'ab82c9014dd47efbe72cb3612c2a57715bcb212d',
            'type' => 'library',
            'install_path' => __DIR__ . '/../alanef/wp_autoloader',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
        'composer/installers' => array(
            'pretty_version' => 'v1.0.12',
            'version' => '1.0.12.0',
            'reference' => '4127333b03e8b4c08d081958548aae5419d1a2fa',
            'type' => 'composer-installer',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'fullworks/stop-wp-emails-going-to-spam' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => 'b2f5419f6acbcc39e1983a1a71cc6ffae3aee9d6',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
    ),
);
