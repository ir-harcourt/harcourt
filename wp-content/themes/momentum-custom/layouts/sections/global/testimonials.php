<?php
$headline = get_field('industries_headline' , 'Options');
$bg_img = get_field('industries_bg_img' , 'Options');
?>
<section class="customers-section has-bg-img d-flex align-items-center justify-content-center" style="background-image: url(<?php echo $bg_img['url'] ?>)">
  <div class="container-fluid">
    <div class="headline">
      <h2 data-aos-duration="1500" data-aos="fade-up" class="white text-center">What Our Customers Say</h2>
    </div>
    <div class="owl-carousel customers-items text-center white">

      <?php

      $posts = get_field('testimonials');

      if( $posts ): ?>
      <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT) ?>
        <?php setup_postdata($post); ?>
        <div class="customer-item col-lg-6 mx-auto data-aos-duration="1500" data-aos="fade-up"">
          <div class="quote"><?php the_content(); ?></div>
          <div class="customer mt-3"><?php the_title(); ?></div>
        </div>
      <?php endforeach; ?>
      <?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
    <?php endif; ?>

  </div>
</div>
</section>
