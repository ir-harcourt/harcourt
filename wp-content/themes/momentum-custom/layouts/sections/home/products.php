<?php

$prefix = 'products_';
$fields = array('headline', 'title', 'description', 'btn', 'bg_img','image', 'list');

foreach ($fields as $key => $value) {
  ${$value} = get_field($prefix . $value);
}
// $image = get_field('products_image');
?>

<section class="products-section has-bg-img" style="background-image:url(<?php echo $bg_img['url'] ?>);">
  <div class="container-fluid">
    <div class="row has-overlay">
      <div class="col-lg-6 overlay image-half site-padding d-none d-lg-flex align-items-center justify-content-center">
        <div data-aos="fade-right" data-aos-duration="1000" class="image"><?php echo wp_get_attachment_image( $image, 'full'); ?></div>
      </div>
      <div class="col-lg-6 overlay content-half site-padding">
        <div data-aos="fade-left" data-aos-duration="1000" class="content">
          <span class="red-headline"><?php echo $headline ?></span>
          <h2><?php echo $title ?></h2>
          <p><?php echo $description ?></p>
          <div class="list"><?php echo $list ?></div>
          <div class="text-left">
            <a class="standard-btn red" href="<?php echo $btn['url']?>" target="<?php echo $btn['target'] ?>"><?php echo $btn['title'] ?></a>
          </div>
        </div>
      </div>
    </div>


  </div>
</section>
