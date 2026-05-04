<?php
/*
Template Name: Blog
Template Post Type: page
*/

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

?>


<section class="blog-featured site-padding">
  <div class="container">
    <div class="owl-carousel" id="featuredPost">

      <a class="featured-blog-item">
        <div class="the-post">
          <img src="https://picsum.photos/600/600/?image=20" alt="">
          <span class="post-content">
            <span class="post-cat">Blog</span>
            <span class="post-title">This is the post title and it can be very long if it wants to be.</span>
          </span>
        </div>
      </a>
      <a class="featured-blog-item">
        <div class="the-post">
          <img src="https://picsum.photos/600/600/?image=30" alt="">
          <span class="post-content">
            <span class="post-cat">Blog</span>
            <span class="post-title">This is the post title and it can be very long if it wants to be.</span>
          </span>
        </div>
      </a>
      <a class="featured-blog-item">
        <div class="the-post">
          <img src="https://picsum.photos/600/600/?image=40" alt="">
          <span class="post-content">
            <span class="post-cat">Blog</span>
            <span class="post-title">This is the post title and it can be very long if it wants to be.</span>
          </span>
        </div>
      </a>
      <a class="featured-blog-item">
        <div class="the-post">
          <img src="https://picsum.photos/600/600/?image=50" alt="">
          <span class="post-content">
            <span class="post-cat">Blog</span>
            <span class="post-title">This is the post title and it can be very long if it wants to be.</span>
          </span>
        </div>
      </a>

      <a class="featured-blog-item">
        <div class="the-post">
          <img src="https://picsum.photos/600/600/?image=60" alt="">
          <span class="post-content">
            <span class="post-cat">Blog</span>
            <span class="post-title">This is the post title and it can be very long if it wants to be.</span>
          </span>
        </div>
      </a>
      <a class="featured-blog-item">
        <div class="the-post">
          <img src="https://picsum.photos/600/600/?image=70" alt="">
          <span class="post-content">
            <span class="post-cat">Blog</span>
            <span class="post-title">This is the post title and it can be very long if it wants to be.</span>
          </span>
        </div>
      </a>
      <a class="featured-blog-item">
        <div class="the-post">
          <img src="https://picsum.photos/600/600/?image=80" alt="">
          <span class="post-content">
            <span class="post-cat">Blog</span>
            <span class="post-title">This is the post title and it can be very long if it wants to be.</span>
          </span>
        </div>
      </a>
      <a class="featured-blog-item">
        <div class="the-post">
          <img src="https://picsum.photos/600/600/?image=90" alt="">
          <span class="post-content">
            <span class="post-cat">Blog</span>
            <span class="post-title">This is the post title and it can be very long if it wants to be.</span>
          </span>
        </div>
      </a>

    </div>
  </div>
</section>

<section class="blog-posts">

  <div class="container">

    <h2>Recent News</h2>
    <hr>

    <?php

    // The Query
    $paged = ( get_query_var('page') ) ? get_query_var('page') : 1;
    $args = array(
      'posts_per_page'         => 9,
      'update_post_meta_cache' => false,
      'paged' => $paged
    );
    $query = new WP_Query( $args );


    // The Loop
    if ( $query->have_posts() ) {

      $row = 0;

      while ( $query->have_posts() ) {

        $query->the_post();

        $row++;

        if ($row == 1 || $row == 4 || $row == 7):
          echo '<div class="row">';
        endif;

        echo '<div class="col-lg-4 blog-post-item">';
        echo '<a href="' . get_the_permalink() . '">';
        the_post_thumbnail('blog-thumb');
        the_category();
        echo '<a href="' . get_the_permalink() . '">';
        echo '<span class="title">';
        the_title();
        echo '</span>';
        echo '<time datetime="' . get_the_date('c') . '" itemprop="datePublished">' . get_the_date() . '</time>';
        echo '<span class="excerpt">';
        echo excerpt(30);
        echo '</span>';
        echo '<span class="more">Read More <i class="fas fa-long-arrow-alt-right"></i></span>';
        echo '</a>';
        echo '</div>';


        if ($row == 3 || $row == 6 || $row == 9):
          echo '</div>';
        endif;


      }

      if ( function_exists('momentumst_pagination') ) { momentumst_pagination(); } else if ( is_paged() ) { ?>
        <ul class="pagination">
          <li class="page-item older">
            <?php next_posts_link('<i class="fas fa-arrow-left"></i> ' . __('Previous', 'momentumst')) ?></li>
            <li class="page-item newer">
              <?php previous_posts_link(__('Next', 'momentumst') . ' <i class="fas fa-arrow-right"></i>') ?></li>
            </ul>
          <?php }

          /* Restore original Post Data */
          wp_reset_postdata();
        } else {
          // no posts found
        }

        ?>

      </div>

    </section>

    
    <?php




  endwhile;

  //404
else: get_template_part('/loops/index-post', 'none');

endif;


get_footer();

?>

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
      items:4
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
