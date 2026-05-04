<?php


$fields = array('grid_id', 'grid_items', 'grid_type', 'scroll', 'post_type_select');

foreach ($fields as $key => $value) {
  ${$value} = get_field($value);
}

?>

<div class="grid-items" id="<?php echo $grid_id ?>">
  <div class="row <?php if ($scroll): echo 'scroll'; endif; ?>">

    <?php

    if ($grid_type === 'select') {
      // WP_Query arguments
      $args = array(
        'post_status'            => array( 'publish' ),
        'post_type'              => array( 'page' ),
        'nopaging'               => true,
        'posts_per_page'         => '-1',
        'post__in'		           => $grid_items,
        'orderby'                => 'post__in',
        'order'                  => 'ASC',
      );

    }

    elseif ($grid_type === 'post') {
      // WP_Query arguments
      $args = array(
        'post_status'            => array( 'publish' ),
        'post_type'              => array( $post_type_select ),
        'nopaging'               => true,
        'posts_per_page'         => '-1',
      );

    }

    else {

      // WP_Query arguments
      $args = array(
        'post_parent'			       => $grid_items[0],
        'post_status'            => array( 'publish' ),
        'post_type'              => array( 'page' ),
        'nopaging'               => true,
        'posts_per_page'         => '-1',
        'orderby'                => 'menu_order',
        'order'                  => 'ASC',
      );

    }

    // The Query
    $query = new WP_Query( $args );

    // The Loop
    if ( $query->have_posts() ) {
      while ( $query->have_posts() ) {
        global $wp_query;
        $query->the_post();
        $excerpt = get_field('excerpt');
        $cover_type = get_field('cover_type');
        $id = get_the_ID();
        // do something
        ?>
        <div class="col-lg-3">
          <div data-aos="fade-up" data-aos-duration="1000" class="grid-item">

            <?php if ('cut-out' == $cover_type): ?>

              <div class="grid-img">
                <div class="grid-img-container">
                  <?php the_post_thumbnail('full'); ?>
                </div>
              </div>

            <?php else: ?>

              <div class="grid-img" style="background-image:url(<?php echo get_the_post_thumbnail_url($id, 'full'); ?>); background-size: cover;">
                <div class="grid-img-container">
                </div>
              </div>

            <?php endif; ?>

            <div class="grid-content">
              <h3><?php the_title(); ?></h3>
              <p><?php echo $excerpt; ?></p>
              <a class="inline-btn" href="<?php the_permalink(); ?>">Learn More</a>
            </div>
          </div>
        </div>
        <?php
      }
    } else {
      // no posts found
    }




    // Restore original Post Data
    wp_reset_postdata();


    ?>
  </div>
</div>
