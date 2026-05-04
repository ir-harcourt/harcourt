<?php
	$website_notification = get_field('website_notification', 'options');
	get_header();
?>

<main class="container">
  <div class="site-padding <?php if($website_notification): echo "add-margin"; endif; ?>">
    <div class="row">

      <div class="col-sm pr-5">
        <div id="content" role="main">
          <?php get_template_part('loops/single-post-faq'); ?>
        </div><!-- /#content -->
      </div>

      <?php get_sidebar(); ?>

    </div><!-- /.row -->
  </div><!-- /.row -->
</main><!-- /.container -->

<?php get_footer(); ?>
