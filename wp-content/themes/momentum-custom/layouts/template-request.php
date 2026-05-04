<?php
/*
Template Name: Request Access
Template Post Type: page
*/

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

get_template_part('/layouts/sections/request/hero');
get_template_part('/layouts/sections/request/content');

endwhile;

//404
else: get_template_part('/loops/index-post', 'none');

endif;


get_footer();

?>
