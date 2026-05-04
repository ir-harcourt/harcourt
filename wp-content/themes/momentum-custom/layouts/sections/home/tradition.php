<?php

$prefix = 'tradition_';
$fields = array('headline', 'title', 'description', 'btn', 'image', 'btn_2');

foreach ($fields as $key => $value) {
  ${$value} = get_field($prefix . $value);
}

?>

<section class="tradition-section site-padding">
  <div class="container">
    <div class="row align-items-center ">
      <div class="col-lg-6 mb-4 mb-md-0">
        <div data-aos="fade-right" data-aos-duration="1000" class="image"><?php echo wp_get_attachment_image( $image, 'full'); ?></div>
      </div>
      <div class="col-lg-6 p-left">
        <div data-aos="fade-left" data-aos-duration="1000"  class="content">
          <span class="red-headline"><?php echo $headline ?></span>
          <h2><?php echo $title ?></h2>
          <p><?php echo $description ?></p>
          <div class="button-group text-left">
            <a class="standard-btn red" href="<?php echo $btn['url']?>" target="<?php echo $btn['target'] ?>"><?php echo $btn['title'] ?></a>
            <a class="standard-btn white" href="<?php echo $btn_2['url']?>" target="<?php echo $btn_2['target'] ?>"><?php echo $btn_2['title'] ?></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
