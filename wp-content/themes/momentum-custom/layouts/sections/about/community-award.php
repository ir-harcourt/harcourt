<?php

$prefix = 'community_';
$fields = array('headline', 'title', 'description', 'image');

foreach ($fields as $key => $value) {
  ${$value} = get_field($prefix . $value);
}

?>
<div class="site-padding">

  <div class="container">

    <div class="row community-section">

      <div class="col-lg-6 image mb-md-0 mb-4 " data-aos-duration='1500' data-aos='fade-right'>
        <?php echo wp_get_attachment_image($image, 'full') ?>
      </div>

      <div class="col-lg-6 content pl-lg-5">

        <span data-aos-duration='1500' data-aos='fade-down' class="red-headline"><?php echo $headline ?></span>
        <h2 data-aos-duration='1500' data-aos='fade-left'><?php echo $title ?></h2>
        <div data-aos-duration='1500' data-aos='fade-left' class="column-description"><?php echo $description ?></div>

      </div>
    </div>
    <?php

    $prefix = 'award_';
    $fields = array('headline', 'title', 'description', 'image');

    foreach ($fields as $key => $value) {
      ${$value} = get_field($prefix . $value);
    }

    ?>

    <div class="row mt-5 pt-5">

      <div class="col-lg-6 content pr-lg-5" data-aos-duration='1500' data-aos='fade-right'>

        <span data-aos-duration='1500' data-aos='fade-down' class="red-headline"><?php echo $headline ?></span>
        <h2 data-aos-duration='1500' data-aos='fade-right'><?php echo $title ?></h2>
        <div  data-aos-duration='1500' data-aos='fade-right' class="column-description"><?php echo $description ?></div>
        <div class="row award-section mt-5 d-none d-sm-flex">
          <div class="col-lg-12">
            <h4 data-aos-duration='1500' data-aos='fade-down'>Boeing Supplier Relations Awards</h4>
          </div>
          <?php

          if( have_rows('awards', 'options') ):

            $award_icon = get_field('award_icon', 'options');

            while ( have_rows('awards', 'options') ) : the_row();

            $label = get_sub_field('label');
            $year = get_sub_field('year');

            echo "<div class='col-sm-6 col-lg-3 d-flex award-item my-4 align-items-center'>";
            echo "<div class='award-image pr-4' data-aos-duration='1500' data-aos='fade-down'>";
            echo wp_get_attachment_image($award_icon, 'full');
            echo "</div>";

            echo "<div class='award-content'>";
            if ($label) {
              echo "<div class='award-label'>{$label}</div>";
            }
            echo "<div class='award-year'>{$year}</div>";
            echo "</div>"; // end award-content

            echo "</div>"; // end award-item

          endwhile;


        endif;

        ?>
      </div>
      </div>

      <div class="col-lg-6 image mt-md-0 mt-4 ">
        <?php echo wp_get_attachment_image($image, 'full') ?>
      </div>


    </div>


</div>
</div>
