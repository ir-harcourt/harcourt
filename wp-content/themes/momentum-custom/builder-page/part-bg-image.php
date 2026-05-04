<?php
$background = get_sub_field('background');
$text_color = get_sub_field('text_color');
$column_content = get_sub_field('column_content');
$column_content_position = get_sub_field('column_content_position');
$sidebar_content = get_sub_field('sidebar_content');
$sidebar_position = get_sub_field('sidebar_position');
$subtitle = get_sub_field('subtitle');
$title = get_sub_field('title');
$description = get_sub_field('description');
$media_image = get_sub_field('media_type');
$video_mp4 = get_sub_field('video_mp4');
$video_webm = get_sub_field('video_webm');
$video_placeholder = get_sub_field('video_placeholder');
$image = get_sub_field('image');
?>

<section class="builder-block block-full-image site-padding-60 <?php echo $background ?>">
<?php if ( $sidebar_content ): ?><div class="image-sidebar-container <?php if ( $sidebar_position ): ?><?php else: ?>reverse-wrap<?php endif; ?>"><?php endif; ?>
    <?php if ( $media_image ): // media_image returned true ?>
      <div class="background-image<?php if ( $sidebar_content ): ?> col col-12 col-lg-9<?php endif; ?>" style="<?php if( !empty( $image ) ): ?>background-image: url('<?php echo esc_url($image['url']); ?>');<?php endif; ?>">
        <?php if ( $column_content ): ?>
          <div class="container <?php if(get_sub_field('column_content_position') == "col-right"): ?>col-right<?php elseif (get_sub_field('column_content_position') == "col-left"): ?>col-left<?php else: ?>col-bottom<?php endif; ?>">
            <?php if( have_rows('columns') ): ?>
              <div div class="row">
                <?php while( have_rows('columns') ) : the_row(); ?>
                <?php
                $title = get_sub_field('title');
                $content = get_sub_field('content');
                ?>
                  <div class="col col-6 col-lg-4 stats">
                    <h3><?php echo $title ?></h3>
                    <div class="description">
                      <?php echo $content ?>
                    </div>
                  </div>
                <?php endwhile; ?>
              </div>
            <?php else : ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <div class="overlay site-padding-sm <?php echo $background ?>"></div>
      </div>
    <?php else: // media_image returned false ?>
      <div class="background-video <?php if ( $sidebar_content ): ?>col col-12 col-lg-9<?php endif; ?>">
        <?php if ( $column_content ): ?>
          <div class="container <?php if(get_sub_field('column_content_position') == "col-right"): ?>col-right<?php elseif (get_sub_field('column_content_position') == "col-left"): ?>col-left<?php else: ?>col-bottom<?php endif; ?>">
            <?php if( have_rows('columns') ): ?>
              <div div class="row">
                <?php while( have_rows('columns') ) : the_row(); ?>
                <?php
                $title = get_sub_field('title');
                $content = get_sub_field('content');
                ?>
                  <div class="col col-6 col-lg-4 stats">
                    <h3><?php echo $title ?></h3>
                    <div class="description">
                      <?php echo $content ?>
                    </div>
                  </div>
                <?php endwhile; ?>
              </div>
            <?php else : ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <video playsinline="" autoplay="" muted="" loop="" poster="<?php echo $video_placeholder ?>" id="bgvideo" width="x" height="y">
          <source src="<?php echo $video_webm ?>" type="video/webm">
          <source src="<?php echo $video_mp4 ?>" type="video/mp4">
        </video>
        <div class="overlay site-padding-sm <?php echo $background ?>"></div>
      </div>
    <?php endif; // end of if media_image logic ?>
    <?php if ( $sidebar_content ): // sidebar_content returned true ?>
      <div class="container sidebar-container <?php if ( $sidebar_content ): ?>col col-12 col-lg-3<?php endif; ?>">
        <div class="sidebar-content <?php if($background == 'bg-none') : echo $text_color; endif; ?>">
          <?php if ( $subtitle ): ?>
            <span class="red-headline"><?php echo $subtitle ?></span>
          <?php endif; ?>
          <?php $h1_tag = get_field('h1_tag');
          if ( $h1_tag ): // h1_tag returned true ?> 
            <h1 class="<?php if ( $subtitle ): ?>mt-3<?php endif; ?>"><?php echo $title ?></h1>
          <?php else: // h1_tag returned false ?>
            <h2 class="<?php if ( $subtitle ): ?>mt-3<?php endif; ?>"><?php echo $title ?></h2>
          <?php endif; // end of if h1_tag logic ?>
            <div class="description mt-2">
              <?php echo $description ?>
            </div>
        </div>
      </div>
    <?php endif; // end of if sidebar_content logic ?>
    <?php if ( $sidebar_content ): ?></div><?php endif; ?>
</section>