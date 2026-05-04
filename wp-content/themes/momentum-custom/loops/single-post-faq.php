<?php
/*
The Single Post
===============
*/
?>

<?php if(have_posts()): while(have_posts()): the_post(); ?>
  <?php

  if ( has_post_format('gallery') ) {
    $postFormat = 'gallery';
  } else if ( has_post_format('aside') ) {
    $postFormat = 'aside';
  } else if ( has_post_format('video') ) {
    $postFormat = 'video';
  } else {
    $postFormat = 'post';
  }

  ?>
  <article itemscope itemtype="http://schema.org/Article" role="article" id="post_<?php the_ID()?>" <?php post_class()?>>

    <div class="breadcrumb-section mb-4 border-bottom">
      <?php if ( function_exists('yoast_breadcrumb') ) {
        yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
      } ?>
    </div>

    <h1 class="hc-title"> <?php the_title()?> </h1>
    <div class="header-meta text-muted">
      <?php momentumst_post_date(); ?>
    </div>

    <main itemprop="articleBody">

      <?php
      the_post_thumbnail( 'full', array( 'itemprop' => 'image' ) );
      the_content();
      wp_link_pages();
      ?>
    </main>

    <?php if( 'faq' == get_post_type( $post ) ): ?>
      <div class="faq-select site-padding-tl">

        <h4>Can't find what you're looking? Try browsing other FAQs for this topic.</h4>

        <div class="d-flex">
          <select id="faqSelect" name="faq.select">
            <option value="">View All FAQs</option>
            <?php

            $terms = wp_get_post_terms($post->ID, 'faq_type');
            $term = $terms[0]->term_id;

            // WP_Query arguments
            $args = array(
              'post_type'   => array( 'faq' ),
              'nopaging'    => true,
              'tax_query' => array(
                array(
                  'taxonomy' => 'faq_type',
                  'terms'    => $term,
                ),
              ),
            );

            // The Query
            $query = new WP_Query( $args );

            // The Loop
            if ( $query->have_posts() ) {
              while ( $query->have_posts() ) {
                $query->the_post();
                $post_slug = $post->post_name;
                $post_title = get_the_title();;
                echo "<option value='{$post_slug}'>{$post_title}</option>";
              }
            } else {
              // no posts found
            }

            // Restore original Post Data
            wp_reset_postdata();

            ?>
          </select>
          <a class="standard-btn red mt-0 ml-2 disabled" id="selectButton" href="/faq">Go <i class="fa&nbsp;fa-angle-right"><!--icon--></i></a>
        </div>
      </div>

      <style media="screen">
      .faq-select select.form-control:not([size]):not([multiple]) { height: 71px; padding: 15px; }
      .faq-select .standard-btn { width: 80px; }
      </style>
    <?php endif; ?>

  </article>
  <?php

endwhile; else :
  get_template_part('loops/404');
endif;
?>
