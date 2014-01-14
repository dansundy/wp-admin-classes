<?php

class Empyre_Customize {

    public static function register( $wp_customize ) {
      
        global $options;

        foreach ($options as $option) {

            $wp_customize->add_section( $option->id,
                array(
                    'title'       => __( $option->title, 'empyre' ),
                    'priority'    => isset( $option->priority ) ? $option->priority : 120,
                    'capability'  => 'edit_theme_options',
                    'description' => isset( $option->description) ? __( $option->description, 'empyre' ) : ''
                )
            );

            $priority = 0;
            foreach ($option->fields as $field) {

                $wp_customize->add_setting( $option->id . '[' . $field->id . ']',
                    array(
                        'default'     => isset( $field->default ) ? $field->default : '',
                        'type'        => isset( $field->type) ? $field->type : 'theme_mod',
                        'capability'  => 'edit_theme_options',
                        'transport'   => 'refresh'
                    )
                );

                $args = array(
                    'label'     => __( $field->label, 'empyre' ), //Admin-visible name of the control
                    'section'   => $option->id, //ID of the section this control should render in (can be one of yours, or a WordPress default section)
                    'settings'  => $option->id . '[' . $field->id . ']', //Which setting to load and manipulate (serialized is okay)
                    'priority'  => isset( $field->priority) ? $field->priority : $priority //Determines the order this control appears in for the specified section
                );

                if ( $field->class == 'select' || $field->class == 'checkbox' ) {
                    $args['type'] = $field->class;     

                    if ( ! empty( $field->choices ) )
                        $args['choices'] = $field->choices;
                }

                if ( ! empty( $field->description ) )
                    $args['description'] = $field->description;

                $setting_id = $option->id . '_' . $field->id;
                self::set_controls( $wp_customize, $setting_id, $args, $field->class );

                $priority++;
            }
        }
    }

    public static function header_output() {

        global $options;
        ?>

        <!--Customizer CSS--> 
        <style type="text/css">
        <?php
            foreach( $options as $option) {
                foreach( $option->fields as $field ) {
                    
                    if ( ! empty( $field->css ) ) {

                        // The current values.
                        $values = isset( $field->type ) && $field->type == 'option' ? get_option( $option->id ) : get_theme_mod( $option->id );

                        $default = ! empty( $field->default ) ? $field->default : null;
                        // If there is no value currently set use the default.
                        $value = empty( $values[ $field->id ] ) ? $default : $values[ $field->id ];

                        
                        
                        // If the default is also empty, don't output CSS.
                        if ( empty( $value ) )
                            continue;

                        foreach( $field->css as $css) {
                            
                            // If there is a google font set, don't output the CSS for the dropdown.
                            if ( $css[0] === 'font-family' && ! empty( $values[ $field->id . '_google' ] ) )
                                continue;
                                

                            // If there are some tweaks to the value needed, do them.
                            if ( $css[0] === 'background-image' )
                                $value = "url('$value')";

                            self::generate_css( $css[1], $css[0], $value );
                        }              
                    }
                }
            }
            
            // Now output any custom overrides in the Custom CSS setting.
            $grp = get_theme_mod( 'empyre_custom_css' );            

            if ( ! empty( $grp['custom_css'] ) )
                echo $grp['custom_css'];
        ?>
        </style> 
        <!--/Customizer CSS-->

    <?php
    }

    public static function generate_css( $selector, $style, $value, $echo = true ) {
        $return = '';

        if ( $style == 'font-family' )
            $value = self::generate_font_stack( $value );

        if ( ! empty( $value ) ) {        
            $return = sprintf('%s { %s:%s; }',
                $selector,
                $style,
                $value
            );
            if ( $echo ) {
                echo $return;
            }
        }

        return $return;
    }

    public function set_controls( $wp_customize, $id, $args, $type ) {
        switch($type) {
            case "color":
                $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, $args ) );
                break;
            case "image":
                $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $id, $args ) );
                break;
            case "textarea":
                $wp_customize->add_control( new Empyre_Customize_Textarea_Control( $wp_customize, $id, $args ) );
                break;
            case "customtext":
                $wp_customize->add_control( new Empyre_Custom_Text_Control( $wp_customize, $id, $args ) );
                break;
            default:
                $wp_customize->add_control( $id, $args );      
                break;
        }
    }

    public function generate_font_stack( $key ) {
        $fonts = (object) array(
            'helvetica' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
            'lucida'    => '"Lucida Grande", "Lucida Sans", Geneva, Verdana, sans-serif',
            'verdana'   => 'Verdana, Geneva, sans-serif',
            'georgia'   => 'Georgia, Times, "Times New Roman", serif',
            'times'     => '"Times New Roman", Times, Georgia, serif'
        );
        
        $stack = isset( $fonts->$key ) ? $fonts->$key : "'$key', sans-serif";

        return $stack;
    }
}


// Create a Textarea Control
if ( class_exists( 'WP_Customize_Control' ) ) {

    class Empyre_Customize_Textarea_Control extends WP_Customize_Control {
        public $type = 'textarea';
     
        public function render_content() {
            ?>
            <label>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <textarea rows="15" style="width:100%;" <?php $this->link(); ?>><?php echo esc_textarea( $this->value() ); ?></textarea>
            </label>
            <?php
        }
    }

    class Empyre_Custom_Text_Control extends WP_Customize_Control {
        public $type = 'customtext';
        public $description = '';

        public function render_content() { ?>
            <label>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <input type="text" value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?> />
                <p class="description"><?php echo esc_html( $this->description ); ?></p>
            </label>
        <?php }
    }

}

?>