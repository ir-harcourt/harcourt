<?php
$h1_tag = get_sub_field('h1_tag');
$background = get_sub_field('background');
$content_above = get_sub_field('content_above');
$above_title = get_sub_field('above_title');
$above_description = get_sub_field('above_description');
$center_above_content = get_sub_field('center_above_content');
$center_text = get_sub_field('center_text');
?>

<section class="builder-block block-testimonial site-padding-60 <?php echo $background ?>">
  <div class="container">
  <?php if ( $content_above ): // content_above returned true ?> 
    <div class="above-content <?php if ( $center_above_content ): ?>text-center<?php endif;?>">
      <?php if( !empty( $above_title ) ): ?>
        <?php if ( $h1_tag ): // h1_tag returned true ?> 
          <h1><?php echo $above_title ?></h1>
        <?php else: // h1_tag returned false ?>
          <h2><?php echo $above_title ?></h2>
        <?php endif; // end of if h1_tag logic ?>
      <?php endif; ?>
      <?php if( !empty( $above_description ) ): ?>
        <?php echo $above_description ?>
      <?php endif; ?>
    </div>
    <?php endif; // end of if content_above logic ?>
    <?php if( have_rows('testimonial_slides') ): ?>
      <div class="row owl-carousel owl-theme testimonial-slider">
        <?php while( have_rows('testimonial_slides') ) : the_row(); ?>
        <?php
        $name = get_sub_field('name');
        $title_company = get_sub_field('title_company');
        $testimonial = get_sub_field('testimonial');
        ?>
        <div class="col col-12 col-lg-11<?php if ( $center_text ): ?> center-text<?php endif; ?>">
            <div class="review-icon review-left"><i class="fas fa-quote-left"></i></div>
            <div class="testimonial"><?php echo $testimonial ?></div>
            <div class="profile-container">
                <span class="test-name"><?php echo $name ?></span>
                <?php if ($title_company) : ?>
                    <span class="test-sep"> | </span>
                    <span class="test-info"><?php echo $title_company ?></span>
                <?php endif; ?>
            </div>
            <div class="review-icon review-right"><i class="fas fa-quote-right"></i></div>
        </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
</section>