<!-- Block One -->

<?php

$list = get_field('block_one_list');
$title = get_field('block_one_title');
$description = get_field('block_one_description');
$image = get_field('block_one_image');
$headline = get_field('block_one_headline');
$block_one = get_field('block_one');
$block_two = get_field('block_two');
$block_three = get_field('block_three');
$block_icons = get_field('block_icons');
$block_simple = get_field('block_simple');
$block_video = get_field('block_video');
$product_config = get_field('product_config');
$catalog_bar = get_field('catalog_bar');

?>

<?php if ($block_one) :?>
  <section class="product-block-one black-radial-gradient">
    <div class="container">
      <div class="row">

        <div class="col-lg-7 image-half" data-aos-duration="2500" data-aos="fade-right">
          <?php echo wp_get_attachment_image($image, 'full') ?>
        </div>

        <div data-aos-duration="1500" data-aos="fade-left" class="col-lg-5 content-half">
          <div class="site-padding-bl">
            <span class="red-headline mt-5"><?php echo $headline ?></span>
            <h1 class="mt-5"><?php echo $title ?></h1>
            <div class="description mt-2">
              <?php echo $description ?>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
<?php endif;?>

<!-- Block Simple -->


<?php if ($block_video) :?>

  <?php

  $video_embed = get_field('video_embed');

  ?>

  <section class="product-block-simple black-radial-gradient site-padding">
    <div class="container">
      <div class="row align-items-center">

        <div class="col-lg-8 mx-auto text-center">
          <?php echo $video_embed ?>
        </div>

      </div>
    </div>
  </section>

<?php endif;?>

<!-- Block configurator -->


<?php if ($product_config) :?>


  <?php

  $title = get_field('why_config_title', 214);

  ?>

  <section class="why-configurator black-radial-gradient">
    <div class="container large">

      <div class="row align-items-center">

        <div data-aos-duration="1500" data-aos="fade-right" class="col-lg-5 py-4 py-lg-0">
          <h2 class="mt-0 white"><?php echo $title ?> </h2>
          <div class="icon-items">
            <?php


            if( have_rows('why_config_features', 214) ):

              while ( have_rows('why_config_features', 214) ) : the_row();

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

<?php endif;?>

<!-- Block Simple -->


<?php if ($block_simple) :?>

  <?php

  $headline = get_field('block_simple_headline');
  $title = get_field('block_simple_title');
  $description = get_field('block_simple_description');
  $image = get_field('block_simple_image');

  ?>

  <section class="product-block-simple black-radial-gradient site-padding">
    <div class="container">
      <div class="row align-items-center">

        <div class="col-lg-7" data-aos-duration="2500" data-aos="fade-right">
          <?php echo wp_get_attachment_image($image, 'full') ?>
        </div>

        <div data-aos-duration="1500" data-aos="fade-left" class="col-lg-5 pl-5 content-half">
          <div>
            <span class="red-headline mt-5"><?php echo $headline ?></span>
            <h1 class="mt-5"><?php echo $title ?></h1>
            <div class="description mt-2">
              <?php echo $description ?>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

<?php endif;?>

<!-- Block Icons -->

<?php if ($block_icons) :?>
  <section class="product-block-one black-radial-gradient text-center site-padding">
    <div class="container-fluid ">
      <div class="row align-items-stretch">

        <?php

        if( have_rows('icon_items') ):

          while ( have_rows('icon_items') ) : the_row();

          $icon = get_sub_field('icon');
          $content = get_sub_field('content');

          $class = '';

          $current_row = get_row_index();

          if ($current_row === 1 ) {
            $class = 'first';
          }

          ?>
          <div class="icon-item col-12 col-sm-6 col-md-4 col-lg-2 d-flex flex-column <?php echo $class ?>">
            <div class="icon-container first">
              <img src="<?php echo $icon['url'] ?>" alt="<?php echo $icon['alt'] ?>">
            </div>
            <div class="content-container mt-5">
              <?php echo $content ?>
            </div>
          </div>

          <?php

        endwhile;

        else :

        endif;

        ?>


      </div>
    </div>
  </section>

  <style>

  .icon-item {border-left: 1px solid #484848;}
  .icon-item.first {border-left: 0;}
  .product-block-two { padding: 100px 0 100px; }

</style>


<?php endif;?>


<!-- Block two -->

<?php if ($block_two) :?>

  <?php $title = get_field('block_two_title'); ?>

  <section class="product-block-two">
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-center mb-5">
          <h2><?php echo $title ?></h2>
        </div>
      </div>

      <div class="row mt-5">
        <?php


        if( have_rows('block_two_items') ):

          while ( have_rows('block_two_items') ) : the_row();
          $row = get_row_index();
          $title = get_sub_field('title');
          $description = get_sub_field('description');
          $image = get_sub_field('image');
          $classes = ($row === 2 ? 'solution-item bordered' : 'solution-item' );

          ?>
          <div class="col-lg-4 solution-item <?php echo $classes ?>">
            <?php if ($image): ?>
              <div class="solution-image mb-4"><?php echo wp_get_attachment_image($image, 'full') ?></div>
            <?php endif; ?>
            <h3><?php echo $title ?></h3>
            <p><?php echo $description ?></p>
          </div>

          <?php

        endwhile;

        else :

        endif;

        ?>
      </div>
    </div>
  </section>

<?php endif;?>

<?php if ($catalog_bar) :?>

  <!-- Catalog Bar -->
  <?php get_template_part('/layouts/sections/global/catalog-bar'); ?>

<?php endif;?>

<?php if ($block_three) :?>

  <!-- Block Three -->

  <?php

  $list = get_field('block_three_list');
  $title = get_field('block_three_title');
  $image = get_field('block_three_image');

  ?>

  <section class="product-block-three">
    <div class="container">
      <div class="row">

        <div data-aos-duration="1500" data-aos="fade-left" class="col-lg-5 content-half">
          <div class="site-padding">
            <h1 class="mt-5"><?php echo $title ?></h1>
            <ul class="list hc-list mt-2">

              <?php

              if( have_rows('block_three_list') ):

                while ( have_rows('block_three_list') ) : the_row();

                $item = get_sub_field('item');

                echo '<li>'. $item . '</li>';

              endwhile;

              else :

              endif;
              ?>

            </ul>
          </div>
        </div>

        <div class="col-lg-7 image-half" data-aos-duration="2500" data-aos="fade-right">
          <?php echo wp_get_attachment_image($image, 'full') ?>
        </div>

      </div>
    </div>
  </section>
<?php endif;?>
