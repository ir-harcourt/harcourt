

<section class="why-features">
  <div class="container-fluid text-center red-radial-gradient">
    <div class="row">
      <?php


      if( have_rows('why_features') ):

        while ( have_rows('why_features') ) : the_row();

        $title = get_sub_field('title');
        $description = get_sub_field('description');

        ?>

        <div class="col-lg-4 site-padding <?php if ( get_row_index() === 2 ): echo 'has-overlay overlay'; endif; ?>">
          <div class="content-container">
            <h3 data-aos-duration="1500" data-aos="fade-up" class="white"><?php echo $title ?></h3>
            <p data-aos-duration="1500" data-aos="fade-up" class="mb-0"><?php echo $description ?></p>
          </div>
        </div>

        <?php

      endwhile;

    endif;

    ?>


  </div>
</div>
</section>

<?php

$title = get_field('why_config_title');

?>

<section class="why-configurator black-radial-gradient">
  <div class="container large">

    <div class="row align-items-center">

      <div data-aos-duration="1500" data-aos="fade-right" class="col-lg-5 py-4 py-lg-0">
        <h2 class="mt-0 white"><?php echo $title ?> </h2>
        <div class="icon-items">
          <?php


          if( have_rows('why_config_features') ):

            while ( have_rows('why_config_features') ) : the_row();

            $title = get_sub_field('title');
            $description = get_sub_field('description');
            $icon = get_sub_field('icon');

            ?>

            <div class="icon-item d-flex">
              <div class="image-container">
                <?php echo wp_get_attachment_image($icon, 'full') ?>
              </div>
              <div class="content-container pl-5 pl-lg-0">
                <h3 class="white mt-0"><?php echo $title ?></h3>
                <p class="mb-0"><?php echo $description ?></p>
              </div>
            </div>

            <?php

          endwhile;

        endif;

        ?>
      </div>
    </div>

    <div class="offset-md-1 col-lg-6 site-padding parts-solution">
    </div>

  </div>

</div>
</section>
