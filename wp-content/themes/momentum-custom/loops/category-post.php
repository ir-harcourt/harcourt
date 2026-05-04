<?php
/*
The Category Post (or excerpt)
===========================
Used by category.php
*/
?>

<article role="article" id="post_<?php the_ID()?>" <?php post_class("category-post-item"); ?> >
  <header>
    <h2>
      <a href="<?php the_permalink(); ?>">
        <?php the_title()?>
      </a>
    </h2>
    <p class="text-muted">
      <i class="far fa-calendar-alt"></i>&nbsp;<?php momentumst_post_date(); ?>&nbsp;|
      <i class="far fa-user"></i>&nbsp; <?php _e('By ', 'momentumst'); the_author_posts_link(); ?>&nbsp;|
      <i class="far fa-comment"></i>&nbsp;<a href="<?php comments_link(); ?>"><?php printf( _nx( 'One Comment', '%1$s Comments', get_comments_number(), '', 'momentumst' ), number_format_i18n( get_comments_number() ) ); ?></a>
    </p>
  </header>
  <div>
    <?php
    $length = get_field('length' , 'Options');
    echo '<div>' . excerpt($length) . '</div>' ;
    ?>
  </div>
</article>
