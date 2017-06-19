<?php

class MPI_Theme_Settings {

  private $tab_descriptions = array();
  private $section_descriptions = array();
  private $tabs = array();
  //private $count = 0;
  
  function __construct( $menu, $data ) {

    $this->menu = $menu;
    $this->data = $data;
    $this->current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $data[0]->id;

    add_action( 'admin_init', array( &$this, 'register_settings' ) );
    add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
    add_action( 'admin_notices', array( &$this, 'add_admin_error' ) );
  }

  function add_admin_error() {

    foreach ($this->data as $tab) {
      if ( ! isset( $tab->errors) || $tab->id != $this->current_tab ) continue;
      
      foreach( $tab->errors as $id => $error ) {
        ?>
        <div id="<?php echo $id; ?>" class="error" style="display:none;">
          <p><?php echo $error; ?></p>
        </div>
        <?php
      }
    }
  }
  
  function register_settings() {
    
    foreach ( $this->data as $tab ) {
      $this->tabs[ $tab->id ] = $tab->title;

      foreach ( $tab->sections as $section ) {
        $this->section_descriptions[ $section->id ] = isset( $section->description ) ? $section->description : '';

        register_setting( $tab->id, $section->id );
        add_settings_section( $section->id, $section->title, array( &$this, 'render_section_description' ), $tab->id );
        

        if ( $tab->id != $this->current_tab || empty( $section->fields ) )
          continue;

        foreach ( $section->fields as $field ) {

          $options = (array) get_option( $section->id );

          // For developer use only. This resets the tab or section to its defaults.
          // TODO: Remove for production - tab defalut reset.
          if ( isset( $section->reset ) || isset( $tab->reset ) ) {
            $options = array();
          }

          if ( isset( $field->defaults ) && is_array( $field->defaults ) ) {
            foreach( $field->boxes as $id => $value ) {
              if ( ! isset( $options[ $field->id ][$id] ) ) {
                $options[ $field->id ][ $id ] = isset($field->defaults[$id]) ? $field->defaults[$id] : '';
                update_option( $section->id, $options );
              }
            }
          } elseif ( ! isset( $options[ $field->id ] ) ) {
            $options[ $field->id ] = isset( $field->default ) ? $field->default : '';
            update_option( $section->id, $options );
          }

          add_settings_field( 
            $field->id,
            $field->label,
            array( &$this, 'render_fields' ),
            $tab->id,
            $section->id, 
            array(
              'section'     => $section->id,
              'type'        => $field->type,
              'id'          => $field->id,
              'value'       => ! is_array( $field->id ) ? $options[ $field->id ] : null,
              'size'        => isset( $field->size ) ? $field->size : null,
              'class'       => isset( $field->class ) ? $field->class : null,
              'desc'        => isset( $field->description ) ? $field->description : '',
              'choices'     => isset( $field->choices ) ? $field->choices : '',
              'placeholder' => isset( $field->placeholder ) ? $field->placeholder : null,
              'boxes'       => isset( $field->boxes ) ? $field->boxes : null,
              'rows'        => isset( $field->rows ) ? $field->rows : null,
              'cols'        => isset( $field->cols ) ? $field->cols: null
            ) 
          );
        }
      }
    }
  }

  function render_section_description( $s ) {

    $desc = $this->section_descriptions[ $s['id'] ];
    
    if ( ! empty( $desc ) ) echo $desc;

  }

  function render_fields( $args ) {
    switch ($args['type']) {
      case 'text':
        printf( '<input id="%2$s" type="text" name="%1$s[%2$s]" value="%3$s"%4$s%5$s%6$s>',
          $args['section'],
          $args['id'],
          esc_attr( $args['value'] ),
          isset( $args['class'] ) ? " class='{$args['class']}'" : "",
          isset( $args['size'] ) ? " size='{$args['size']}'" : "",
          isset( $args['placeholder'] ) ? " placeholder='{$args['placeholder']}'" : ""
        );
        break;
      case 'textarea':
        printf( '<textarea id="%2$s" name="%1$s[%2$s]" cols="%4$s" rows="%5$s"%6$s>%3$s</textarea>',
          $args['section'],
          $args['id'],
          esc_attr( $args['value'] ),
          isset( $args['cols'] ) ? $args['cols'] : 60,
          isset( $args['rows'] ) ? $args['rows'] : 10,
          isset( $args['class'] ) ? " class='{$args['class']}'" : ""
        );
        break;
      case 'checkbox':
        printf ('<input name="%1$s[%2$s]" type="hidden" value="0"><input name="%1$s[%2$s]" type="checkbox" value="1" class="code%4$s" %3$s>',
          $args['section'],
          $args['id'],
          checked( 1, $args['value'], false ),
          isset( $args['class'] ) ? " class='{$args['class']}'" : ""
        );
        break;
      case 'multicheckbox':
        foreach( $args['boxes'] as $id => $label ) {
          $options = get_option( $args['section'] );
          printf ('<input name="%1$s[%2$s][%3$s]" type="hidden" value="0"><input name="%1$s[%2$s][%3$s]" type="checkbox" value="1" class="code%5$s" %4$s> %6$s<br>',
            $args['section'],
            $args['id'],
            $id,
            checked( 1, $options[ $args['id'] ][ $id ], false ),
            isset( $args['class'] ) ? " class='{$args['class']}'" : "",
            $label
          );
        }
        break;
      case 'select':
        $options = get_option( $args['section'] );
        $val = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $args['default'];
        printf ( '<select id="%2$s" name="%1$s[%2$s]%3$s">',
          $args['section'], 
          $args['id'],
          isset( $args['class'] ) ? " class='{$args['class']}'" : ""
        );
        foreach( $args['choices'] as $k => $v ) {
          printf( '<option value="%s" %s>%s</option>', $k, selected( $val, $k ), $v );
        }
        echo '</select>';
        break;
    }
  
    if ( ! empty( $args['desc'] ) )
      printf( '<p class="description">%s</p>', $args['desc']);
  }
 
  function add_admin_menus() {
    if ($this->menu[0] == 'settings') {
      add_options_page( $this->menu[1], $this->menu[2], $this->menu[3], $this->menu[4], array( &$this, 'render_settings_page' ) );
    } elseif ($this->menu[0] == 'appearance') {
      add_theme_page( $this->menu[1], $this->menu[2], $this->menu[3], $this->menu[4], array( &$this, 'render_settings_page' ) );
    }
  }

  function render_settings_page() {
    $tab = $this->current_tab;
    ?>
    <div class="wrap">
      <?php self::render_settings_tabs(); ?>
      <form method="post" action="options.php">
        <?php wp_nonce_field( 'update-options' ); ?>
        <?php settings_fields( $tab ); ?>
        <?php do_settings_sections( $tab ); ?>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }

  function render_settings_tabs() {
    screen_icon();
    echo '<h2 class="nav-tab-wrapper">';
    foreach ( $this->tabs as $k => $v) {
      $active = $this->current_tab == $k ? 'nav-tab-active' : '';
      echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->menu[4] . '&tab=' . $k . '">' . $v . '</a>'; 
    }
    echo '</h2>';
  }
};

?>