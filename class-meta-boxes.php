<?php

class Empyre_Add_Meta_Box {

    private $boxes;

    /**
     * Hook into the appropriate actions when the class is constructed.
     */
    public function __construct( $meta_box_data ) {
        $this->boxes = $meta_box_data;
        
        add_action( 'add_meta_boxes_' . $this->boxes['post_type'], array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta_box' ) );

        add_filter( 'empyre_check_template', array( $this, 'check_template' ), 10, 2 );
    }

    /**
     * Adds the meta box container.
     */
    public function add_meta_box( $post ) {
        //$post_types = array('post', 'page');     //limit meta box to certain post types
        //render_var($post_type);
        $box = $this->boxes;

        if ( ! empty( $box['template'] ) && ! apply_filters( 'empyre_check_template', true, $this->boxes ) )
            return;

        add_meta_box(
            $box['id'],
            __( $box['headline'], 'empyre' ),
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
        if ( ! isset( $_POST['empyre_inner_custom_box_nonce'] ) )
            return $post_id;

        $nonce = $_POST['empyre_inner_custom_box_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'empyre_inner_custom_box' ) )
            return $post_id;

        // If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
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

            // Sanitize the user input.
            if ( $field->type == 'text' ) {
                $value = sanitize_text_field( $_POST[ $key ] );
            } else {
                $value = $_POST[ $key ];
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
        wp_nonce_field( 'empyre_inner_custom_box', 'empyre_inner_custom_box_nonce' );

        foreach( $fields as $field ) {

            // Use get_post_meta to retrieve an existing value from the database.
            $post_value = get_post_meta( $post->ID, $field->id, true );

            $default = isset( $field->default ) ? $field->default : '';
            $value = ! empty( $post_value ) ? $post_value : $default;
            $label = ! empty( $field->label ) ? sprintf( '<label for="%s">%s </label>', $field->id, $field->label ) : '';
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
                    $checked = checked( $value, 'on', false );
                    printf('%1$s<input type="checkbox" id="%2$s" name="%2$s" %3$s>%4$s',
                        $label,
                        $field->id,
                        $checked,
                        ! empty( $field->after ) ? ' <span class="meta-after">' . $field->after . '</span>' : ''
                    );
                    break; 
            }     

            echo '</p>';
            
        }
    }
}

?>
