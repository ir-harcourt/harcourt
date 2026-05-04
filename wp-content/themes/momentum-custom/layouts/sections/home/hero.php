<?php

$headline = get_field('hero_headline');
$title = get_field('hero_title');
$description = get_field('hero_description');
$button = get_field('hero_button');
$image = get_field('hero_image');
$website_notification = get_field('website_notification' , 'options');
?>
<section class="home-hero <?php if($website_notification): echo "add-margin"; endif; ?>">
	<video playsinline="" autoplay="" muted="" loop="" poster="/wp-content/uploads/2023/07/first-frame-image.png" id="bgvideo" width="x" height="y">
    <source src="<?php echo get_stylesheet_directory_uri() ?>/videos/hero-video.webm" type="video/webm">
    <source src="<?php echo get_stylesheet_directory_uri() ?>/videos/hero-video.mp4" type="video/mp4">
  </video>
  <div class="background-overlay">
    <div class="container">
      <div class="row">
        <div data-aos-duration="1500" data-aos="fade-right" class="col-lg-6 hero-col-1">
          <span class="red-headline"><?php echo $headline ?></span>
          <h1><?php echo $title ?> </h1>
          <a class="standard-btn red scscpq_newuser" href="<?php echo $button['url'] ?>"><?php echo $button['title'] ?></a>
        </div>

        <div data-aos-duration="2000" data-aos="fade-left" data-aos-easing="ease-in-sine" class="col-lg-6 hero-col-2 image-half">
          <?php echo wp_get_attachment_image( $image['id'], 'full' ) ?>
        </div>
      </div>
    </div>
  </div>
</section>
