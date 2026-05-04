<?php get_header(); ?>

<style media="screen">
#content h1 { font-size: 3.5rem; }
#content main a { font-weight: 600; color:#da2636;}
#content time.entry-date.published { font-size: .925rem; color: #4c4c4c; }
#content time.updated { font-size: .925rem; color: #000; font-weight: 600; }
#content .related-posts .post-img { border-radius: 4px 4px 0px 0px ; height: 120px; background-position: center; display: block; background-size: 100%;border: 2px solid #000; border-bottom: 2px solid rgba(0, 0, 0, 0.15);}
#content .related-posts .post-img:hover { background-size: 105%; transition: ease all 2s;}
#content .related-posts .post-content { padding: 1rem; background: #eaeaea; min-height: 140px; border-radius: 0px 0px 4px 4px; border: 2px solid #000; border-top-width: 0; }
#content .related-posts .post-content a { color: #212121;}
#content .related-posts .post-content a:hover { color: #da2636; text-decoration: none;}
#content .related-posts .post-content time { font-size: .75rem; color: #616161; }
#content .related-posts .post-content h4 { margin: .5rem 0 0;}
</style>

<article itemscope itemtype="http://schema.org/Article" role="article" id="post_<?php the_ID()?>" <?php post_class()?>>
  <div id="content" role="main">
    <div class="container">

      <div class="row site-padding">

        <div class="col-lg-8 mx-auto pr-5">

          <?php if(have_posts()): while(have_posts()): the_post(); ?>

            <div class="breadcrumb-section text-center">
              <?php if ( function_exists('yoast_breadcrumb') ) {
                yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
              } ?>
            </div>

            <h1 class="text-center"> <?php the_title()?> </h1>
            <div class="header-meta text-muted text-center">
              <i class="far fa-clock"></i>  <?php momentumst_post_date(); ?>
            </div>

          </div>
        </div><!-- /.row -->
      </div>
      <div class="black-radial-gradient">
        <div class="container">
          <div class="row site-padding">

            <div class="col-lg-8 mx-auto ">

              <main itemprop="articleBody">
                <?php
                the_content();
                wp_link_pages();
                ?>
              </main>

              <hr>
              <div class="row mt-5 related-posts">

                <div class="col-lg-12">
                  <h2>Related Posts</h2>
                </div>

                <?php
                $postcats = get_the_category( $post->ID );
                foreach ($postcats as $cat ) {
                  $related_posts[] = $cat->term_id;
                }
                global $wp_query;
                $args = array(
                  'post_type' => 'post',
                  'posts_per_page' => 3,
                  'category__in' => $related_posts
                );

                $posts = get_posts($args);
                foreach ($posts as $post) :
                  ?>
                  <div class="col-lg-4">
                    <div class="post-item">
                      <a class="post-img" style="background-image:url('<?php echo get_the_post_thumbnail_url() ?>');"href="<?php the_permalink()?>"></a>
                      <div class="post-content"><time datetime="<?php echo get_the_date('c'); ?>" itemprop="datePublished"><i class="far fa-clock"></i> <?php echo get_the_date(); ?> </time><h4><a href="<?php the_permalink()?>"><?php the_title(); ?></a></h4></div>
                    </div>
                  </div>
                  <?php
                endforeach;
                ?>
              </div>

            </div>

          </div>
        </div>
        <?php get_template_part('/layouts/sections/global/cta'); ?>
      </div>

    <?php endwhile; else : get_template_part('loops/404'); endif; ?>

    </div><!-- /#content -->
  </article>


  <?php get_footer(); ?>
