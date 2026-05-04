<?php
$background = get_sub_field('background');
$section_padding = get_sub_field('section_padding');
?>

<?php if( have_rows('rows') ): ?>
  <section class="builder-block block-alt-rows site-padding-60 <?php echo $background ?>">
  <?php if ( $background == 'bg-harcourt'): ?><div class="background-overlay"><?php endif; ?>
    <div class="container">
      <?php while( have_rows('rows') ) : the_row(); ?>
        <div class="row row-<?php echo get_row_index(); ?> <?php if ( $section_padding ): ?>section-padding<?php endif; ?>">
          <div data-aos-duration="1500" data-aos="fade-<?php if( get_row_index() % 2 == 0 ): ?>left<?php else: ?>right<?php endif; ?>" class="content-half col col-12 col-lg-6">
            <?php 
            $subtitle = get_sub_field('subtitle');
            $title = get_sub_field('title');
            $content = get_sub_field('content');
            $media_image = get_sub_field('media_type');
            $video = get_sub_field('video_embed');
            $image = get_sub_field('image');
            ?>
            <?php if ( $subtitle ): ?>
              <span class="red-headline mt-5"><?php echo $subtitle ?></span>
            <?php endif; ?>
            <h2 class="mt-5"><?php echo $title ?></h2>
            <div class="description mt-2">
              <?php echo $content ?>
            </div>
          </div>
          <div data-aos-duration="1500" data-aos="fade-<?php if( get_row_index() % 2 == 0 ): ?>right<?php else: ?>left<?php endif; ?>" class="image-half col col-12 col-lg-6">
            <?php if ( $media_image ): // media_image returned true ?> 
              <?php if( !empty( $image ) ): ?>
                <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
              <?php endif; ?>
            <?php else: // media_image returned false ?>
              <?php if( !empty( $video ) ): ?>
                <div class="video-content"><?php echo $video ?></div>
              <?php endif; ?>
            <?php endif; // end of if media_image logic ?>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
    <?php if ( $background == 'bg-harcourt'): ?></div><?php endif; ?>
  </section>
  <?php else : ?>
<?php endif; ?>