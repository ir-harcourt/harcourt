<?php
$content_position = get_sub_field('content_position');
$h1_tag = get_sub_field('h1_tag');
$background = get_sub_field('background');
$breadcrumbs = get_sub_field('breadcrumbs');
$subtitle = get_sub_field('subtitle');
$title = get_sub_field('title');
$content = get_sub_field('content');
$content_align = get_sub_field('content_align');
$media_image = get_sub_field('media_type');
$image_contained = get_sub_field('image_contained');
$video = get_sub_field('video_embed');
$image = get_sub_field('image');
$section_padding = get_sub_field('section_padding');
$smaller_image = get_sub_field('smaller_image');
$lower_content_section = get_sub_field('lower_content_section');
$website_notification = get_field('website_notification', 'options');
?>

<section class="builder-block block-two-column <?php if ( !$section_padding ): ?>no-padding<?php else: ?>site-padding-60<?php endif; ?> <?php if ( $smaller_image ): ?>smaller-image<?php endif; ?> <?php echo $content_position ?> <?php echo $background ?> <?php if($website_notification): echo "add-margin"; endif; ?>" <?php if ( $image_contained == true ): ?>style="padding: 6rem 0;"<? endif; ?>>
<?php if ( $background == 'bg-harcourt'): ?><div class="background-overlay <?php if ( !$section_padding ): ?>no-padding<?php endif; ?>"><?php endif; ?>
  <div class="container">
    <div class="row" <?php if ( $image_contained == false && $smaller_image == true ): ?>style="height: 550px;"<?php elseif ( $image_contained == false && $smaller_image == false ): ?>style="height: 800px;"<? else: ?><?php endif; ?>>
      <div data-aos-duration="1500" data-aos="fade-<?php if(get_sub_field('content_position') == "left"): ?>right<?php else: ?>left<?php endif; ?>" class="content-half col col-12 col-lg-6">
          <?php if ( $breadcrumbs ): ?>
            <div class="breadcrumb-section text-<?php echo $content_align ?>">
              <?php if ( function_exists('yoast_breadcrumb') ) {
              yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
              } ?>
            </div>
          <?php endif; ?>
          <div class="column-content-section text-<?php echo $content_align ?> <?php if ($lower_content_section) : ?>lower-content<?php endif; ?>">
            <?php if ( $subtitle ): ?>
              <span class="red-headline mt-3"><?php echo $subtitle ?></span>
            <?php endif; ?>
            <?php if ( $h1_tag ): // h1_tag returned true ?> 
              <h1><?php echo $title ?></h1>
            <?php else: // h1_tag returned false ?>
              <h2><?php echo $title ?></h2>
            <?php endif; // end of if h1_tag logic ?>
            <div class="description mt-2">
              <?php echo $content ?>
            </div>
          </div>
      </div>
      <div data-aos-duration="1500" data-aos="fade-<?php if(get_sub_field('content_position') == "left"): ?>left<?php else: ?>right<?php endif; ?>" class="image-half col col-12 col-lg-6 <?php if ( $image_contained == false ): ?>image-overlap<? endif; ?>">
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
  </div>
<?php if ( $background == 'bg-harcourt'): ?></div><?php endif; ?>
</section>