<?php
/*
Template Name: Contact - UK
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
      
      <div class="sidebar col-lg-4" id="sidebar" role="navigation">

          <div class="sidebar-widget-container black-radial-gradient">
              <?php dynamic_sidebar('uk-sidebar'); ?>
          </div>

          <?php if( is_active_sidebar('sidebar-widget-area') ): ?>

            <div class="scscpq_newuser mt-4 red-radial-gradient text-center p-5">
                  <h3 class="mt-0 mb-4">Get Access to Our Full Catalog</h3>
                  <a class="standard-btn ignore-hover white mt-0 w-100" href="<?php echo home_url('/'); ?>request-access">Request Access</a>
            </div>
      </div>

<?php endif; ?>

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
