<?php
$content_position = get_sub_field('content_position');
$h1_tag = get_sub_field('h1_tag');
$background = get_sub_field('background');
$breadcrumbs = get_sub_field('breadcrumbs');
$two_column_list = get_sub_field('two_column_list');
$subtitle = get_sub_field('subtitle');
$title = get_sub_field('title');
$content = get_sub_field('content');
$image_contained = get_sub_field('image_contained');
$image = get_sub_field('image');
?>

<section class="builder-block block-list site-padding-60 <?php echo $content_position ?> <?php echo $background ?>" <?php if ( $image_contained == true ): ?>style="padding: 6rem 15px;"<? endif; ?>>
<?php if ( $background == 'bg-harcourt'): ?><div class="background-overlay"><?php endif; ?>
    <div class="container">
        <div class="row">
            <div data-aos-duration="1500" data-aos="fade-<?php if(get_sub_field('content_position') == "left"): ?>right<?php else: ?>left<?php endif; ?>" class="content-half col col-12 col-lg-6" <?php if ( $image_contained == false ): ?>style="padding: 6rem 15px;"<? endif; ?>>
                <?php if ( $breadcrumbs ): ?>
                    <div class="breadcrumb-section">
                        <?php if ( function_exists('yoast_breadcrumb') ) {
                        yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
                        } ?>
                    </div>
                <?php endif; ?>
                <?php if ( $subtitle ): ?>
                    <span class="red-headline mt-5"><?php echo $subtitle ?></span>
                <?php endif; ?>
                <?php if ( $h1_tag ): // h1_tag returned true ?> 
                    <h1 class="mt-5"><?php echo $title ?></h1>
                <?php else: // h1_tag returned false ?>
                    <h2 class="mt-5"><?php echo $title ?></h2>
                <?php endif; // end of if h1_tag logic ?>
                <hr>
                <?php if( have_rows('list') ): ?>
                <div class="list-items <?php if ( $two_column_list ): ?>two-col<?php endif; ?>">
                    <?php while( have_rows('list') ) : the_row(); 
                    $title = get_sub_field('title');
                    $description = get_sub_field('description');  
                    ?>
                        <div class="stats">
                            <p class="title"><b><?php echo $title ?></b></p>
                            <p><?php echo $description ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php else : ?>
                <?php endif; ?>
            </div>
            <div data-aos-duration="1500" data-aos="fade-<?php if(get_sub_field('content_position') == "left"): ?>left<?php else: ?>right<?php endif; ?>" class="image-half col col-12 col-lg-6 <?php if ( $image_contained == false ): ?>image-overlap<? endif; ?>">
                <?php if( !empty( $image ) ): ?>
                    <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php if ( $background == 'bg-harcourt'): ?></div><?php endif; ?>
</section>