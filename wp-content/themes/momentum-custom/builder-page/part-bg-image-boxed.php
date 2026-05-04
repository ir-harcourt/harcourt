<?php
$background = get_sub_field('background');
$text_color = get_sub_field('text_color');
$title1 = get_sub_field('title_content1');
$description1 = get_sub_field('description_content1');
$button1 = get_sub_field('button_content1');
$title2 = get_sub_field('title_content2');
$description2 = get_sub_field('description_content2');
$button2 = get_sub_field('button_content2');
$media_image = get_sub_field('media_type');
$video_mp4 = get_sub_field('video_mp4');
$video_webm = get_sub_field('video_webm');
$video_placeholder = get_sub_field('video_placeholder');
$image = get_sub_field('image');
$content_section_1 = get_sub_field('content_section_1');
$content_section_2 = get_sub_field('content_section_2');
?>

<section class="builder-block block-background-media block-full-image site-padding-60 <?php echo $background ?>">
    <div class="media-background">
        <?php if ( $media_image ): // media_image returned true ?>
            <div class="background-image" style="<?php if( !empty( $image ) ): ?>background-image: url('<?php echo esc_url($image['url']); ?>');<?php endif; ?>">
                <div class="overlay site-padding-sm <?php echo $background ?>"></div>
            </div>
        <?php else: // media_image returned false ?>
            <div class="background-video <?php if ( $sidebar_content ): ?>col col-12 col-lg-9<?php endif; ?>">
                <video playsinline="" autoplay="" muted="" loop="" poster="<?php echo $video_placeholder ?>" id="bgvideo" width="x" height="y">
                    <source src="<?php echo $video_webm ?>" type="video/webm">
                    <source src="<?php echo $video_mp4 ?>" type="video/mp4">
                </video>
                <div class="overlay site-padding-sm <?php echo $background ?>"></div>
            </div>
        <?php endif; // end of if media_image logic ?>
    </div>
    <div class="container">
        <div class="row">
            <?php if($content_section_1) : ?>
                <div class="col col-12 col-lg-6 content-overlay <?php if($background == 'bg-none'): ?>text-<?php echo $text_color ?><?php elseif($background == 'bg-black'): ?>text-white<?php else: ?>text-black<?php endif; ?>">
                    <h2><?php echo $title1; ?></h2>
                    <?php echo $description1; ?>
                    <?php if( $button1 ): 
                    $button1_url = $button1['url'];
                    $button1_title = $button1['title'];
                    $button1_target = $button1['target'] ? $button1['target'] : '_self'; ?>
                        <div class="btn-container">
                            <a class="standard-btn red" href="<?php echo esc_url( $button1_url ); ?>" target="<?php echo esc_attr( $button1_target ); ?>"><?php echo esc_html( $button1_title ); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="row">
            <?php if($content_section_2) : ?>
                <div class="col col-12 col-lg-6 bg-white content-boxed">
                    <h2><?php echo $title2; ?></h2>
                    <?php echo $description2; ?>
                    <?php if( $button2 ): 
                    $button2_url = $button2['url'];
                    $button2_title = $button2['title'];
                    $button2_target = $button2['target'] ? $button2['target'] : '_self'; ?>
                        <div class="btn-container">
                            <a class="standard-btn red" href="<?php echo esc_url( $button2_url ); ?>" target="<?php echo esc_attr( $button2_target ); ?>"><?php echo esc_html( $button2_title ); ?></a>
                        </div>
                    <?php else: ?>
                    <div class="btn-container">
                        <a href="/catalog" class="standard-btn red scscpq_catalog">Catalog</a>
                        <a href="/request-access" class="standard-btn red scscpq_newuser">Request Access</a>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>