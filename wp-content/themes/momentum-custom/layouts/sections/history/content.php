<?php

$image_one = get_field('image_one');
$image_two = get_field('image_two');
$image_three = get_field('image_three');
$history_header = get_field('history_header');

?>
<section class="history-images red-radial-gradient">
  <div class="grid-bg"></div>
  <div class="container">
    <div class="row">
      <div class="col-lg-10 mx-auto mb-0 mb-lg-5">
        <h2><?php echo $history_header ?></h2>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="row">
          <div class="col-lg-6 img-col img-col-1">
            <div class="image-one image-container">
              <?php echo wp_get_attachment_image($image_one['image'], 'full') ?>
              <span data-aos-duration="1500" data-aos="fade-right" class="caption"><?php echo $image_one['caption'] ?></span>
              <span data-aos-duration="1500" data-aos="fade-right" class="arrow"><img src="/wp-content/themes/momentum-custom/images/arrow-up-white.png" alt=""></span>
            </div>
          </div>
          <div class="col-lg-6 img-col img-col-2">
            <div class="image-two image-container">
              <?php echo wp_get_attachment_image($image_two['image'], 'full') ?>
              <span data-aos-duration="1500" data-aos="fade-down" class="caption"><?php echo $image_two['caption'] ?></span>
              <span data-aos-duration="1500" data-aos="fade-down" class="arrow"><img src="/wp-content/themes/momentum-custom/images/arrow-down-white.png" alt=""></span>
            </div>
            <div class="image-three image-container">
              <?php echo wp_get_attachment_image($image_three['image'], 'full') ?>
              <span data-aos-duration="1500" data-aos="fade-left" class="caption"><?php echo $image_three['caption'] ?></span>
              <span data-aos-duration="1500" data-aos="fade-left" class="arrow"><img src="/wp-content/themes/momentum-custom/images/arrow-up-white.png" alt=""></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style media="screen">


@media only screen and ( min-width : 991px ) {
  .history-images {padding: 70px 0 170px; position: relative;}
  .history-images .grid-bg{ position: absolute; left: 0; top: 0; height: 100%; width: 100%;background-size: cover; background-image: url(/wp-content/themes/momentum-custom/images/red-banner3.jpg); background-repeat: no-repeat;}
  .history-images .image-one {padding-top: 23px;}
  .history-images .image-container img {width: 100%;}
  .history-images .image-container {position: relative;}
  .history-images .caption {position: absolute;font-size: 1.25rem;font-style: italic;}
  .history-images .image-one .caption {bottom: -130px;left: 40px;width: 70%;}
  .history-images .image-two .caption {top: -130px;left: 0;width: 82%;}
  .history-images .image-three .caption {bottom: -130px;text-align: right;right: 40px;left: auto;width: 90%;}
  .history-images .arrow {position: absolute;}
  .history-images .arrow img { width: auto}
  .history-images .image-one .arrow {bottom: -50px;left: 100px;}
  .history-images .image-two .arrow {top: -25px;left: 100px;}
  .history-images .image-three .arrow {bottom: -50px;right: 100px;left: auto;}
  .history-images .img-col {flex: 0 0 0;max-width: 100%;}
  .history-images .img-col-1 {flex-basis: 49.75%;}
  .history-images .img-col-2 {flex-basis: 42%;}
  .history-images .image-two { padding-bottom: 15px; margin-left: -15px; }
  .history-images .image-three { margin-left: -15px; }
}

@media only screen and ( max-width : 991px ) {
  .history-images .arrow { display: none}
  .history-images img { width: 100%; margin-bottom: 20px; margin-top: 60px;}
  .history-images {padding: 40px 0 40px; position: relative;}
}
</style>
