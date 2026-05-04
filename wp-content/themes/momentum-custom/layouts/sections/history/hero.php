<?php

$content = get_field('hero_content');
$bg_image = get_field('hero_bg_image');
$image = get_field('hero_image');
$website_notification = get_field('website_notification', 'options');

?>

<section class="history-hero <?php if($website_notification): echo "add-margin"; endif; ?>" style="background-image:url('<?php echo $bg_image ?>')">
  <div class="background-overlay site-padding ">
    <div class="container">
      <div class="row">

        <div data-aos-duration="1500" data-aos="fade-right" class="col-lg-6 content-half">
          <div class="pr-md-5">
            <?php get_template_part('layouts/sections/global/breadcrumbs'); ?>

            <div class="hero-content">
              <?php echo $content ?>
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
.history-hero { background-size: cover; }
.history-hero .background-overlay { background: rgba(255,255,255,.95)}
.history-hero h3 { margin-top: 2.5rem;}
@media only screen and ( max-width : 991px ) {
  .history-hero .content-half img {height: auto;}
  .history-hero .image-half img { display: none; }
}
</style>
