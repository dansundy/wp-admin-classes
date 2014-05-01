<?php

class Empyre_Register_Custom_Post_Type {    
  private $data;

  public function __construct( $cpt_data ) {
    $this->data = $cpt_data;

    $this::add_custom_post();
  }

  public function add_custom_post() {
    $data     = $this->data;
    $name     = $data->labels['name'];
    $singular = $data->labels['singular'];
    $plural   = $data->labels['plural'];

    $labels = array(
      'name'               => $name,
      'singular_name'      => $plural,
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
      'supports'      => $data->supports,
      'has_archive'   => ! isset( $data->archive ) ? true : $data->archive,
      'taxonomies'    => isset( $data->taxonomies ) ? $data->taxonomies : null
    );

    register_post_type( $data->id, $args );
  }
}

?>