<?php
/*
Template Name: Product (Boxjoint)
Template Post Type: page
*/

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

get_template_part('/layouts/sections/product-single/hero');
get_template_part('/layouts/sections/product-single/content');
?>


<section class="boxjoint-history-section">

    <div class="container">

        <div class="row">

            <div class="boxjoint-history-content col-md-6 col-lg-8">

                <h2 class="boxjoint-history-h2"><?php echo get_field('history_heading') ?></h2>
                <div class="boxjoint-history-p"><?php echo get_field('history_content') ?></div>

            </div>
            <div class="boxjoint-history-media col-md-6 col-lg-4">

                <div class="boxjoint-history-image">
                    <?php  
                    if( !empty( get_field('history_image') ) ): ?>
                    <img src="<?php echo esc_url(get_field('history_image')['url']); ?>" alt="<?php echo esc_attr(get_field('history_image')['alt']); ?>" />
                    <?php endif; ?>
                </div>




            </div>

        </div>

    </div>


</section>


<section class="product-cta black-radial-gradient"> <?php
    get_template_part('/layouts/sections/global/cta');
    ?> </section> 

<?php
endwhile;

//404
else: get_template_part('/loops/index-post', 'none');

endif;


get_footer();

?>
