<?php
/*
Template Name: Why Harcourt
Template Post Type: page
*/

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

get_template_part('/layouts/sections/why/hero');
get_template_part('/layouts/sections/why/content');
get_template_part('/layouts/sections/global/testimonials');
?> <section class="black-radial-gradient site-padding-bl"> <?php
get_template_part('/layouts/sections/global/cta');
?> </section> <?php
endwhile;

//404
else: get_template_part('/loops/index-post', 'none');

endif;


get_footer();

?>

<script type="text/javascript">
jQuery('.customers-items').owlCarousel({
  loop:true,
  dots:true,
  items: 1,
})
</script>
