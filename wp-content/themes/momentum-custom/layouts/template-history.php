<?php
/*
Template Name: History
Template Post Type: page
*/

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

get_template_part('/layouts/sections/history/hero');
get_template_part('/layouts/sections/history/content');
?> <section class="black-radial-gradient site-padding-bl"> <?php
get_template_part('/layouts/sections/global/cta');
?> </section> <?php
endwhile;

//404
else: get_template_part('/loops/index-post', 'none');

endif;


get_footer();

?>
