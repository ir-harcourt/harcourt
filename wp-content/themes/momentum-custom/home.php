<?php get_header(); ?>

<div class="site-padding-bl">
  <div class="container">
    <?php

    $breadcrumbs = get_field('breadcrumbs', 'Options');
    $featured = get_field('featured', 'Options');

    if ($breadcrumbs) {
      get_template_part('layouts/sections/global/breadcrumbs');
    }
    ?>
  </div>
</div>

<?php if ($featured):?>
<section class="blog-featured site-padding-tl">
  <div class="container">
      <h2><?php _e('Featured', 'momentumst'); ?></h2>
      <hr>
      <div class="owl-carousel" id="featuredPost">

        <?php

        // The Query
        $args = array(
          'posts_per_page'         => 14,
          'update_post_meta_cache' => false,
          'meta_key' => 'meta-checkbox',
          'meta_value' => 'yes'
        );

        $query = new WP_Query( $args );

        // The Loop
        if ( $query->have_posts() ) :

          $row = 0;

          while ( $query->have_posts() ) :
            $query->the_post();
            $categories = get_the_category();


            ?>

            <a href="<?php the_permalink(); ?>" class="featured-blog-item">
              <div class="the-post">
                <?php the_post_thumbnail('blog-featured'); ?>
                <span class="post-content">
                  <span class="post-cat">
                    <?php
                    if ( ! empty( $categories ) ) {
                      echo esc_html( $categories[0]->name );
                    }
                    ?>
                  </span>
                  <span class="post-title"><?php the_title(); ?></span>
                  <time datetime="<?php echo get_the_date('c'); ?>" itemprop="datePublished"><i class="far fa-clock"></i> <?php echo get_the_date(); ?> </time>
                </span>
              </div>
            </a>

            <?php

          endwhile;

        endif;

        ?>


      </div>
  </div>
</section>
<?php endif; ?>

<section class="blog-posts">

  <div class="container">

    <h2><?php _e('Recent News', 'momentumst'); ?></h2>
    <hr>

    <?php if(have_posts()) :

      //Columns must be a factor of 12 (1,2,3,4,6,12)
      $columns = get_field('columns' , 'Options');
      $excerpt = get_field('excerpt' , 'Options');
      $readMore = get_field('read_more' , 'Options');
      $length = get_field('length' , 'Options');
      $numOfCols = $columns;
      $rowCount = 0;
      $bootstrapColWidth = 12 / $numOfCols;

      echo '<div class="row">';

      while(have_posts()) : the_post(); ?>

      <?php
      $categories = get_the_category();
      echo '<div class="col-lg-' . $bootstrapColWidth . ' blog-post-item">';
      echo '<a href="' . get_the_permalink() . '">';
      echo '<div class="post-image-cat">';
      the_post_thumbnail('blog-thumb');
      echo '<span class="blog-cat">';
      if ( ! empty( $categories ) ) {
        foreach ($categories as $category ) {
          echo '<span class="' . esc_html( $category->slug ) . '">' . esc_html( $category->name ) . '</span>';
        }
      }
      echo '</span>';
      echo '</div>';
      echo '<a href="' . get_the_permalink() . '">';
      echo '<span class="title">';
      the_title();
      echo '</span>';
      echo '<time datetime="' . get_the_date('c') . '" itemprop="datePublished">' . get_the_date() . '</time>';
      if ($excerpt) {
        echo '<span class="excerpt">';
        echo excerpt($length);
        echo '</span>';
      }
      if ($readMore) {
        echo '<span class="more">';
        _e ( 'Read More ' , 'momentumst' );
        echo '<i class="fas fa-long-arrow-alt-right"></i></span>';
      }
      echo '</a>';
      echo '</div>';


      $rowCount++;

      if ( more_posts() == 0 ) {
        echo '</div>';
      }
      else if ($rowCount % $numOfCols == 0) :
        echo '</div><div class="row">';
      endif;


      ?>

    <?php endwhile; ?>

    <?php if ( function_exists('momentumst_pagination') ) { momentumst_pagination(); } else if ( is_paged() ) { ?>
      <ul class="pagination">
        <li class="page-item older">
          <?php next_posts_link('<i class="fas fa-arrow-left"></i> ' . __('Previous', 'momentumst')) ?></li>
          <li class="page-item newer">
            <?php previous_posts_link(__('Next', 'momentumst') . ' <i class="fas fa-arrow-right"></i>') ?></li>
          </ul>
        <?php } ?>
      </div>

    </section>
    <?php
    else :
      get_template_part('loops/404');
    endif;
    ?>


    <?php get_footer(); ?>

    <?php $items = get_field('featured_posts_columns', 'Options'); ?>

    <script type="text/javascript">
    // Card Slider
    var cardOwl = $('#featuredPost');

    cardOwl.owlCarousel({
      items:1,
      nav: true,
      dots: true,
      loop:true,
      margin:0,
      responsive:{
        768:{
          items:2
        },
        991:{
          items:2
        },
        1500:{
          items:<?php echo $items ?>
        },
      }
    });

    //Navigation
    $(".nav-icons #prevSlide").click(function() {
      cardOwl.trigger('prev.owl.carousel');
    });
    $(".nav-icons #nextSlide").click(function() {
      cardOwl.trigger('next.owl.carousel');
    });
    </script>
