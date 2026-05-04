<?php
$image = get_field('advantage_image');
$headline = get_field('advantage_headline');
?>

<section class="about-advantage site-padding-bl black-radial-gradient">

  <div class="container">
    <div class="row">

      <div class="col-lg-6 pr-5">

        <?php

        echo "<span data-aos-duration='1500' data-aos='fade-down' class='red-headline'>{$headline}</span>";

        if( have_rows('advantage_items') ):

          $item = '';

          while ( have_rows('advantage_items') ) : the_row();

          // Vars
          $title = get_sub_field('title');
          $description = get_sub_field('description');

          // Build Advantage Item
          $item.= "<div class='advantage-item' data-aos-duration='1500' data-aos='fade-right'>";
          $item.= "<h2>{$title}</h2>";
          $item.= "<div class='advantage-description'>{$description}</div>";
          $item.= "</div>";


        endwhile;

        // Return Advantage Item
        echo $item;

      endif;
      ?>
    </div>

    <div class="col-lg-6 text-center" data-aos-duration='1500' data-aos='fade-left'>
      <?php echo wp_get_attachment_image($image, 'full') ?>
    </div>

  </div> <!-- end row -->
</div> <!-- end container -->

</section>
