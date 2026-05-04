<?php
/*
Template Name: About
Template Post Type: page
*/

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

get_template_part('/layouts/sections/about/hero');
get_template_part('/layouts/sections/about/advantage');
get_template_part('/layouts/sections/about/mid-section');

?>
<section class="black-radial-gradient">
<?php

get_template_part('/layouts/sections/about/community-award');
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
