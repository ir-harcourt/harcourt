<?php
$description = get_field('mid_description');
$bg_image = get_field('mid_bg_img');
?>

<section class="about-mid-section site-padding has-overlay" style="background-image:url('<?php echo $bg_image ?>');">
  <div class="absolute-overlay red-radial-gradient"> </div>
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mx-auto text-center bold" data-aos-duration='1500' data-aos='fade-down'>
          <?php echo $description ?>
        </div>
      </div>
    </div>
</section>
