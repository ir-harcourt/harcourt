<?php

$prefix = 'red_box2_';
$fields = array('title', 'description', 'link', 'image', 'btn');
foreach ($fields as $key => $value) {
  ${$value} = get_field($prefix . $value);
}
?>


<section class="tradition-section site-padding single-card">
<div class="container">
<div data-aos-duration="1000" data-aos="fade-right" class="ll-item has-overlay has-bg-img" style="background-image:url('<?php echo $image; ?>');">
  <div class="overlay">
    <a class="absolute-link" href="<?php echo $link['url']; ?>"></a>
    <div class="ll-content">
      <h3><?php echo $title ?></h3>
      <p><?php echo $description ?></p>
      <a class="standard-btn white" href="<?php echo $btn['url']?>" target="<?php echo $btn['target'] ?>"><?php echo $btn['title'] ?></a>
    </div>
  </div>
</div>
</div>
</div>
</section>
