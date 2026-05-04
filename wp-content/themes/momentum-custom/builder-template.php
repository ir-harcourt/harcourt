<?php /* Template Name: Page Builder Template */ ?>

<?php

add_filter( 'body_class','pj_class_names' );
function pj_class_names( $classes ) {
  $classes[] = 'landing-page';
  return $classes;
}

get_header();
?>

<main>

  <?php

  // Main Loop
  if ( have_posts() ) :
    while ( have_posts() ) : the_post();

    // Check value exists.
    if( have_rows('page_builder') ):

      // Loop through rows.
      while ( have_rows('page_builder') ) : the_row();


      if( get_row_layout() == 'grid' ):

        get_template_part('builder-page/part-grid');

        elseif( get_row_layout() == 'content_slider' ):

          get_template_part('builder-page/part-content-slider');

        elseif( get_row_layout() == 'two_column' ):

          get_template_part('builder-page/part-two-column');

          elseif( get_row_layout() == 'list_column' ):

            get_template_part('builder-page/part-list-column');

            elseif( get_row_layout() == 'banner' ):

              get_template_part('builder-page/part-banner');

              elseif( get_row_layout() == 'alt_rows' ):

                get_template_part('builder-page/part-alt-rows');

                elseif( get_row_layout() == 'bg_image' ):

                  get_template_part('builder-page/part-bg-image');

                  elseif( get_row_layout() == 'bg_image_boxed' ):

                    get_template_part('builder-page/part-bg-image-boxed');

                  elseif( get_row_layout() == 'image_gallery' ):

                    get_template_part('builder-page/part-image-gallery');

                    elseif( get_row_layout() == 'list' ):

                      get_template_part('builder-page/part-list');

                      elseif( get_row_layout() == 'image_w_list_items' ):

                        get_template_part('builder-page/part-image-w-list-items');

                        elseif( get_row_layout() == 'testimonials' ):

                          get_template_part('builder-page/part-testimonials');

                      elseif( get_row_layout() == 'custom_html' ):

                        get_template_part('builder-page/part-custom-html');

                        elseif( get_row_layout() == 'cta' ):

                          get_template_part('builder-page/part-cta');

                  endif;

                  // End loop.
                endwhile;

                // No value.
                else :
                  // Do something...
                endif;


              endwhile;
            endif;

            ?>

          </main>

<?php get_footer(); ?>
<script type="text/javascript">
  $(document).ready(function() {
    $('.owl-carousel.content-slider').owlCarousel({
        loop : true,
        items : 1,
        dots : true,
        pullDrag : true,
        slideBy : 1,
        nav : true,
        navText : false,
        singleItem:true,
        autoHeight:true,
        responsive : {
          0:{ 
        items : 1,
          },
          768:{ 
        items : 2,
          },
          992:{ 
        items : 3,
          },
          1600:{ 
        items : 4,
          },   
      }
    });
  });
</script>
<script type="text/javascript">
  $(document).ready(function() {
    $('.owl-carousel.image-gallery').owlCarousel({
    loop : true,
    items : 1,
    dots : true,
    pullDrag : true,
    slideBy : 1,
    nav : true,
    navText : false,
    singleItem: true,
    });
  });
</script>
<script type="text/javascript">
  $(document).ready(function() {
    $('.owl-carousel.testimonial-slider').owlCarousel({
    loop : true,
    items : 1,
    dots : true,
    pullDrag : true,
    slideBy : 1,
    nav : true,
    navText : false,
    singleItem: true,
    autoHeight: true,
    });
  });
</script>