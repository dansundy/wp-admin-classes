<?php

if ( ! class_exists( 'Helios_Add_Meta_Box' ) ) :

  class Helios_Add_Meta_Box {

    private $boxes;

    /**
     * Hook into the appropriate actions when the class is constructed.
     */
    public function __construct( $meta_box_data ) {
      $this->boxes = $meta_box_data;
      
      add_action( 'add_meta_boxes_' . $this->boxes['post_type'], array( $this, 'add_meta_box' ) );
      add_action( 'save_post_' . $this->boxes['post_type'], array( $this, 'save_meta_box' ) );

      add_filter( 'helios_check_template', array( $this, 'check_template' ), 10, 2 );
    }

    /**
     * Descendant category checking utility function.
     */
    public function check_descendant_category( $cats, $_post = null ) {

      foreach ( (array) $cats as $cat ) {
        // get_term_children() accepts integer ID only
        $descendants = get_term_children( (int) $cat, 'category' );
        if ( $descendants && in_category( $descendants, $_post ) ) {
          return true;
        }
      }
      return false;
    }

    /**
     * Adds the meta box container.
     */
    public function add_meta_box( $post ) {
      //$post_types = array('post', 'page');     //limit meta box to certain post types
      //render_var($post_type);
      $box = $this->boxes;

      if ( ! empty( $box['parent_category'] ) ) {
        $category = get_term_by( 'name', $box['parent_category'], 'category' );
        if ( ! in_category( $box['parent_category'] ) && ! $this->check_descendant_category( $category->term_id ) ) {
          return;
        }
      }

      if ( ! empty( $box['template'] ) && ! apply_filters( 'helios_check_template', true, $this->boxes ) )
        return;

      add_meta_box(
        $box['id'],
        $box['headline'],
        array( $this, 'render_meta_box' ),
        $post->post_type,
        $box['context'],
        $box['priority'],
        array(
          'description' => isset( $box['description'] ) ? $box['description'] : '',
          'fields'      => $box['fields']
        )
      );
    }

    public function check_template( $display, $box ) {
      if ( empty( $box['template'] ) )
        return false;

      if ( isset( $_GET['post'] ) ) {
        $post_id = $_GET['post'];
      } elseif ( isset( $_POST['post_ID'] ) ) {
        $post_id = $_POST['post_ID'];
      }

      if ( ! ( isset( $post_id ) || is_page() ) ) return false;

      $templ = get_post_meta( $post_id, '_wp_page_template', true );

      $box['template'] = ! is_array( $box['template'] ) ? array( $box['template'] ) : $box['template'];

      if ( in_array( $templ, $box['template'] ) ) {
        return true;
      } else {
        return false;
      }
    }

    /**
     * Save the meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_meta_box( $post_id ) {
    
      /*
       * We need to verify this came from the our screen and with proper authorization,
       * because save_post can be triggered at other times.
       */

      // Check if our nonce is set.
      if ( ! isset( $_POST['helios_inner_custom_box_nonce'] ) )
        return $post_id;

      $nonce = $_POST['helios_inner_custom_box_nonce'];

      // Verify that the nonce is valid.
      if ( ! wp_verify_nonce( $nonce, 'helios_inner_custom_box' ) )
        return $post_id;

      // If this is an autosave, our form has not been submitted,
      // so we don't want to do anything.
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return $post_id;

      // Check the user's permissions.
      if ( 'page' == $_POST['post_type'] ) {

        if ( ! current_user_can( 'edit_page', $post_id ) )
          return $post_id;
    
      } else {

        if ( ! current_user_can( 'edit_post', $post_id ) )
          return $post_id;
      }

      /* OK, its safe for us to save the data now. */

      foreach( $this->boxes['fields'] as $field ) {
        $key = $field->id;

        // Check to make sure the key exists.
        // if ( ! isset( $_POST[ $key ] ) )
        //   continue;  

        if ( $field->type == 'output' ) {
          continue;
        }

        // Sanitize the user input.
        if ( $field->type == 'checkbox-group' ) {
          $unprocessed = isset( $_POST[ $key ] ) ? $_POST[ $key ] : array();
          $value = array();
          if ( is_array( $unprocessed ) && count( $unprocessed ) > 1) {
            foreach( $unprocessed as $val ) { $value[] = (int) $val; }
            $value = implode(',', $value);
          } else {
            $value = isset( $_POST[ $key ][0] ) ? $_POST[ $key ][0] : null;
          }
          // $value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : null;
        } elseif ( $field->type == 'text' ) {
          $value = isset( $_POST[ $key ] ) ? sanitize_text_field( $_POST[ $key ] ) : null;
          if ( ! empty( $field->date_format ) ) {
            $value = date( $field->date_format, strtotime( $value ) );
          }
        } else {
          $value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : null;
        }

        // Update the meta field.
        update_post_meta( $post_id, $key, $value );
      }
    }


    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public function render_meta_box( $post, $args ) {
      
      $fields = $args['args']['fields'];

      if ( ! empty( $args['args']['description'] ) ) {
        echo $args['args']['description'];
      }
      // Add an nonce field so we can check for it later.
      wp_nonce_field( 'helios_inner_custom_box', 'helios_inner_custom_box_nonce' );

      foreach( $fields as $field ) {

        // Use get_post_meta to retrieve an existing value from the database.
        $post_value = get_post_meta( $post->ID, $field->id, true );

        $default = isset( $field->default ) ? $field->default : '';
        $value = empty( $post_value ) && $post_value != 0 ? $default : $post_value;

        if ( ! empty( $value ) && ! empty( $field->date_format ) ) {
          $value = date('Y-m-d', strtotime( $value ) );
        }
        
        $label = ! empty( $field->label ) ? sprintf( '<label for="%s" style="width: 200px; display: inline-block;">%s </label>', $field->id, $field->label ) : '';
        $classes = isset( $field->class ) ? ' class="' . implode( ',', $field->class ) . '"' : '';
        
        echo '<p' . $classes . '>';

        switch( $field->type ) {
          case 'text':
            printf('%2$s<input type="text" id="%1$s" name="%1$s" value="%3$s" size="%4$s"%5$s>%6$s',
              $field->id,
              $label,
              esc_attr( $value ),
              ! empty( $field->size ) ? $field->size : 25,
              ! empty( $field->placeholder) ? ' placeholder="' . $field->placeholder . '"' : '',
              ! empty( $field->after ) ? ' <span class="meta-after">' . $field->after . '</span>' : ''
            );
            break;
          case 'select':
            echo $label;
            echo '<select name="' . $field->id . '" id="' . $field->id . '">';
            
            foreach( $field->choices as $k => $v ) {
              $selected = selected( $value, $k, false );//$value == $k ? 'selected' : '';
              echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
            }
            
            echo '</select>';
            break;
          case 'editor':
            wp_editor( $value, $field->id );
            break;
          case 'checkbox':
            printf('%1$s<input type="checkbox" id="%2$s" value="1" name="%2$s" %3$s>%4$s',
              $label,
              $field->id,
              checked( $value, 1, false ),
              ! empty( $field->after ) ? ' <span class="meta-after">' . $field->after . '</span>' : ''
            );
            break;
          case 'checkbox-group':
            $value = explode(',', $value);
            printf( '<p><label for="%s">%s </label></p>', $field->id, $field->label );
            foreach( $field->choices as $k => $v ) {
              printf('<input type="checkbox" id="%1$s" value="%1$s" name="%2$s" %3$s> %4$s<br>',
                $k,
                $field->id . '[]',
                checked( in_array( $k, $value ), true, false ),
                $v
              );
            }
            break;
          case 'textarea':
            printf('%1$s<textarea name="%2$s" id="%2$s" rows="%4$s" cols="%5$s" style="max-width:%6$s;">%3$s</textarea><br>%7$s',
              ! empty( $field->label) ? '<p>' . $label . '</p>' : $label,
              $field->id,
              esc_attr( $value ),
              ! empty( $field->rows ) ? $field->rows : '',
              ! empty( $field->columns) ? $field->columns : '',
              '100%',
              ! empty( $field->after ) ? ' <span class="meta-after">' . $field->after . '</span>' : ''
            );
            break;

          case 'output':
            printf('<pre>%s</pre>', get_post_meta( $post->ID, $field->id, true ) );
            break;
        }     

        echo '</p>';
        
      }
    }
  }
  
endif;