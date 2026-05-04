<?php
/*
Template Name: Career
Template Post Type: page
*/

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

get_template_part('/layouts/sections/career/hero');

?>
<section class="black-radial-gradient">
<?php
get_template_part('/layouts/sections/career/job-listing');
get_template_part('/layouts/sections/global/cta');

?>

</section>

<?php
endwhile;

//404
else: get_template_part('/loops/index-post', 'none');

endif;


get_footer();

?>
