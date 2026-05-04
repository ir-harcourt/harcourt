<?php

$prefix = 'inno_';
$fields = array('headline', 'title', 'description', 'btn');

foreach ($fields as $key => $value) {
  ${$value} = get_field($prefix . $value);
}

?>

<section class="innovations-section black-radial-gradient site-padding">
  <div class="container">
    <div class="row">
      <div data-aos="fade-up" data-aos-duration="1000" class="col-lg-8 text-center mx-auto">
        <span class="red-headline"><?php echo $headline ?></span>
        <h2><?php echo $title ?></h2>
        <p><?php echo $description ?></p>
      </div>
    </div>
    <?php get_template_part('/layouts/sections/global/grid-items'); ?>

    <div data-aos="fade-up" data-aos-duration="1000" class="text-center">
      <a class="standard-btn red large" href="<?php echo $btn['url']?>" target="<?php echo $btn['target'] ?>"><?php echo $btn['title'] ?></a>
    </div>

  </div>
</section>
