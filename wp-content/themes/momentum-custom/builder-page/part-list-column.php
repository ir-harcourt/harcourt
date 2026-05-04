<?php
$content_position = get_sub_field('content_position');
$h1_tag = get_sub_field('h1_tag');
$background = get_sub_field('background');
$breadcrumbs = get_sub_field('breadcrumbs');
$subtitle = get_sub_field('subtitle');
$title = get_sub_field('title');
$content_align = get_sub_field('content_align');
$custom_code = get_sub_field('custom_code');
?>

<section class="builder-block block-list-column why-configurator <?php echo $content_position ?> <?php echo $background ?>">
    <div class="container large">
        <div class="row align-items-center">
            <div data-aos-duration="1500" data-aos="fade-<?php if(get_sub_field('content_position') == "left"): ?>left<?php else: ?>right<?php endif; ?>" class="col-lg-5 py-4 py-lg-0 aos-init aos-animate text-<?php echo $content_align ?> <?php if(get_sub_field('content_position') == 'left'): ?><?php else: ?>offset-md-1<?php endif; ?>">
                <?php if ( $breadcrumbs ): ?>
                    <div class="breadcrumb-section text-<?php echo $content_align ?>">
                        <?php if ( function_exists('yoast_breadcrumb') ) { yoast_breadcrumb( '<p id="breadcrumbs">','</p>' ); } ?>
                    </div>
                <?php endif; ?>
                <?php if ( $subtitle ): ?>
                    <span class="red-headline mt-3"><?php echo $subtitle ?></span>
                <?php endif; ?>
                <?php if ( $h1_tag ): ?> 
                    <h1 class="mt-0"><?php echo $title ?></h1>
                <?php else: ?>
                    <h2 class="mt-0"><?php echo $title ?></h2>
                <?php endif; ?>
                <?php if( have_rows('list_col_items') ): ?>    
                    <div class="icon-items">
                        <?php while( have_rows('list_col_items') ) : the_row(); ?>
                            <?php $list_image = get_sub_field('list_image');
                            $list_title = get_sub_field('list_title');
                            $list_description = get_sub_field('list_description'); ?>
                            <div class="icon-item d-flex">
                                <div class="image-container">
                                    <?php if( !empty( $list_image ) ): ?>
                                        <img src="<?php echo esc_url($list_image['url']); ?>" alt="<?php echo esc_attr($list_image['alt']); ?>" class="attachment-full size-full" />
                                    <?php endif; ?>
                                </div>
                                <div class="content-container pl-5 pl-lg-0">
                                    <h3 class="mt-0"><?php echo $list_title ?></h3>
                                    <p class="mb-0"><?php echo $list_description ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: endif; ?>
            </div>
            <div class="<?php if(get_sub_field('content_position') == 'left'): ?>offset-md-1<?php else: ?><?php endif; ?> col-lg-6 site-padding list-col">
                <?php echo $custom_code ?>
            </div>
        </div>
    </div>
</section>