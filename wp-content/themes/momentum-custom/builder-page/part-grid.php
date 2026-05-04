<?php
$h1_tag = get_sub_field('h1_tag');
$background = get_sub_field('background');
$breadcrumbs = get_sub_field('breadcrumbs');
$content_above = get_sub_field('content_above');
$above_title = get_sub_field('above_title');
$above_description = get_sub_field('above_description');
$center_text = get_sub_field('center_text');
$full_width = get_sub_field('full_width');
$center_above_content = get_sub_field('center_above_content');
?>

<section class="builder-block block-grid site-padding-60-bl <?php echo $background ?>">
<?php if ( $background == 'bg-harcourt'): ?><div class="background-overlay"><?php endif; ?>
  <div class="container<?php if ( $full_width ): ?>-fluid<?php endif;?>">
    <?php if ( $content_above ): // content_above returned true ?> 
      <?php if ( $breadcrumbs ): ?>
        <div class="breadcrumb-section">
          <?php if ( function_exists('yoast_breadcrumb') ) {
          yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
          } ?>
        </div>
      <?php endif; ?>
    <div class="above-content <?php if ( $center_above_content ): ?>text-center<?php endif;?>">
      <?php if( !empty( $above_title ) ): ?>
        <?php if ( $h1_tag ): // h1_tag returned true ?> 
          <h1><?php echo $above_title ?></h1>
        <?php else: // h1_tag returned false ?>
          <h2><?php echo $above_title ?></h2>
        <?php endif; // end of if h1_tag logic ?>
      <?php endif; ?>
      <?php if( !empty( $above_description ) ): ?>
        <p><?php echo $above_description ?></p>
      <?php endif; ?>
    </div>
    <?php endif; // end of if content_above logic ?>
    <div class="row align-items-start">
      <?php $columns = get_sub_field('columns');
      if( have_rows('grid_items') ):
        while ( have_rows('grid_items') ) : the_row(); 
        // Grid Item Container - Start ?>
        <div class='grid-item-content col col-12 col-lg-<?php echo $columns ?> <?php if ( $center_text ): ?>center-text<?php endif; // end of if content_above logic ?>'>
        
        <?php
        // Grid vars
        $image = get_sub_field('image');
        $title = get_sub_field('title');
        $content = get_sub_field('content');
        $link = get_sub_field('link');

        // Grid Content
        if( !empty( $link ) ):
          echo "<a class='grid-item-link' href='{$link}'>";
        endif; ?>

        <?php echo "<div class='grid-image'>";
        if( !empty( $image ) ): ?>
          <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
        <?php endif;
        echo "</div>";
        ?>
        
        <?php if( !empty( $title ) ): 
        echo "<h3>";
        echo $title;
        echo "</h3>";
        endif; ?>

        <?php if( !empty( $content ) ): 
        echo "<div class='grid-content'>";
        echo $content;
        echo "</div>";
        endif; ?>

        <?php if( !empty( $link ) ):
          echo "</a>";
        endif;

        echo "</div>";
        endwhile;
      endif; ?>
    </div>
  </div>
  <?php if ( $background == 'bg-harcourt'): ?></div><?php endif; ?>
</section>


