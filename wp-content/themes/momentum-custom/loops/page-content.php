<?php
/**!
 * The Page Content Loop
 */
?>

<?php if(have_posts()): while(have_posts()): the_post(); ?>
  <article role="article" id="post_<?php the_ID()?>" <?php post_class()?>>
      <div class="breadcrumb-section mb-4 border-bottom">
        <?php if ( function_exists('yoast_breadcrumb') ) {
          yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
        } ?>
      </div>
      <h1 class="hc-title"> <?php the_title()?> </h1>
    <div>
      <?php the_content()?>
      <?php wp_link_pages(); ?>
    </div>
  </article>
<?php
  endwhile;
  else :
    get_template_part('loops/404');
  endif;
?>
