<?php

$list = get_field('hero_list');
$title = get_field('hero_title');
$description = get_field('hero_description');
$bg_image = get_field('bg_image');
$image = get_field('hero_image');
$headline = get_field('hero_headline');
$website_notification = get_field('website_notification' , 'options');

?>

<section class="product-hero <?php if($website_notification): echo "add-margin"; endif; ?>" style="background-image:url('<?php echo $bg_image['url'] ?>')">
  <div class="background-overlay">
    <div class="container">
      <div class="row">

        <div data-aos-duration="1500" data-aos="fade-right" class="col-lg-6 content-half">
          <div class="site-padding pr-md-5">
            <?php get_template_part('layouts/sections/global/breadcrumbs'); ?>
            <span class="red-headline mt-5"><?php echo $headline ?></span>
            <h1 class="mt-5"><?php echo $title ?></h1>
            <div class="description mt-2">
              <?php echo $description ?>
            </div>
          </div>
        </div>
        <div class="col-lg-6 image-half" data-aos-duration="1500" data-aos="fade-left">
          <?php echo wp_get_attachment_image($image, 'full') ?>
        </div>

      </div>
    </div>
  </div>
</section>


<style media="screen">
.product-hero .background-overlay { background: rgba(255,255,255,.95)}
.product-hero .row { height: 680px;}
.product-hero .image-half img { position: absolute; left: 0; top: 0; height: auto; max-width: none; }

@media only screen and ( max-width : 991px ) {
  .product-hero .content-half img {height: auto;}
  .product-hero .image-half img { display: none; }
}
</style>
