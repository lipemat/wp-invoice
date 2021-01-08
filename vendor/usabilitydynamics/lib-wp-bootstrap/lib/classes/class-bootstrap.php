<?php
/**
 * Bootstrap
 *
 * @namespace UsabilityDynamics
 *
 * This file is being used to bootstrap WordPress theme.
 */
namespace UsabilityDynamics\WP {

  if( !class_exists( 'UsabilityDynamics\WP\Bootstrap' ) ) {

    /**
     * Bootstrap the theme in WordPress.
     *
     * @class Bootstrap
     * @author: peshkov@UD
     */
    class Bootstrap extends Scaffold {

      /**
       * Schemas
       *
       * @public
       * @property schema
       * @var array
       */
      public $schema = null;

      /**
       * Absolute path to schema ( composer.json )
       *
       * @public
       * @property schema_path
       * @var array
       */
      public $schema_path = null;

      /**
       * Admin Notices handler object
       *
       * @public
       * @property errors
       * @var object UsabilityDynamics\WP\Errors object
       */
      public $errors = false;

      /**
       * Settings
       *
       * @public
       * @static
       * @property $settings
       * @type \UsabilityDynamics\Settings object
       */
      public $settings = null;

      /**
       * Path to main plugin/theme file
       *
       * @public
       * @property boot_file
       * @var array
       */
      public $boot_file = false;

      /**
       * Constructor
       * Attention: MUST NOT BE CALLED DIRECTLY! USE get_instance() INSTEAD!
       *
       * @author peshkov@UD
       */
      protected function __construct( $args ) {
        parent::__construct( $args );
        //** Define our Admin Notices handler object */
        $this->errors = new Errors( array_merge( $args, array(
          'type' => $this->type
        ) ) );
        //** Determine if Composer autoloader is included and modules classes are up to date */
        $this->composer_dependencies();
        //** Determine if plugin/theme requires or recommends another plugin(s) */
        $this->plugins_dependencies();
        // Maybe run install or upgrade processes.
        $this->maybe_run_upgrade_process();
        add_action( 'wp_ajax_ud_bootstrap_dismiss_notice', array( $this, 'ud_bootstrap_dismiss_notice' ) );
      }

      /**
       * Initialize application.
       * Redeclare the method in final class!
       *
       * @author peshkov@UD
       */
      public function init() {}

      /**
       * Determine if errors exist
       * Just wrapper.
       */
      public function has_errors() {
        return $this->errors->has_errors();
      }

      /**
       * @param string $key
       * @param mixed $value
       *
       * @author peshkov@UD
       * @return \UsabilityDynamics\Settings
       */
      public function set( $key = null, $value = null ) {
        if( !is_object( $this->settings ) || !is_callable( array( $this->settings, 'set' ) ) ) {
          return false;
        }
        return $this->settings->set( $key, $value );
      }

      /**
       * @param string $key
       * @param mixed $default
       *
       * @author peshkov@UD
       * @return \UsabilityDynamics\type
       */
      public function get( $key = null, $default = null ) {
        if( !is_object( $this->settings ) || !is_callable( array( $this->settings, 'get' ) ) ) {
          return $default;
        }
        return $this->settings->get( $key, $default );
      }

      /**
       * Returns specific schema from composer.json file.
       *
       * @param string $file Path to file
       * @author peshkov@UD
       * @return mixed array or false
       */
      public function get_schema( $key = '' ) {
        if( $this->schema === null ) {
          if( !empty( $this->schema_path ) && file_exists( $this->schema_path ) ) {
            $this->schema = (array)\UsabilityDynamics\Utility::l10n_localize( json_decode( file_get_contents( $this->schema_path ), true ), (array)$this->get_localization() );
          }
        }
        //** Break if composer.json does not exist */
        if( !is_array( $this->schema ) ) {
          return false;
        }
        //** Resolve dot-notated key. */
        if( strpos( $key, '.' ) ) {
          $current = $this->schema;
          $p = strtok( $key, '.' );
          while( $p !== false ) {
            if( !isset( $current[ $p ] ) ) {
              return false;
            }
            $current = $current[ $p ];
            $p = strtok( '.' );
          }
          return $current;
        }
        //** Get default key */
        else {
          return isset( $this->schema[ $key ] ) ? $this->schema[ $key ] : false;
        }
      }

      /**
       * Return localization's list.
       *
       * Example:
       * If schema contains l10n.{key} values:
       *
       * { 'config': 'l10n.hello_world' }
       *
       * the current function should return something below:
       *
       * return array(
       *   'hello_world' => __( 'Hello World', $this->domain ),
       * );
       *
       * @author peshkov@UD
       * @return array
       */
      public function get_localization() {
        return array();
      }

      /**
       * Determine if product is just installed or upgraded
       * and run install/upgrade processes
       *
       * @author peshkov@UD
       */
      protected function maybe_run_upgrade_process() {
        //** Determine what to show depending on version installed */
        $version = get_option($this->slug . '-current-version', 0);
        $this->old_version = $version;
        //** Just installed */
        if (!$version) {
          /* Run Install handlers */
          add_action( 'plugins_loaded', array( $this, '_run_install_process' ), 0 );
        }
        //** Upgraded */
        elseif (version_compare($version, $this->args['version']) == -1) {
          /* Run Upgrade handlers */
          add_action( 'plugins_loaded', array( $this, '_run_upgrade_process' ), 0 );
        }
        // Need to save current version on plugins_loaded action,
        // unless _run_install_process and _run_upgrade_process not get called.
        add_action( 'plugins_loaded', array( $this, 'save_version_no' ), 100 );
      }

      /**
       * Saving version no to database.
       *
       */
      public function save_version_no($value=''){
        update_option( $this->slug . '-current-version', $this->args['version'] );
      }

      /**
       * Installation Handler
       * Internal method. Use run_install_process() instead
       */
      public function _run_install_process() {
        /* Delete 'Install/Upgrade' notice 'dismissed' information */
        delete_option( sanitize_key( 'dismiss_' . $this->slug . '_' . str_replace( '.', '_', $this->args['version'] ) . '_notice' ) );
        /* Delete 'Bootstrap' notice 'dismissed' information */
        delete_option( 'dismissed_notice_' . sanitize_key( $this->name ) );
        $this->run_install_process();
      }

      /**
       * Upgrade Handler
       * Internal method. Use run_upgrade_process() instead
       */
      public function _run_upgrade_process() {
        /* Delete 'Install/Upgrade' notice 'dismissed' information */
        delete_option( sanitize_key( 'dismiss_' . $this->slug . '_' . str_replace( '.', '_', $this->args['version'] ) . '_notice' ) );
        /* Delete 'Bootstrap' notice 'dismissed' information */
        delete_option( 'dismissed_notice_' . sanitize_key( $this->name ) );
        $this->run_upgrade_process();
      }

      /**
       * Run Install Process.
       *
       * Re-define the function in child.
       */
      public function run_install_process() {}

      /**
       * Run Upgrade Process.
       *
       * Re-define the function in child.
       */
      public function run_upgrade_process() {}


      /**
       * Check plugins requirements
       *
       * @author peshkov@UD
       */
      public function check_plugins_requirements() {
        //** Determine if we have TGMA Plugin Activation initialized. */
        $is_tgma = $this->is_tgma;
        if( $is_tgma ) {
          $tgma = TGM_Plugin_Activation::get_instance();
          //** Maybe get TGMPA notices. */
          $notices = $tgma->notices( get_class( $this ) );
          if( !empty( $notices[ 'messages' ] ) && is_array( $notices[ 'messages' ] ) ) {
            $error_links = false;
            $message_links = false;
            foreach( $notices[ 'messages' ] as $m ) {
              if( $m[ 'type' ] == 'error' ) $error_links = true;
              elseif( $m[ 'type' ] == 'message' ) $message_links = true;
              $this->errors->add( $m[ 'value' ], $m[ 'type' ] );
            }
            //** Maybe add footer action links to errors and|or notices block. */
            if( !empty( $notices[ 'links' ] ) && is_array( $notices[ 'links' ] ) ) {
              foreach( $notices[ 'links' ] as $type => $links ) {
                foreach( $links as $link ) {
                  $this->errors->add_action_link( $link, $type );
                }
              }
            }
          }
        }
      }

      /**
       * Maybe determines if Composer autoloader is included and modules classes are up to date
       *
       * @author peshkov@UD
       */
      private function composer_dependencies() {
        $dependencies = $this->get_schema( 'extra.schemas.dependencies.modules' );
        if( !empty( $dependencies ) && is_array( $dependencies ) ) {
          foreach( $dependencies as $module => $classes ) {
            if( !empty( $classes ) && is_array( $classes ) ) {
              foreach( $classes as $class => $v ) {
                if( !class_exists( $class ) ) {
                  $this->errors->add( sprintf( __( 'Module <b>%s</b> is not installed or the version is old, class <b>%s</b> does not exist.', $this->domain ), $module, $class ) );
                  continue;
                }
                if ( '*' != trim( $v ) && ( !property_exists( $class, 'version' ) || $class::$version < $v ) ) {
                  $this->errors->add( sprintf( __( 'Module <b>%s</b> should be updated to the latest version, class <b>%s</b> must have version <b>%s</b> or higher.', $this->domain ), $module, $class, $v ) );
                }
              }
            }
          }
        }
      }

      /**
       * Determine if plugin/theme requires or recommends another plugin(s)
       *
       * @author peshkov@UD
       */
      private function plugins_dependencies() {
        /**
         * Dependencies must be checked before plugins_loaded hook to prevent issues!
         *
         * The current condition fixes incorrect behaviour on custom 'Install Plugins' page
         * after activation plugin which has own dependencies.
         *
         * The condition belongs to WordPress 4.3 and higher.
         */
        if( did_action( 'plugins_loaded' ) && $this->type == 'plugin' ) {
          return;
        }
        $plugins = $this->get_schema( 'extra.schemas.dependencies.plugins' );
        if( !empty( $plugins ) && is_array( $plugins ) ) {
          $tgma = TGM_Plugin_Activation::get_instance();
          foreach( $plugins as $plugin ) {
            $plugin[ '_referrer' ] = get_class( $this );
            $plugin[ '_referrer_name' ] = $this->name;
            $tgma->register( $plugin );
          }
          $this->is_tgma = true;
        }
      }


      /**
       * Defines License Client if 'licenses' schema is set
       *
       * @author peshkov@UD
       */
      protected function define_license_client() {
        //** Break if we already have errors to prevent fatal ones. */
        if( $this->has_errors() ) {
          return false;
        }
        //** Be sure we have licenses scheme to continue */
        $schema = $this->get_schema( 'extra.schemas.licenses.client' );
        if( !$schema ) {
          return false;
        }
        //** Licenses Manager */
        if( !class_exists( '\UsabilityDynamics\UD_API\Bootstrap' ) ) {
          $this->errors->add( __( 'Class \UsabilityDynamics\UD_API\Bootstrap does not exist. Be sure all required plugins and (or) composer modules installed and activated.', $this->domain ) );
          return false;
        }
        $args = $this->args;
        $args = array_merge( $args, array(
          'type' => $this->type,
          'name' => $this->name,
          'slug' => $this->slug,
          'referrer_slug' => $this->slug,
          'domain' => $this->domain,
          'errors_callback' => array( $this->errors, 'add' ),
        ), $schema );
        if( empty( $args[ 'screen' ] ) ) {
          $this->errors->add( __( 'Licenses client can not be activated due to invalid \'licenses\' schema.', $this->domain ) );
        }
        $this->client = new \UsabilityDynamics\UD_API\Bootstrap( $args );
      }

      /**
       * Defines License Manager if 'license' schema is set
       *
       * @author peshkov@UD
       */
      public function define_license_manager() {
        //** Break if we already have errors to prevent fatal ones. */
        if( $this->has_errors() ) {
          return false;
        }
        //** Be sure we have license scheme to continue */
        $schema = $this->get_schema( 'extra.schemas.licenses.product' );
        if( !$schema ) {
          return false;
        }
        if( empty( $schema[ 'product_id' ] ) || ( empty( $schema[ 'referrer' ] ) && $this->type !== 'theme' ) ) {
          $this->errors->add( __( 'Product requires license, but product ID and (or) referrer is undefined. Please, be sure, that license schema has all required data.', $this->domain ), 'message' );
        }
        $schema = array_merge( (array)$schema, array(
          'type' => $this->type,
          'name' => $this->name,
          'boot_file' => $this->boot_file,
          'errors_callback' => array( $this->errors, 'add' )
        ) );
        //** Licenses Manager */
        if( !class_exists( '\UsabilityDynamics\UD_API\Manager' ) ) {
          //$this->errors->add( __( 'Class \UsabilityDynamics\UD_API\Manager does not exist. Be sure all required plugins installed and activated.', $this->domain ), 'message' );
          return false;
        }
        $this->license_manager = new \UsabilityDynamics\UD_API\Manager( $schema );
        return true;
      }
    }
  }
}
