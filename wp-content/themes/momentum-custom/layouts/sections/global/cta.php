<?php
$prefix = 'cta_';
$fields = array('title', 'list', 'logo', 'bg_img', 'btn');
foreach ($fields as $key => $value) {
  ${$value} = get_field($prefix . $value, 'options');
}
?>

<section class="cta-section scscpq_newuser">

  <div class="container">

    <div class="row">

      <div class="background-image has-overlay">

        <div class="cta-content-container">

          <div data-aos-offset="400" data-aos-duration="1000" data-aos="fade-up" class="cta-content">

            <div class="heading d-flex align-items-center justify-content-center">
              <h2><?php echo $title ?></h2>
              <?php echo wp_get_attachment_image( $logo, 'full'); ?>
            </div>

            <ul class="list">

              <?php

              if( have_rows('cta_list', 'options') ):

                while ( have_rows('cta_list', 'options') ) : the_row();

                $item = get_sub_field('item');

                echo '<li>'. $item . '</li>';

              endwhile;

              else :

              endif;
              ?>

            </ul>

            <div class="text-center">
              <?php

              $link = '/request-access';

              if (is_page_template('layouts/template-request.php')) {
               $btn = 'New User Sign Up';
               $link = '#signup';
              }

              ?>
              <a class="standard-btn red" href="<?php echo $link ?>"> <?php echo $btn ?> </a>
              <!-- <button type="button" class="standard-btn red" data-toggle="modal" data-target="#formPopout">
            </button> -->
          </div>
        </div>

      </div>

      <div data-aos-duration="1000" data-aos="fade-up" class="absolute-overlay overlay-gradient"></div>

      <div class="cta-img-bg" data-aos-duration="1000" data-aos="fade-up">
        <?php echo wp_get_attachment_image( $bg_img, 'full'); ?>
      </div>

    </div>

  </div>

</div>

</section>
