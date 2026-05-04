<?php
	get_header();

	$website_notification = get_field('website_notification' , 'options');
?>

<div class="site-padding <?php if($website_notification): echo "add-margin"; endif; ?>">
  <main class="container">
    <div class="row">

      <div class="col-lg-8 pr-lg-5">
        <div id="content" role="main">
          <?php get_template_part('loops/page-content'); ?>
        </div><!-- /#content -->
      </div>

      <?php get_sidebar(); ?>

    </div><!-- /.row -->
  </main><!-- /.container -->
</div><!-- /.row -->

<?php get_footer(); ?>
