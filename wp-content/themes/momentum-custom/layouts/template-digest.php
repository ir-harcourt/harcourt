<?php
/*
Template Name: Digest
Template Post Type: page
*/

$website_notification = get_field('website_notification' , 'options');

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections
?>

<section class="digest-grid site-padding-bl <?php if($website_notification): echo "add-margin"; endif; ?>">
  <div class="container">
    <div class="row">
      <div class="col-lg-8">
        <div class="breadcrumb-section">
          <?php if ( function_exists('yoast_breadcrumb') ) {
            yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
          } ?>
        </div>
        <h1><?php the_title(); ?></h1>
        <?php the_content(); ?>
      </div>
    </div>
  </div>
</section>
<hr>
<section class="digest-grid site-padding">
  <div class="container">
    <?php get_template_part('/layouts/sections/global/grid-items'); ?>
  </div>
</section>

<section class="black-radial-gradient site-padding-bl">
  <?php
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
