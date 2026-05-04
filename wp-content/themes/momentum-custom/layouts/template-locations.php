<?php
/*
Template Name: Locations
Template Post Type: page
*/

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

get_template_part('/layouts/sections/contact/hero');
?>

<section id="locationMap" class="site-padding" style="padding-bottom:0px;">
    <main class="container">
        <div class="map-hotspot"><?php the_field('map_shortcode'); ?></div>
    </main>
</section>

<section id="locationList" class="site-padding">
    <main class="container">
       <?php if( have_rows('location') ): ?>
       <div class="row">
        <?php while( have_rows('location') ): the_row(); ?>
            <div class="col-12 col-lg-4 location-item">
               <div class="location-container">
                    <h3><?php the_sub_field('name'); ?></h3>
                    <p><i class="fas fa-map-marker-alt"></i> <?php the_sub_field('address'); ?></p>
		   		    <p><i class="fas fa-phone"></i> <a href="tel:<?php the_sub_field('phonef'); ?>"><?php the_sub_field('phone'); ?></a></p>
                    <p><i class="fas fa-envelope"></i> <a href="mailto:<?php the_sub_field('email'); ?>"><?php the_sub_field('email'); ?></a></p>
                    <div class="text-center">
                        <a class="standard-btn red large" href="<?php the_sub_field('form_link'); ?>">Contact Us</a>
                    </div>
                </div>
           </div>
        <?php endwhile; ?>
        </div>
       <?php endif; ?>
    </main>
</section>

<!-- <section id="integratorList" class="site-padding">
    <main class="container">
      <h2>Harcourt Integrators</h2>
       <?php //if( have_rows('integrator') ): ?>
       <div class="row">
        <?php //while( have_rows('integrator') ): the_row(); ?>
            <div class="col-12 col-lg-6 integrator-item">
               <div class="integrator-container">
                    <h3><i class="fas fa-map-marker-alt"></i> <?php //the_sub_field('name'); ?></h3>
                </div>
           </div>
        <?php //endwhile; ?>
        </div>
       <?php //endif; ?>
    </main>
</section>

       --> 
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
