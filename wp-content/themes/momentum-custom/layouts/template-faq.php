<?php
/*
Template Name: FAQ
Template Post Type: page
*/

$website_notification = get_field('website_notification' , 'options');

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

?>

<section class="site-padding-bl mb-5 <?php if($website_notification): echo "add-margin"; endif; ?>">
  <div class="container">
    <div class="row">
      <div class="mb-4 col-lg-12">
        <div class="breadcrumb-section">
          <?php if ( function_exists('yoast_breadcrumb') ) {
            yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
          } ?>
        </div>
        <h1 class="hc-title"> <?php the_title()?> </h1>
      </div>
    </div>
  </div>
</section>

<section class="faq-section site-padding-tl">
  <div class="container">
    <div class="row">

      <?php


      if( have_rows('faq_sections') ):

        while ( have_rows('faq_sections') ) : the_row();


        $term = get_sub_field('faq_type');
        $title = get_sub_field('title');
        $icon = get_sub_field('icon');
        $slug = $term->slug;

        echo '<div class="col-lg-6 mb-5">';
        echo "<h3>{$icon} {$title}</h3>";
        echo "<hr>";

        // WP_Query arguments
        $args = array(
          'post_type'              => array( 'faq' ),
          'nopaging'               => true,
          'order'                  => 'ASC',
          'orderby'                => 'date',
          'tax_query' => array(
            array (
              'taxonomy' => 'faq_type',
              'field' => 'slug',
              'terms' => $slug,
            )
          ),
        );

        // The Query
        $query = new WP_Query( $args );

        // The Loop
        if ( $query->have_posts() ) {
          while ( $query->have_posts() ) {
            $query->the_post();
            $title = get_the_title();
            $link = get_permalink();
            echo "<div class='faq-question'><a href='{$link}'>{$title}</a></div>";
          }
        } else {
          // no posts found
        }

        // Restore original Post Data
        wp_reset_postdata();

        echo '</div>';

      endwhile;

      else :

      endif;

      ?>

    </div>
  </div>
</section>

<section class="black-radial-gradient site-padding-bl">
  <?php
  get_template_part('/layouts/sections/global/cta');
  ?>

</section>

<?php
endwhile;

//404
else: get_template_part('/loops/index-post', 'none');

endif;


get_footer();

?>
