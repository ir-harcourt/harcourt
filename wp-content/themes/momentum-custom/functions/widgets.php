<?php
/**!
* Widgets
*/

function momentumst_widgets_init() {

  // Sidebar (one widget area)
  register_sidebar( array(
    'name'            => __( 'Sidebar', 'momentumst' ),
    'id'              => 'sidebar-widget-area',
    'description'     => __( 'The sidebar widget area', 'momentumst' ),
    'before_widget'   => '<section class="%1$s %2$s">',
    'after_widget'    => '</section>',
    'before_title'    => '<h3>',
    'after_title'     => '</h3>',
  ) );

  // Industries
  register_sidebar( array(
    'name'            => __( 'Industries', 'momentumst' ),
    'id'              => 'sidebar-widget-area-2',
    'description'     => __( 'The sidebar widget area', 'momentumst' ),
    'before_widget'   => '<section class="%1$s %2$s">',
    'after_widget'    => '</section>',
    'before_title'    => '<h3>',
    'after_title'     => '</h3>',
  ) );

  // Lunch and Learn
  register_sidebar( array(
    'name'            => __( 'Lunch & Learn', 'momentumst' ),
    'id'              => 'sidebar-widget-area-3',
    'description'     => __( 'The sidebar widget area', 'momentumst' ),
    'before_widget'   => '<section class="%1$s %2$s">',
    'after_widget'    => '</section>',
    'before_title'    => '<h3>',
    'after_title'     => '</h3>',
  ) );

  // FAQ
  register_sidebar( array(
    'name'            => __( 'FAQ', 'momentumst' ),
    'id'              => 'sidebar-widget-area-4',
    'description'     => __( 'The sidebar widget area', 'momentumst' ),
    'before_widget'   => '<section class="%1$s %2$s">',
    'after_widget'    => '</section>',
    'before_title'    => '<h3>',
    'after_title'     => '</h3>',
  ) );

  // Blog
  register_sidebar( array(
    'name'            => __( 'Blog', 'momentumst' ),
    'id'              => 'sidebar-widget-area-5',
    'description'     => __( 'The sidebar widget area', 'momentumst' ),
    'before_widget'   => '<section class="%1$s %2$s">',
    'after_widget'    => '</section>',
    'before_title'    => '<h3>',
    'after_title'     => '</h3>',
  ) );

  /*
  Footer (1, 2, 3, or 4 areas)

  Flexbox `col-sm` gives the correct the column width:

  * If only 1 widget, then this will have full width ...
  * If 2 widgets, then these will each have half width ...
  * If 3 widgets, then these will each have third width ...
  * If 4 widgets, then these will each have quarter width ...
  ... above the Bootstrap `sm` breakpoint.
  */

  register_sidebar( array(
    'name'            => __( 'Footer', 'momentumst' ),
    'id'              => 'footer-widget-area',
    'description'     => __( 'The footer widget area', 'momentumst' ),
    'before_widget'   => '<div class="%1$s %2$s col-sm">',
    'after_widget'    => '</div>',
    'before_title'    => '<h3>',
    'after_title'     => '</h3>',
  ) );
  register_sidebar(  array(
    'name'          => 'UK Sidebar',
    'id'            => 'uk-sidebar',
    'description'   => 'This is the sidebar for the UK Contact page.',
    'class'         => '',
    'before_widget' => '<div id="%1$s" class="widget %2$s">',
    'after_widget'  => '</div>',
    'before_title'  => '<h3 class="widgettitle">',
    'after_title'   => '</h3>' 
  )

);

register_sidebar( array(
    'name'          => 'France Sidebar',
    'id'            => 'france-sidebar',
    'description'   => 'This is the sidebar for the France Contact page.',
    'class'         => '',
    'before_widget' => '<div id="%1$s" class="widget %2$s">',
    'after_widget'  => '</div>',
    'before_title'  => '<h3 class="widgettitle">',
    'after_title'   => '</h3>' 
  )

);

register_sidebar( array(
    'name'          => 'India Sidebar',
    'id'            => 'india-sidebar',
    'description'   => 'This is the sidebar for the India Contact page.',
    'class'         => '',
    'before_widget' => '<div id="%1$s" class="widget %2$s">',
    'after_widget'  => '</div>',
    'before_title'  => '<h3 class="widgettitle">',
    'after_title'   => '</h3>' 
  )

);


}
add_action( 'widgets_init', 'momentumst_widgets_init' );


function acf_load_sidebar_field_choices( $field ) {

  // reset choices
  $field['choices'] = array();


  // if has rows
  foreach ( $GLOBALS['wp_registered_sidebars'] as $sidebar ) {


    // vars
    $value = ucwords( $sidebar['id'] );
    $label = ucwords( $sidebar['name'] );


    // append to choices
    $field['choices'][ $value ] = $label;

  }

  // return the field
  return $field;

}

add_filter('acf/load_field/name=sidebar_select', 'acf_load_sidebar_field_choices');
