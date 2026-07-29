<?php
/**
 * Plugin Name: Registry reCAPTCHA - Contact Forms
 * Description: Enforces the registry table's reCAPTCHA v3 score_spam threshold on the Contact Us
 *              form family, independently of the gravityformsrecaptcha add-on (whose own site/secret
 *              keys are not configured and whose settings are encrypted). Renders its own invisible
 *              reCAPTCHA v3 widget using registry.recaptcha.site_key and verifies submissions
 *              server-side against registry.recaptcha.score_spam.
 */

// if (!defined('ABSPATH')) exit;

// define('REGISTRY_RECAPTCHA_CONTACT_FORM_IDS', array(3, 4, 5, 6, 12, 13, 14));
// define('REGISTRY_RECAPTCHA_CONTACT_ACTION', 'contact');

// function registry_recaptcha_contact_registry_value($field) {
//     global $wpdb;
//     return $wpdb->get_var($wpdb->prepare(
//         "SELECT data FROM registry WHERE domain='' AND module='recaptcha' AND field=%s AND item=''",
//         $field
//     ));
// }

// function registry_recaptcha_contact_log_blocked($email, $comment) {
//     global $wpdb;
//     $wpdb->query($wpdb->prepare(
//         "INSERT INTO log (ip, date_time, user_id, bot_id, email, type, subtype, sku, subcategory_id, comment)
//          VALUES (%s, %d, 0, 0, %s, 'Recaptcha', 'Blocked', '', 0, %s)",
//         $_SERVER['REMOTE_ADDR'] ?? '',
//         time(),
//         $email,
//         $comment
//     ));
// }

// function registry_recaptcha_contact_field_email($form) {
//     foreach ((array) rgar($form, 'fields') as $field) {
//         if ($field->type === 'email') {
//             return rgpost('input_' . $field->id);
//         }
//     }
//     return '';
// }

/**
 * Verifies a reCAPTCHA v3 token against Google using the registry table's own credentials.
 *
 * @return float|null Score (0.0-1.0), or null if the token/keys/verification are invalid.
 */
// function registry_recaptcha_contact_verify($token) {
//     if (!strlen((string) $token)) return null;

//     $secret = registry_recaptcha_contact_registry_value('private_key');
//     if (!strlen((string) $secret)) return null;

//     $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
//         'timeout' => 10,
//         'body'    => array(
//             'secret'   => $secret,
//             'response' => $token,
//             'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
//         ),
//     ));

//     if (is_wp_error($response)) return null;

//     $body = json_decode(wp_remote_retrieve_body($response));
//     if (!is_object($body) || !$body->success || !isset($body->score)) return null;
//     if (isset($body->action) && $body->action !== REGISTRY_RECAPTCHA_CONTACT_ACTION) return null;

//     return (float) $body->score;
// }

// add_action('gform_enqueue_scripts', function ($form, $is_ajax) {
//     if (!in_array($form['id'], REGISTRY_RECAPTCHA_CONTACT_FORM_IDS)) return;

//     $site_key = registry_recaptcha_contact_registry_value('site_key');
//     if (!strlen((string) $site_key)) return;

//     wp_enqueue_script(
//         'registry-recaptcha-api',
//         'https://www.google.com/recaptcha/api.js?render=' . rawurlencode($site_key),
//         array(),
//         null,
//         true
//     );

//     wp_enqueue_script(
//         'registry-recaptcha-contact',
//         content_url('mu-plugins/registry-recaptcha-contact-forms.js'),
//         array('registry-recaptcha-api'),
//         filemtime(__DIR__ . '/registry-recaptcha-contact-forms.js'),
//         true
//     );

//     wp_localize_script('registry-recaptcha-contact', 'registryRecaptchaContact', array(
//         'siteKey' => $site_key,
//         'action'  => REGISTRY_RECAPTCHA_CONTACT_ACTION,
//         'field'   => 'registry_recaptcha_response',
//     ));
// }, 10, 2);

// add_filter('gform_form_tag', function ($form_tag, $form) {
//     if (!in_array($form['id'], REGISTRY_RECAPTCHA_CONTACT_FORM_IDS)) return $form_tag;
//     return $form_tag . "<input type='hidden' class='registry_recaptcha_response' name='registry_recaptcha_response' value=''/>";
// }, 20, 2);

// add_filter('gform_validation', function ($validation_result) {
//     $form = $validation_result['form'];
//     if (!in_array($form['id'], REGISTRY_RECAPTCHA_CONTACT_FORM_IDS)) return $validation_result;

//     $score_spam = registry_recaptcha_contact_registry_value('score_spam');
//     $token      = rgpost('registry_recaptcha_response');
//     $score      = registry_recaptcha_contact_verify($token);

//     if ($score === null || ($score_spam !== null && $score <= (float) $score_spam)) {
//         $validation_result['is_valid'] = false;
//         $GLOBALS['registry_recaptcha_contact_blocked'] = true;
//         registry_recaptcha_contact_log_blocked(
//             registry_recaptcha_contact_field_email($form),
//             'reCAPTCHA score ' . ($score === null ? 'unavailable' : $score) . ' at or below spam threshold ' . $score_spam . ' (' . $form['title'] . ')'
//         );
//     }

//     $validation_result['form'] = $form;
//     return $validation_result;
// }, 10);

// add_filter('gform_validation_message', function ($message, $form) {
//     if (!in_array($form['id'], REGISTRY_RECAPTCHA_CONTACT_FORM_IDS)) return $message;
//     if (empty($GLOBALS['registry_recaptcha_contact_blocked'])) return $message;

//     return "<div class='validation_error'>We were unable to verify your submission. Please try again, or contact us directly at <a href='mailto:sales@harcourt.co'>sales@harcourt.co</a> if the issue continues.</div>";
// }, 10, 2);
