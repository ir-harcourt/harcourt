<?php
$background = get_sub_field('background');
$text = get_sub_field('banner_text');
$link = get_sub_field('banner_link');  
?>

<section class="builder-block block-banner">
    <a class="banner-link <?php echo $background ?>" href="<?php echo $link ?>">
        <div class="container">
            <?php echo $text ?>
        </div>
    </a>
</section>