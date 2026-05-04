<?php

$prefix = 'hero_';
$fields = array('title', 'description', 'bg_img', 'bg_img_right');
$website_notification = get_field('website_notification' , 'options');

foreach ($fields as $key => $value) {
  ${$value} = get_field($prefix . $value);
}

?>

<section class="about-hero <?php if($website_notification): echo "add-margin"; endif; ?>" style="background-image:url(<?php echo $bg_img ?>);">
  <div class="container-fluid background-container pr-0">
    <div class="row pr-0">
      <div class="col-lg-5"></div>
      <div class="col-lg-7 right-bg-image pr-0" style="background-image:url(<?php echo $bg_img_right ?>)"></div>
    </div>
  </div>
  <div class="container hero-content-container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <?php get_template_part('layouts/sections/global/breadcrumbs'); ?>
        <div data-aos-duration="1500" data-aos="fade-right"  class="red-radial-gradient hero-content">
          <h1><?php echo $title; ?></h1>
          <div class="column-description">
            <?php echo $description; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<style media="screen">
.about-hero { position: relative; background-size: cover;}
.about-hero .container-fluid.background-container { position: absolute; left: 0; top: 0; width: 100%; height: 840px; }
.about-hero .container-fluid.background-container .row,
.about-hero .container.hero-content-container > .row { height: 840px; z-index: 30;position: relative; }
.about-hero .hero-content { padding:80px 60px }
.about-hero .container-fluid.background-container:after {content: '';width: 100%;height: 100%;position: absolute;left: 0;top: 0;z-index: 10;background: rgba(255, 255, 255, .9);}
.about-hero .container-fluid .right-bg-image {z-index: 20;position: relative; background-size: cover;}
.about-hero .breadcrumb-section { position: absolute; top: -100px; left: 15px; }
</style>
