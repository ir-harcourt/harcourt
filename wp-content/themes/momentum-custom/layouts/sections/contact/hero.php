<?php

$title = get_field('hero_title');
$description = get_field('hero_description');
$bg_image = get_field('hero_bg_img');
$headline = get_field('hero_headline');
$website_notification = get_field('website_notification', 'options');

?>

<section class="contact-hero <?php if($website_notification): echo "add-margin"; endif; ?>" style="background-image:url('<?php echo $bg_image ?>')">
  <div class="background-overlay">
    <div class="container">
      <div class="row">
        <div data-aos-duration="1500" data-aos="fade-right" class="col-lg-8 mx-auto text-center">
          <div class="site-padding pr-md-5">
            <?php get_template_part('layouts/sections/global/breadcrumbs'); ?>
            <h1 class="mt-5"><?php echo $title ?></h1>
            <div class="description mt-2">
              <?php echo $description ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<style media="screen">
.contact-hero { background-size: cover; background-repeat: no-repeat;}
.contact-hero .background-overlay { background: rgba(255,255,255,.95)}
.contact-hero .row { height: auto;}
.contact-hero .image-half img { position: absolute; left: 0; top: 0; height: auto; max-width: none; }

@media only screen and ( max-width : 991px ) {
  .contact-hero .content-half img {height: auto;}
  .contact-hero .image-half img { display: none; }
}
</style>
