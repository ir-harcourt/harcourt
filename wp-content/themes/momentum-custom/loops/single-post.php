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

    <div class="breadcrumb-section text-center mb-4 border-bottom">
      <?php if ( function_exists('yoast_breadcrumb') ) {
        yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
      } ?>
    </div>

    <h1 class="text-center"> <?php the_title()?> </h1>
    <div class="header-meta text-muted text-center">
      <?php momentumst_post_date(); ?>
    </div>
    <main itemprop="articleBody">
      <?php
      // the_post_thumbnail( 'full', array( 'itemprop' => 'image' ) );
      the_content();
      wp_link_pages();
      ?>
    </main>

  </article>
  <?php

endwhile; else :
  get_template_part('loops/404');
endif;
?>
