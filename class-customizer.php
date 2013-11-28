<?php

class Empyre_Customize {

    public static function register ( $wp_customize ) {
      
        global $option_groups, $option_settings;

        $priority = 120;
        foreach ($option_groups as $option_group) {
            $wp_customize->add_section( $option_group->id,
                array(
                    'title'       => __( $option_group->title, 'empyre' ),
                    'priority'    => $priority,
                    'capability'  => 'edit_theme_options',
                    'description' => __( $option_group->description, 'empyre' )
                )
            );
            $priority++;
        }

        $priority = 100;
        foreach ($option_settings as $option_setting) {

            // Push defaults to defaults object for future reference.
            $option_defaults[ $option_setting->id ] = isset( $option_setting->default ) ? $option_setting->default : '';

            $wp_customize->add_setting( $option_setting->groupid . '[' . $option_setting->id . ']',
                array(
                    'default'     => $option_defaults[$option_setting->id],
                    'type'        => 'theme_mod',
                    'capability'  => 'edit_theme_options',
                    'transport'   => 'refresh'
                )
            );

            $args = array(
                'label'     => __( $option_setting->label, 'empyre' ), //Admin-visible name of the control
                'section'   => $option_setting->groupid, //ID of the section this control should render in (can be one of yours, or a WordPress default section)
                'settings'  => $option_setting->groupid . '[' . $option_setting->id . ']', //Which setting to load and manipulate (serialized is okay)
                'priority'  => $priority //Determines the order this control appears in for the specified section
            );

            if ( $option_setting->class == 'select' || $option_setting->class == 'checkbox' ) {
                $args['type'] = $option_setting->class;     

                if ( ! empty( $option_setting->choices ) )
                    $args['choices'] = $option_setting->choices;
            }

            if ( ! empty( $option_setting->description ) )
                $args['description'] = $option_setting->description;

            $setting_id = $option_setting->groupid . '_' . $option_setting->id;
            self::set_controls( $wp_customize, $setting_id, $args, $option_setting->class );

            $priority++;
        }
    }

    public static function header_output() {

        global $option_groups, $option_settings;
        ?>

        <!--Customizer CSS--> 
        <style type="text/css">
        <?php
            foreach( $option_settings as $option_setting ) {
                
                if ( isset( $option_setting->selector ) ) {

                    // The current values.
                    $values = get_theme_mod( $option_setting->groupid );
                    
                    // If there is no value currently set use the default.
                    $value = empty( $values[ $option_setting->id ] ) ? $option_setting->default : $values[ $option_setting->id ];

                    // If the default is also empty, don't output CSS.
                    if ( empty( $value ) || ! isset( $option_setting->property ) )
                        continue;
                    
                    // If there is a google font set, don't output the CSS for the dropdown.
                    if ( $option_setting->selector === 'font-family' && isset( $values[ $value . '_google' ] ) )
                        continue;

                    // If there are some tweaks to the value needed, do them.
                    if ( $option_setting->property === 'background-image' )
                        $value = "url('$value')";

                    self::generate_css( $option_setting->selector, $option_setting->property, $value );

                    if ( $option_setting->id === 'site_link_color' ) {
                        // CSS settings based on link color
                        $selector = '#header-search form input[type="text"]:focus, form.form-search input[type="text"]:focus';
                        self::generate_css($selector, 'border-color', $value);
                    }                  
                }
            }
            
            // Now output any custom overrides in the Custom CSS setting.
            $grp = get_theme_mod( 'empyre_css' );            

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
                $wp_customize->add_control($id, $args);      
                break;
        }
    }

    public function generate_font_stack($key) {
        $fonts = (object) array(
            'helvetica' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
            'lucida'    => '"Lucida Grande", "Lucida Sans", Geneva, Verdana, sans-serif',
            'verdana'   => 'Verdana, Geneva, sans-serif',
            'georgia'   => 'Georgia, Times, "Times New Roman", serif',
            'times'     => '"Times New Roman", Times, Georgia, serif'
        );
        
        $stack = isset($fonts->$key) ? $fonts->$key : "'$key', sans-serif";

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