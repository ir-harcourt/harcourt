<?php
/*
All the functions are in the PHP files in the `functions/` folder.
*/

//require get_template_directory() . '/functions/cleanup.php';
require get_template_directory() . '/functions/setup.php';
require get_template_directory() . '/functions/enqueues.php';
require get_template_directory() . '/functions/navbar.php';
require get_template_directory() . '/functions/widgets.php';
require get_template_directory() . '/functions/search-widget.php';
require get_template_directory() . '/functions/index-pagination.php';
require get_template_directory() . '/functions/single-split-pagination.php';

function location_info() {
  $html.= "<h5>Address</h5>";
  $html.= "<a href='https://goo.gl/maps/yBDg4iB6xPRKMH9s8' target='_blank'>1100 E. Whitcomb Ave. <br/>
  Madison Heights, MI 48071</a>";
  $html.= "<h5>Phone:</h5>";
  $html.= "<a href='tel:+18008885033'>(800) 888-5033 </a>";
  $html.= "<h5>Fax:</h5>";
  $html.= "<p>(866) 432-9442</p>";
  $html.= "<h5>Email:</h5>";
  $html.= "<a href='mailto:sales@harcourt.co'>sales@harcourt.co</a>";
  return $html;
}

add_shortcode( 'location', 'location_info' );
add_filter( 'widget_text', 'do_shortcode' );

/**
 * Retrieves the current year.
 *
 * @return string|false The current year or false on failure.
 */
function myprefix_current_year() {
    $year = date('Y');
    if ($year === false) {
        // Error handling: Unable to retrieve current year.
        return false;
    }
    return $year;
}

add_shortcode('year', 'myprefix_current_year');

function load_tagembed_script() {
    if (is_page(1374)) {
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var container = document.querySelector(".tagembed-container");
                if (container) {
                    var script = document.createElement("script");
                    script.src = "//widget.tagembed.com/embed.min.js";
                    script.type = "text/javascript";
                    script.async = true;
                    container.appendChild(script);
                }
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'load_tagembed_script');

// Menu item change based on user location
function get_cf_country_code() {
    return isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : null;
}

add_filter('wp_nav_menu_objects', 'filter_menu_items_by_cf_country', 10, 2);

function filter_menu_items_by_cf_country($items, $args) {
    $country_code = get_cf_country_code();

    // Optional: Restrict to a specific menu location
    if ($args->theme_location !== 'navbar') return $items;

    foreach ($items as $key => $item) {

        // Hide 'US Offer' item if not in US
        if ($item->title === 'Test' && $country_code !== 'NL') {
            unset($items[$key]);
        }
    }

    return $items;
}