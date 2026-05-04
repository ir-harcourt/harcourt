<?php function build_content($content_side, $prefix, $background) {

  $prefix = $prefix . '_';
  $fields = array('headline', 'title', 'description', 'image');

  foreach ($fields as $key => $value) {
    ${$value} = get_field($prefix . $value);
  }

  $html = '';

  $html.= "<section class='content-section site-padding {$content_side} {$background}'>";
  $html.= "<div class='container-fluid'>";

  $html.= "<div class='row align-items-center'>";

  $html.= "<div class='col-lg-6 content-half' data-aos-duration='1500' data-aos='fade-{$content_side}'>";
  $html.= "<div class='content-container'>";
  $html.= "<span class='red-headline'>{$headline}</span>";
  $html.= "<h2>{$title}</h2>";
  $html.= "<p>{$description}</p>";
  $html.= "</div>";
  $html.= "</div>";

  $html.= "<div class='col-lg-6 image-half' data-aos-duration='1500' data-aos='fade-{$content_side}'>";
  $html.= wp_get_attachment_image($image, 'full');
  $html.= "</div>";

  $html.= "</div>";

  $html.= "</div>";
  $html.= "</section>";

  return $html;

}

echo build_content('right', 'block_one', 'black-radial-gradient');
get_template_part('/layouts/sections/global/catalog-bar');
echo build_content('left', 'block_two', 'light');
?>
<section class="black-radial-gradient">

  <?php
  echo build_content('right', 'block_three', '');
  get_template_part('/layouts/sections/global/cta');

  ?>

</section>
