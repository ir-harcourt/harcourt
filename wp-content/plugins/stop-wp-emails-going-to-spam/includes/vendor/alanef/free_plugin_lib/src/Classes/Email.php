<?php

namespace Fullworks_Free_Plugin_Lib\Classes;

class Email {
    private static $plugin_shortname;

    public function __construct($plugin_shortname) {
        self::$plugin_shortname = $plugin_shortname;
    }

    public function handle_optin_submission($email) {
        // Enhanced email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            return false;
        }
        
        // Prepare API endpoint with sanitization
        $api_endpoint = esc_url_raw('https://octopus.fullworksplugins.com/wp-json/fullworks-freemius-octopusmail/v2/action');
        $list_id = '4c6924da-03e8-11ef-b408-2f0724a38cbd';
        $tag = sanitize_key(self::$plugin_shortname);
        
        // Prepare request with security headers
        $response = wp_remote_post('https://octopus.fullworksplugins.com/wp-json/fullworks-freemius-octopusmail/v2/action?list=4c6924da-03e8-11ef-b408-2f0724a38cbd&tag_free=' . self::$plugin_shortname, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version'),
            ),
            'body' => wp_json_encode([
	            'type' => 'user.marketing.opted_in',
	            'plugin_id' => '1330',
	            'is_live' => true,
	            'objects' => [
		            'user' => [
			            'is_marketing_allowed' => true,
			            'email' => sanitize_email($email),
			            'first' => '',
			            'last' => '',
			            'ip' =>  Security::get_client_ip(),
			            'plugin_shortname' => self::$plugin_shortname,
			            'source' => get_site_url(),
		            ]
	            ]
            ]),
            'timeout' => 15,
            'sslverify' => true,
            'blocking' => true,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }

        return true;
    }
}
