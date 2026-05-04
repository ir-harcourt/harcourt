<?php
$background = get_sub_field('background');
$content_above = get_sub_field('content_above');
$center_above_content = get_sub_field('center_above_content');
$above_title = get_sub_field('above_title');
$above_description = get_sub_field('above_description');
$main_image = get_sub_field('main_image');
$full_width = get_sub_field('full_width');
$right_icon = get_sub_field('right_icon');
$left_icon = get_sub_field('left_icon');
?>

<section class="builder-block block-image-list-items site-padding-60 <?php echo $background ?>">
    <div class="container<?php if ( $full_width ): ?>-fluid<?php endif;?>">
        <?php if ( $content_above ): // content_above returned true ?> 
        <div class="above-content <?php if ( $center_above_content ): ?>text-center<?php endif;?>">
        <?php if( !empty( $above_title ) ): ?>
            <h2><?php echo $above_title ?></h2>
        <?php endif; ?>
        <?php if( !empty( $above_description ) ): ?>
            <p><?php echo $above_description ?></p>
        <?php endif; ?>
        </div>
        <?php endif; // end of if content_above logic ?>
        <div class="row">
            <div class="col col-12 col-lg-4 left-list">
            <?php if( have_rows('list_items_left') ): ?>
                <div class="left-list-item">
                    <?php while( have_rows('list_items_left') ) : the_row(); 
                    $left_image = get_sub_field('left_icon');
                    $left_title = get_sub_field('left_title'); 
                    $left_description = get_sub_field('left_description'); ?>
                    <div class="list-item-container">
                        <?php if( !empty( $left_image ) ): ?>
                            <div class="col col-12 col-lg-2 list-icon">
                                <img src="<?php echo esc_url($left_image['url']); ?>" alt="<?php echo esc_attr($left_image['alt']); ?>" />
                            </div>
                        <?php endif; ?>
                        <div class="col col-12 col-lg-10 list-content">
                            <?php if( $left_title ) : ?>
                                <div class="list-title">
                                    <h3><?php echo $left_title; ?></h3>
                                </div>
                            <?php endif; ?>
                            <?php if( $left_description ) : ?>
                                <div class="list-description">
                                    <p><?php echo $left_description; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else : endif; ?>
        </div>
        <div class="col col-12 col-lg-4 main-image">
            <?php if( !empty( $main_image ) ): ?>
                <img src="<?php echo esc_url($main_image['url']); ?>" alt="<?php echo esc_attr($main_image['alt']); ?>" />
            <?php endif; ?>
        </div>
        <div class="col col-12 col-lg-4 right-list">
            <?php if( have_rows('list_items_right') ): ?>
                <div class="right-list-item">
                    <?php while( have_rows('list_items_right') ) : the_row(); 
                    $right_icon = get_sub_field('right_icon'); 
                    $right_title = get_sub_field('right_title'); 
                    $right_description = get_sub_field('right_description'); ?>
                    <div class="list-item-container">
                        <?php if( !empty( $right_icon ) ): ?>
                            <div class="col col-12 col-lg-2 list-icon">
                                <img src="<?php echo esc_url($right_icon['url']); ?>" alt="<?php echo esc_attr($right_icon['alt']); ?>" />
                            </div>
                        <?php endif; ?>
                        <div class="col col-12 col-lg-10 list-content">
                            <?php if( $right_title ) : ?>
                                <div class="list-title">
                                    <h3><?php echo $right_title; ?></h3>
                                </div>
                            <?php endif; ?>
                            <?php if( $right_description ) : ?>
                                <div class="list-description">
                                    <p><?php echo $right_description; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else : endif; ?>   
        </div>
    </div>
  </div>
</section>