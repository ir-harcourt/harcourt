<?php
$breadcrumbs = get_sub_field('breadcrumbs');
$h1_tag = get_sub_field('h1_tag');
$background = get_sub_field('background');
$content_above = get_sub_field('content_above');
$above_title = get_sub_field('above_title');
$above_description = get_sub_field('above_description');
$center_above_content = get_sub_field('center_above_content');
?>

<section class="builder-block block-image-gallery site-padding-60 <?php echo $background ?>">
  <div class="container">
  <?php if ( $content_above ): // content_above returned true ?> 
      <?php if ( $breadcrumbs ): ?>
        <div class="breadcrumb-section">
          <?php if ( function_exists('yoast_breadcrumb') ) {
          yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
          } ?>
        </div>
      <?php endif; ?>
    <div class="above-content <?php if ( $center_above_content ): ?>text-center<?php endif;?>">
      <?php if( !empty( $above_title ) ): ?>
        <?php if ( $h1_tag ): // h1_tag returned true ?> 
          <h1><?php echo $above_title ?></h1>
        <?php else: // h1_tag returned false ?>
          <h2><?php echo $above_title ?></h2>
        <?php endif; // end of if h1_tag logic ?>
      <?php endif; ?>
      <?php if( !empty( $above_description ) ): ?>
        <p><?php echo $above_description ?></p>
      <?php endif; ?>
    </div>
    <?php endif; // end of if content_above logic ?>
    <?php if( have_rows('images') ): ?>
      <div class="row owl-carousel owl-theme image-gallery">
        <?php while( have_rows('images') ) : the_row(); ?>
        <?php
        $title = get_sub_field('title');
        $content = get_sub_field('content');
        $image = get_sub_field('image');
        ?>
        <div class="col col-12 col-lg-11">
          <div class="image"><img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" /></div>
          <h3 class="image-title"><?php echo $title ?></h3>
          <div class="description mt-2"><?php echo $content ?></div>
        </div>
        <?php endwhile; ?>
      </div>
    <?php else : ?>
<?php endif; ?>
  </div> 
  <script type="text/javascript">
    $(document).ready(function() {
      $('.owl-carousel.image-gallery').owlCarousel({
        loop : true,
        items : 1,
        dots : true,
        pullDrag : true,
        slideBy : 1,
        nav : true,
        navText : false,
        singleItem: true,
        // autoHeight: true
      });
    });
</script>
</section>