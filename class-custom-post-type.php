<?php

if ( ! class_exists( 'Helios_Register_Custom_Post_Type' ) ) :

  class Helios_Register_Custom_Post_Type {    
    private $data;

    public function __construct( $cpt_data ) {
      $this->data = $cpt_data;

      $this::add_custom_post();

      if ( isset( $cpt_data->taxonomies ) )
        $this::add_taxonomy();

    }

    public function add_custom_post() {
      $data     = $this->data;
      $name     = $data->labels['name'];
      $singular = $data->labels['singular'];
      $plural   = $data->labels['plural'];

      $labels = array(
        'name'               => $name,
        'singular_name'      => $singular,
        'add_new'            => 'Add New',
        'add_new_item'       => sprintf( 'Add New %s', $singular ),
        'edit_item'          => sprintf( 'Edit %s', $singular ),
        'new_item'           => sprintf( 'New %s', $singular ),
        'all_items'          => sprintf( 'All %s', $plural ),
        'view_item'          => sprintf( 'View %s', $singular ),
        'search_items'       => sprintf( 'Search %s', $plural ),
        'not_found'          => sprintf( 'No %s found', $plural ),
        'not_found_in_trash' => sprintf( 'No %ss found in the Trash', $plural ),
        'parent_item_colon'  => '',
        'menu_name'          => $name
      );

      $args = array(
        'labels'        => $labels,
        'description'   => $data->description,
        'public'        => ! isset( $data->public ) ? true : $data->public,
        'menu_position' => isset( $data->position ) ? $data->position : null,
        'menu_icon'     => isset( $data->menu_icon ) ? $data->menu_icon : null,
        'supports'      => $data->supports,
        'has_archive'   => ! isset( $data->archive ) ? true : $data->archive,
        'taxonomies'    => isset( $data->tax ) ? $data->tax : array()
      );

      register_post_type( $data->id, $args );
    }

    public function add_taxonomy() {
      $data     = $this->data;

      foreach( $data->taxonomies as $tax ) {
        $name     = $tax->name;
        $singular = $tax->singular;
        $plural   = $tax->plural;


        $labels = array(
          'name'              => $name,
          'singular_name'     => $singular,
          'search_items'      => sprintf( 'Search %s', $plural ),
          'all_items'         => sprintf( 'All %s', $plural ),
          'parent_item'       => sprintf( 'Parent %s', $singular ),
          'parent_item_colon' => sprintf( 'Parent %s:', $singular ),
          'edit_item'         => sprintf( 'Edit %s', $singular ), 
          'update_item'       => sprintf( 'Update %s', $singular ),
          'add_new_item'      => sprintf( 'Add New %s', $singular ),
          'new_item_name'     => sprintf( 'New %s', $singular ),
          'menu_name'         => sprintf( '%s', $plural ),
        );
        $args = array(
          'labels' => $labels,
          'hierarchical' => ! isset( $tax->hierarchical ) ? true : $tax->hierarchical,
          'show_admin_column' => ! isset( $tax->admin_column ) ? true : $tax->admin_column
        );

        register_taxonomy( $tax->id, $data->id, $args );
      }
    }
  }

endif;

?>