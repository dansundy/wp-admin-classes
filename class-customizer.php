<?php

class Empyre_Option_Functions {
  // Switch for different input types.
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

    // Adjust brightness of hex value.
    public function adjust_brightness($hex, $value) {
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $value = max(-255, min(255, $value));

        // Format the hex color string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
        }

        // Get decimal values
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));

        // Adjust number of steps and keep it inside 0 to 255
        $r = max(0,min(255,$r + $value));
        $g = max(0,min(255,$g + $value));  
        $b = max(0,min(255,$b + $value));

        $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

        return '#'.$r_hex.$g_hex.$b_hex;
    }

    // Get the appropriate font stack based on a key value.
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
            Empyre_Option_Functions::set_controls($wp_customize, $setting_id, $args, $option_setting->class);

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
                    /*$settings = array(
                        'selector'  => $option_setting->selector,
                        'property'  => $option_setting->property,
                        'group'     => $option_setting->groupid,
                        'setting'   => $option_setting->id,
                        'default'   => $option_setting->default
                    );*/

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

                    // TODO: Remove this automated brightness setting.
                    /*if ( isset( $option_setting->spectrum ) ) {
                        $light = Empyre_Option_Functions::adjust_brightness( $value, 80 );
                        $dark = Empyre_Option_Functions::adjust_brightness( $value, -80 );
                        $selectors = explode(', ', $option_setting->selector);

                        foreach($selectors as $selector) {
                            printf( '%s { %s:%s; }',
                                $selector . ' .text-color-light, ' . $selector . ' a.text-color-light',
                                $option_setting->property,
                                $light
                            );

                            printf( '%s { %s:%s; }',
                                $selector . ' .text-color-dark, ' . $selector . ' a.text-color-dark',
                                $option_setting->property,
                                $dark
                            );
                        }
                    }*/
                }
            }
            
            // Now output any custom overrides in the Custom CSS setting.
            $grp = get_theme_mod( 'empyre_css' );            

            if ( ! empty( $grp[ 'custom_css' ] ) )
                echo $grp[ 'custom_css' ];
        ?>
        </style> 
        <!--/Customizer CSS-->

    <?php
   }
   
   /**
    * This outputs the javascript needed to automate the live settings preview.
    * Also keep in mind that this function isn't necessary unless your settings 
    * are using 'transport'=>'postMessage' instead of the default 'transport'
    * => 'refresh'
    * 
    * Used by hook: 'customize_preview_init'
    * 
    * @see add_action('customize_preview_init',$func)
    * @since MyTheme 1.0
    */
/*  public static function live_preview() {
    wp_enqueue_script(
        'empyre-themecustomizer', //Give the script an ID
        get_template_directory_uri().'assets/js/theme-customizer.js', //Define it's JS file
        array( 'jquery','customize-preview' ), //Define dependencies
        '', //Define a version (optional) 
        true //Specify whether to put in footer (leave this true)
    );
  }*/

    public static function generate_css( $selector, $style, $value, $echo = true ) {
        $return = '';
        //$section = get_theme_mod($section_name);
        //if ( isset( $section[ $setting_name ] ) ) {
            //return;
            //$mod = $section[ $setting_name ];
        //} else {

        //}



        if ( $style == 'font-family' )
            $value = Empyre_Option_Functions::generate_font_stack( $value );
          

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
}

//Enqueue live preview javascript in Theme Customizer admin screen
//add_action( 'customize_preview_init' , array( 'MyTheme_Customize' , 'live_preview' ) );

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