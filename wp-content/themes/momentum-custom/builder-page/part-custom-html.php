<?php
$background = get_sub_field('background');
$breadcrumbs = get_sub_field('breadcrumbs');
$custom_content = get_sub_field('custom_content');
$website_notification = get_field('website_notification', 'options');
?>

<section class="builder-block block-custom-html site-padding-60 aos-init aos-animate <?php echo $background ?> <?php if($website_notification): echo "add-margin"; endif; ?>">
<?php if ( $background == 'bg-harcourt'): ?><div class="background-overlay"><?php endif; ?>
    <div class="container">
        <?php if ( $breadcrumbs ): ?>
            <div class="breadcrumb-section">
                <?php if ( function_exists('yoast_breadcrumb') ) {
                yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
                } ?>
            </div>
        <?php endif; ?>
        <div class="custom-content">
            <?php echo $custom_content ?>
        </div>
  </div>
<?php if ( $background == 'bg-harcourt'): ?></div><?php endif; ?>
</section>