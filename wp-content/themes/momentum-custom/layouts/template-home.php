<?php
/*
Template Name: Homepage
Template Post Type: page
*/

get_header();

//loop
if(have_posts()): while(have_posts()): the_post();

//sections

get_template_part('/layouts/sections/home/hero');
get_template_part('/layouts/sections/home/innovations');
get_template_part('/layouts/sections/home/products');
get_template_part('/layouts/sections/home/industries');
get_template_part('/layouts/sections/home/tradition');
?>

<section class="resource-bar red-radial-gradient">
  <div class="container">
    <span data-aos-duration="1000" data-aos="fade-right" class="slash-heading h4">Social Feed</span>
  </div>
</section>

<section class="black-radial-gradient site-padding-bl">
  <?php
  get_template_part('/layouts/sections/home/social-feed');
  get_template_part('/layouts/sections/home/red-box');
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

<script type="text/javascript">
jQuery('.logo-carousel').owlCarousel({
  loop:true,
  dots:true,
  items: 2,
  responsive : {
    // breakpoint from 0 up
    568 : {
      items: 2,
    },
    // breakpoint from 480 up
    768 : {
      items: 3,
    },
    // breakpoint from 768 up
    991 : {
      items: 5,
    }
  }
})
</script>
