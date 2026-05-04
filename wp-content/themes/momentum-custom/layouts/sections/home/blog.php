<?php

$prefix = 'resource_';
$fields = array('headline', 'title', 'description', 'btn', 'bg_img','image', 'list');

foreach ($fields as $key => $value) {
  ${$value} = get_field($prefix . $value);
}
// $image = get_field('products_image');
?>

<section class="resource-section site-padding-tl">
  <div class="container">
    <div class="row">

      <?php
      $featured_posts = get_field('featured_post', false, false);

      // WP_Query arguments
      $args = array(
        'post_status'            => array( 'publish' ),
        'post_type'              => array( 'post' ),
        'nopaging'               => false,
        'numberposts' => 1,
        'posts_per_page'         => 1,
        'post__in'		           => $featured_posts,
      );

      // The Query
      $query = new WP_Query( $args );

      // The Loop
      if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
          $query->the_post();
          $categories = get_the_category();
          ?>
          <div class="col-lg-5">
            <div data-aos-duration="1000" data-aos="fade-right" class="product-item">
              <a class="product-link" href="<?php the_permalink(); ?>"></a>
              <div class="product-img-container">
                <div class="product-img has-bg-img" style="background-image:url('<?php the_post_thumbnail_url() ?>')">
                  <?php
                  if ( ! empty( $categories ) ) {
                    foreach ($categories as $category ) {
                      $separator = ($category != end($categories)) ? ", " : '';
                      echo '<span class="' . esc_html( $category->slug ) . '">' . esc_html( $category->name ) . '</span>'. $separator;
                    }
                  }
                  ?>
                </div>
              </div>
              <div class="product-content">
                <h3><?php the_title(); ?></h3>
                <p><?php echo excerpt(30); ?></p>
              </div>
            </div>
            <?php
          }
        } else {
          // no posts found
        }

        // Restore original Post Data
        wp_reset_postdata();

        $prefix = 'red_box_';
        $fields = array('title', 'description', 'link', 'image');
        foreach ($fields as $key => $value) {
          ${$value} = get_field($prefix . $value);
        }
        ?>
        <div data-aos-duration="1000" data-aos="fade-right" class="ll-item has-overlay has-bg-img" style="background-image:url('<?php echo $image; ?>');">
          <div class="overlay">
            <a class="absolute-link" href="<?php echo $link['url']; ?>"></a>
            <div class="ll-content">
              <h3><?php echo $title ?></h3>
              <p><?php echo $description ?></p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-7 scroll d-flex d-md-block">

        <?php
        $post_items = get_field('post_items');

        // WP_Query arguments
        $args = array(
          'post_status'         => array( 'publish' ),
          'post_type'           => array( 'post' ),
          'nopaging'            => false,
          'numberposts'         => 3,
          'posts_per_page'      => 3,
          'cat'		              => $post_items,
        );

        // The Query
        $query = new WP_Query( $args );

        // The Loop
        if ( $query->have_posts() ) {
          while ( $query->have_posts() ) {
            $query->the_post();
            $categories = get_the_category();
            ?>
            <div data-aos-duration="1000" data-aos="fade-left" class="blog-item has-overlay has-bg-img" style="background-image:url('<?php the_post_thumbnail_url(); ?>');">
              <div class="overlay">
                <div class="blog-content-container">
                  <div class="blog-content">
                    <div class="blog-cat">
                      <?php
                      if ( ! empty( $categories ) ) {
                        foreach ($categories as $category ) {
                          $separator = ($category != end($categories)) ? ", " : '';
                          echo '<span class="' . esc_html( $category->slug ) . '"><a href="'.get_category_link( $category->term_id ).'">' . esc_html( $category->name ) . '</a></span>'. $separator;
                        }
                      }
                      ?>
                    </div>
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <p><a href="<?php the_permalink(); ?>"><?php echo excerpt(30); ?></a></p>
                    <span class="blog-date">  <time datetime="<?php echo get_the_date('c'); ?>" itemprop="datePublished"><i class="far fa-clock"></i> <?php echo get_the_date(); ?> </time></span>
                  </div>
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
  </div>
</section>
