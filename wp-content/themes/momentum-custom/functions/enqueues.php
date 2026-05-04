<?php
/**!
* Enqueues
*/

if ( ! function_exists('momentumst_enqueues') ) {
	function momentumst_enqueues() {

		// wp_deregister_script('jquery');

		wp_register_script('jquery-3.3.1', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js', false, '3.3.1', false);
		wp_enqueue_script('jquery-3.3.1');

		$owl = get_field('owl_slider');

		// Styles
		wp_register_style('google-fonts-css', 'https://fonts.googleapis.com/css?family=Open+Sans:400,700|Rajdhani:400,600,700&display=swap');
		wp_enqueue_style('google-fonts-css');

		wp_register_style('bootstrap-css', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.1/css/bootstrap.min.css', false, '4.1.1', null);
		wp_enqueue_style('bootstrap-css');

		wp_register_style('aos-css', 'https://unpkg.com/aos@2.3.1/dist/aos.css', false, null);
		wp_enqueue_style('aos-css');

		if ($owl || is_home() ) {
			wp_register_style('owl-slider-css', get_template_directory_uri() . '/theme/css/owl.carousel.min.css', false, null);
			wp_enqueue_style('owl-slider-css');
		}

		wp_register_style('global-css', get_template_directory_uri() . '/theme/css/global.css', false, null);
		wp_enqueue_style('global-css');

		// Scripts
		//
		// wp_register_script('font-awesome-config-js', get_template_directory_uri() . '/theme/js/font-awesome-config.js', false, null, null);
		// wp_enqueue_script('font-awesome-config-js');


		wp_register_script('modernizr',  'https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js', false, '2.8.3', false);
		wp_enqueue_script('modernizr');


		wp_register_script('jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.10/jquery.mask.js', false, '1.14.10', false);
		wp_enqueue_script('jquery-mask');

		wp_enqueue_script("gforms_datepicker", WP_PLUGIN_URL . "/gravityforms/js/datepicker.js", "1.3.9", false);


		wp_register_script('popper',  'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js', false, '1.14.3', true);
		wp_enqueue_script('popper');

		wp_register_script('bootstrap-js', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.1/js/bootstrap.min.js', false, '4.1.1', true);
		wp_enqueue_script('bootstrap-js');


		wp_register_script('font-awesome', 'https://kit.fontawesome.com/d628ad4820.js', false, '', null);
		wp_enqueue_script('font-awesome');

		wp_register_script('aos-js', 'https://unpkg.com/aos@next/dist/aos.js', false, null, true);
		wp_enqueue_script('aos-js');

		if ($owl || is_home() || is_page_template( 'builder-template.php' ) ) {
			wp_register_script('owl-slider-js', get_template_directory_uri() . '/theme/js/owl.carousel.min.js', false, null, true);
			wp_enqueue_script('owl-slider-js');
		}

		wp_register_script('global-js', get_template_directory_uri() . '/theme/js/global.js', false, null, true);
		wp_enqueue_script('global-js');

		wp_localize_script('global-js', 'WPURLS', array( 'siteurl' => get_option('siteurl') ));


		if (is_singular() && comments_open() && get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
		}
	}
}
add_action('wp_enqueue_scripts', 'momentumst_enqueues', 200);
