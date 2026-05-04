<?php
/*
Template Name: Contact
Template Post Type: page
*/

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

get_template_part('/layouts/sections/contact/hero');
?>

<section class="site-padding">
  <main class="container">
    <div class="row">

      <div class="col-lg-8 pr-lg-5">
        <div id="content" role="main">

          <article role="article" id="post_<?php the_ID()?>" <?php post_class()?>>
            <?php the_content()?>
          </article>

        </div><!-- /#content -->
      </div>

      <?php get_sidebar(); ?>

    </div><!-- /.row -->
  </main><!-- /.container -->
</section><!-- /.row -->



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
