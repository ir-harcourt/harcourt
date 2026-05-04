<?php
/*
Template Name: scscpq-dashboard
Template Post Type: page
*/

$website_notification = get_field('website_notification' , 'options');

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

?>

    <div <?php if ($website_notification): ?>class=add-margin<?php endif; ?> id=scscpq_page>dashboard</div>


<?php


endwhile;

//404
else: get_template_part('/loops/index-post', 'none');

endif;


get_footer();

?>
