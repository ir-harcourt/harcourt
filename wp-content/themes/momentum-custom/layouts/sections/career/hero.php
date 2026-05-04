<?php

$list = get_field('hero_list');
$title = get_field('hero_title');
$description = get_field('hero_description');
$bg_image = get_field('bg_image');
$image = get_field('hero_image');
$headline = get_field('hero_headline');
$additional_content = get_field('additional_content');
$website_notification = get_field('website_notification', 'options');

?>

<section class="career-hero <?php if($website_notification): echo "add-margin"; endif; ?>" style="background-image:url('<?php echo $bg_image['url'] ?>')">

  <div class="background-overlay">
    <div class="container">
      <div class="row">

        <div data-aos-duration="1500" data-aos="fade-right" class="col-lg-8 mx-auto text-center">
          <div class="site-padding">
            <?php get_template_part('layouts/sections/global/breadcrumbs'); ?>
            <span class="red-headline mt-5"><?php echo $headline ?></span>
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
.career-hero { background-size: cover; background-repeat: no-repeat}
.career-hero .background-overlay { background: rgba(255,255,255,.95)}
.career-hero .row { height: auto;}
.career-hero .image-half img { position: absolute; left: 0; top: 0; height: auto; max-width: none; }

@media only screen and ( max-width : 991px ) {
  .career-hero .content-half img {height: auto;}
  .career-hero .image-half img { display: none; }
}
</style>
