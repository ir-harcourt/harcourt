<?php
/*
Template Name: Thank You
Template Post Type: page
*/

$website_notification = get_field('website_notification' , 'options');

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

?>
<div class="black-radial-gradient">
	
<section class="site-padding thank-section <?php if($website_notification): echo "add-margin"; endif; ?>" style="background-image:url('<?php echo get_the_post_thumbnail_url() ?>')">
  <div class="container">
    <div class="row">
      <div class="col-lg-8 mx-auto text-center">

        <h1><?php the_title(); ?></h1>
        <div class="success"><?php the_content(); ?></div>

      </div>
    </div>
  </div>
</section>

</div>
<?php

endwhile;

//404
else: get_template_part('/loops/index-post', 'none');

endif;


get_footer();

?>

<style media="screen">
/* section.site-padding.thank-section { background-size: cover; height: 800px; background-position: left bottom; background-repeat: no-repeat; } */
section.site-padding.thank-section { background-size: cover; height: 700px; background-position: left bottom; background-repeat: no-repeat; max-width: 1400px; margin: 20px auto; width: 100%; }
h1 { font-size: 3rem; display: none;}
.success { padding: 30px; background: #a3d32d3b; border: 1px solid #9bc728; border-radius: 8px; margin-top: 40px; display: none;}
.success p { margin: 0; color:#6c8e13; }
</style>
