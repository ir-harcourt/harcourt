<?php
/**!
* The Sidebar
* Note: The main column has simply Bootstrap flexbox "col-sm" so it will expand
* to occupy the whole row (if no sidebar) or to occupy whatever part of the row
* is available (if there is a sidebar, or more than one sidebar, etc.).
*
* (So, you don't need to set the main column to col-sm-8 or whatever.)
*/

$sidebar = get_field('sidebar_select');

?>
<div class="sidebar col-lg-4" id="sidebar" role="navigation">

  <div class="sidebar-widget-container black-radial-gradient">

<?php if( is_active_sidebar('sidebar-widget-area') ): ?>


    <?php if( 'faq' == get_post_type( $post ) ): ?>
      <?php dynamic_sidebar('sidebar-widget-area-4'); ?>
    <?php else: ?>
        <?php dynamic_sidebar($sidebar); ?>
    <?php endif; ?>

  </div>

    <div class="scscpq_newuser mt-4 red-radial-gradient text-center p-5">
      <h3 class="mt-0 mb-4">Get Access to Our Full Catalog</h3>
      <a class="standard-btn ignore-hover white mt-0 w-100" href="<?php echo home_url('/'); ?>request-access">Request Access</a>
    </div>
  </div>



<?php endif; ?>
