<?php
$headline = get_field('industries_headline' , 'Options');
$bg_img = get_field('industries_bg_img' , 'Options');
?>
<section class="industries-section has-bg-img d-flex align-items-center justify-content-center" style="background-image: url(<?php echo $bg_img['url'] ?>)">
  <div class="container-fluid">
    <div class="headline">
      <span data-aos="fade-up" data-aos-duration="1000" class="white-headline"><?php echo $headline ?></span>
    </div>
    <div class="logos">
      <div class="owl-carousel logo-carousel logo-container"  data-aos-offset="250" data-aos="fade-up" data-aos-duration="2000" >
        <?php
        if( have_rows('customer_logos' , 'Options') ):

          while ( have_rows('customer_logos' , 'Options') ) : the_row();

          $image = get_sub_field('image');

          // Display Customer Logo
          ?>
          <div class="image"><?php echo wp_get_attachment_image( $image, 'full'); ?></div>

          <?php

        endwhile;

        else :

        endif;
        ?>
      </div>
    </div>
  </div>
</section>
