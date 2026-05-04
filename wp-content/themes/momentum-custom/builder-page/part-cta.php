<?php
$custom_cta = get_sub_field('custom_cta');
$background = get_sub_field('background');
$subtitle = get_sub_field('subtitle');
$title = get_sub_field('title');
$description = get_sub_field('description');
$link = get_sub_field('link');
?>

<?php if ( $custom_cta ): // custom_cta returned true ?> 
  <section class="builder-block block-call-to-action site-padding-60 <?php echo $background ?>" style="padding: 80px 15px;">
    <div class="container">
      <div class="row">
        <div class="cta-content-container">
          <div data-aos-offset="400" data-aos-duration="1000" data-aos="fade-up" class="cta-content aos-init aos-animate">
            <div class="row">
              <div class="heading justify-content-center col-12 col-lg-3">
                <?php if ( $subtitle ): ?>
                  <span class="red-headline"><?php echo $subtitle ?></span>
                <?php endif; ?>
                <h2><?php echo $title ?></h2>
                <div class="cta-btn">
                <?php if( $link ): 
                  $link_url = $link['url'];
                  $link_title = $link['title'];
                  $link_target = $link['target'] ? $link['target'] : '_self';
                ?>
                  <a class="standard-btn red" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><?php echo esc_html( $link_title ); ?></a>
                <?php endif; ?>
                </div>
              </div>
              <div class="cta-description col-12 col-lg-9">
                <?php echo $description ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php else: // custom_cta returned false ?>
  <section class="product-cta black-radial-gradient">
    <?php get_template_part('/layouts/sections/global/cta'); ?>
  </section> 
<?php endif; // end of if custom_cta logic ?>