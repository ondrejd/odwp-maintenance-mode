<?php
/**
 * Plugin Name: Maintenance Mode
 * Plugin URI: https://github.com/wordpress-plugins/odwp-maintenance_mode
 * Description: Small plugin that offers maintenance mode customizable in <strong>theme customizer</strong>.
 * Version: 1.0.0
 * Author: ondrejd
 * Author URI: https://ondrejd.com/
 * License: GPLv3
 * Donate link: https://www.paypal.me/ondrejd
 * Requires at least: 4.7
 * Tested up to: 4.8.2
 * Text Domain: odwp-maintenance_mode
 * Domain Path: /languages
 *
 * @author  Ondřej Doněk, <ondrejd@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @link https://github.com/ondrejd/odwp-maintenance_mode for the canonical source repository
 * @package odwp-maintenance_mode
 * @since 1.0.0
 *
 * @link https://developer.wordpress.org/themes/customize-api/
 * @link https://code.tutsplus.com/tutorials/customizer-javascript-apis-getting-started--cms-26838
 *
 * @todo Add English localization (as a default).
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}


if( ! class_exists( 'ODWP_Maintenance_Mode_Plugin' ) ) :
    /**
     * Main class of the plugin.
     * @since 1.0.0
     */
    class ODWP_Maintenance_Mode_Plugin {

        /**
         * @const string Meta key that identifies page as "Maintance mode" page.
         * @since 1.0.0
         */
        const DEFAULT_META_KEY = 'odwpmm-is_maintenance_mode_page';

        /**
         * @const string
         * @since 1.0.0
         */
        const DEFAULT_TEMPLATE = 'maintenance-mode-template.php';

        /**
         * @var string $basename
         * @since 1.0.0
         */
        protected $basename;

        /**
         * @var array $templates Array with page templates we are adding.
         * @since 1.0.0
         */
        protected $templates;

        /**
         * @var boolean $enabled
         * @since 1.0.0
         */
        protected $enabled;

        /**
         * @var string $role One of these ["admin","editor"].
         * @since 1.0.0
         */
        protected $role;

        /**
         * @var string $background One of these ["color","image"].
         * @since 1.0.0
         */
        protected $background;

        /**
         * @var string $background_color
         * @since 1.0.0
         */
        protected $background_color;

        /**
         * @var string $background_image
         * @since 1.0.0
         */
        protected $background_image;

        /**
         * @var string $title
         * @since 1.0.0
         */
        protected $title;

        /**
         * @var string $title_color
         * @since 1.0.0
         */
        protected $title_color;

        /**
         * @var string $body
         * @since 1.0.0
         */
        protected $body;

        /**
         * @var string $body_color
         * @since 1.0.0
         */
        protected $body_color;

        /**
         * @var string $footer
         * @since 1.0.0
         */
        protected $footer;

        /**
         * @var string $footer_color
         * @since 1.0.0
         */
        protected $footer_color;

        /**
         * Constructor.
         * @return void
         * @since 1.0.0
         * @uses add_action
         * @uses add_filter
         * @uses get_bloginfo
         * @uses plugin_basename
         */
        public function __construct() {
            $this->basename = plugin_basename( __FILE__ );
            $this->templates = [
                self::DEFAULT_TEMPLATE => __( 'Maintenance Mode', 'odwp-maintenance_mode' ),
            ];

            register_activation_hook( __FILE__, [__CLASS__, 'activate'] );
            register_deactivation_hook( __FILE__, [__CLASS__, 'deactivate'] );
            register_uninstall_hook( __FILE__, [__CLASS__, 'uninstall'] );

            // Add a filter to the attributes metabox to inject template into the cache.
            if( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {
                add_filter( 'page_attributes_dropdown_pages_args', [$this, 'register_page_template'] );
            } else {
                add_filter( 'theme_page_templates', [$this, 'page_template_add'] );
            }

            // Add a filter to the save post to inject out template into the page cache
            add_filter( 'wp_insert_post_data', [$this, 'page_template_register'] );

            // Add a filter to the template include to determine if the page has our
            // template assigned and return it's path
            add_filter( 'template_include', [$this, 'page_template_view'] );

            // Plugin's textdomain
            add_action( 'init', [$this, 'load_plugin_textdomain'] );

            // Theme Customizer
            add_action( 'customize_register', [$this, 'customize_register'] );
            add_action( 'customize_preview_init', [$this, 'live_preview'] );
            add_action( 'pre_get_posts', [$this, 'pre_get_posts'] );
            add_action( 'wp_head', [$this, 'wp_head'], 99 );

            // Plugin actions link in "Administration > Plugins".
            add_filter( "plugin_action_links_{$this->basename}", [$this, 'plugin_action_links'] );
        }

        /**
         * @return WP_Post|null
         * @since 1.0.0
         */
        public static function get_maintenance_mode_page() {
            $query = new WP_Query( [
                'post_type' => 'page',
                'meta_key' => self::DEFAULT_META_KEY,
                'meta_value' => 1,
            ] );

            if( $query->post_count <= 0 ) {
                return null;
            }

            if( $query->post_count > 0 ) {
                // XXX Show an admin notice when there is more than one "Maintanence mode" page.
            }

            return $query->posts[0];
        }

        /**
         * @global int $user_ID
         * @internal Activates the plugin.
         * @link http://codex.wordpress.org/Function_Reference/wp_insert_post
         * @link https://wordpress.stackexchange.com/questions/13378/add-custom-template-page-programmatically
         * @link https://clicknathan.com/web-design/automatically-create-pages-wordpress/
         * @return void
         * @since 1.0.0
         * @uses wp_die
         * @uses wp_insert_post
         * @uses update_post_meta
         */
        public static function activate() {
            global $user_ID;

            // Check if page with slug `maintenance-mode` exists
            $mm_page = self::get_maintenance_mode_page();

            // If page exists just return
            if( ( $mm_page instanceof WP_Post ) ) {
                // XXX What if exists but its status isn't "publish"?!
                return;
            }

            // Page doesn't exist create it (with correct template).
            $new_page = [
                'post_title' => __( 'Maintenance Mode', 'odwp-maintenance_mode' ),
                'post_content' => __( '<h2>Maintenance Mode</h2><p>We are sorry but when is under development.</p>', 'odwp-maintenance_mode' ),
                'post_status' => 'publish',
                'post_date' => date( 'Y-m-d H:i:s' ),
                'post_author' => $user_ID,
                'post_type' => 'page',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'meta_input' => [
                    self::DEFAULT_META_KEY => 1,
                ],
            ];
            $page_id = wp_insert_post( $new_page );

            if( ! $page_id ) {
                wp_die( __( 'Error creating template page', 'odwp-maintenance_mode' ) );
            } else {
                // Set up page template
                update_post_meta( $page_id, '_wp_page_template', self::DEFAULT_TEMPLATE );
            }
        }

        /**
         * @internal Deactivates the plugin.
         * @return void
         * @since 1.0.0
         */
        public static function deactivate() {
            // XXX On user confirmation move page "Maintenance mode" to the Trash.
        }

        /**
         * @internal Uninstalls the plugin.
         * @return void
         * @since 1.0.0
         */
        public static function uninstall() {
            // XXX Remove all settings.
        }

        /**
         * Loads plugin textdomain.
         * @return void
         * @since 1.0.0
         * @uses apply_filters
         * @uses get_locale
         * @uses load_textdomain
         * @uses load_plugin_textdomain
         */
        public function load_plugin_textdomain() {
			$domain = 'odwp-maintenance_mode';
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

			load_textdomain( $domain, WP_LANG_DIR . "/{$domain}/{$domain}-{$locale}.mo" );
			load_plugin_textdomain( $domain, false, dirname( __FILE__ ) . "/languages/{$domain}-{$locale}.mo" );
        }

        /**
         * Adds our template to the pages cache in order to trick WordPress
         * into thinking the template file exists where it doens't really exist.
         * @param array $atts
         * @return array
         * @since 1.0.0
         * @uses get_stylesheet
         * @uses get_theme_root
         * @uses wp_cache_add
         * @uses wp_cache_delete
         * @uses wp_get_theme
         */
        public function page_template_register( $atts ) {
            // Create the key used for the themes cache
            $cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

            // Retrieve the cache list.
            // If it doesn't exist, or it's empty prepare an array
            $templates = wp_get_theme()->get_page_templates();
            if( empty( $templates ) ) {
            	$templates = [];
            }

            // New cache, therefore remove the old one
            wp_cache_delete( $cache_key , 'themes' );

            // Now add our template to the list of templates by merging our templates
            // with the existing templates array from the cache.
            $templates = array_merge( $templates, $this->templates );

            // Add the modified cache to allow WordPress to pick it up for listing
            // available templates
            wp_cache_add( $cache_key, $templates, 'themes', 1800 );

            return $atts;
        }

        /**
         * Adds our template to the page dropdown for v4.7+
         * @param array $posts_templates
         * @return array
         * @since 1.0.0
         */
        public function page_template_add( $posts_templates ) {
            $posts_templates = array_merge( $posts_templates, $this->templates );
            return $posts_templates;
        }
        /**
         * Checks if the template is assigned to the page
         * @global WP_Post $post
         * @param string $template
         * @return string
         * @since 1.0.0
         * @uses get_post_meta
         * @uses plugin_dir_path
         */
        public function page_template_view( $template ) {
            // Get global post
            global $post;

            // Return template if post is empty
            if( ! $post ) {
            	return $template;
            }

            // Return default template if we don't have a custom one defined
            if( ! isset( $this->templates[get_post_meta( $post->ID, '_wp_page_template', true )] ) ) {
            	return $template;
            }

            $file = plugin_dir_path( __FILE__ ) . get_post_meta( $post->ID, '_wp_page_template', true );

            // Just to be safe, we check if the file exist first
            if( file_exists( $file ) ) {
                return $file;
            } else {
                odwpdl_write_log( $file );
            }

            // Return template
            return $template;
        }

        /**
         * @return array Settings for customizer.
         * @since 1.0.0
         */
        public function get_customize_settings() {
            return [
                'enabled'          => [ 'default' => false ],
                'role'             => [ 'default' => 'admin' ], //["admin","editor"]
                'background_color' => [ 'default' => '#750743' ],
                'background_image' => [ 'default' => '' ],
                'background_size'  => [ 'default' => 'cover'],//['cover','contain']
                'title'            => [ 'default' => '' ],
                'title_color'      => [ 'default' => '#fff' ],
                'body'             => [ 'default' => __( 'Omlouváme se, ale probíhá údržba.', 'odwp-maintenance_mode' ), ],
                'body_color'       => [ 'default' => '#fff' ],
                'text_align'       => [ 'default' => 'center' ],
                'footer'           => [ 'default' => '' ],
                'footer_color'     => [ 'default' => '#fff' ],
            ];
        }

        /**
         * Action on hook 'customize_register'.
         * @link https://developer.wordpress.org/reference/hooks/customize_register/
         * @param WP_Customize_Manager $wp_customize
         * @return void
         * @since 1.0.0
         */
        public function customize_register( WP_Customize_Manager $wp_customize ) {
            // Add settings
            $this->customize_register_settings( $wp_customize );
            // Add structure
            $this->customize_register_structure( $wp_customize );
            // Add sections
            $this->customize_register_controls_section_1( $wp_customize );
            $this->customize_register_controls_section_2( $wp_customize );
            $this->customize_register_controls_section_3( $wp_customize );
        }

        /**
         * @internal Registers settings for the theme customizer.
         * @param WP_Customize_Manager $wp_customize
         * @return void
         * @since 1.0.0
         */
        private function customize_register_settings( WP_Customize_Manager $wp_customize ) {
            foreach( $settings = $this->get_customize_settings() as $key => $val ) {
                $id   = sprintf( '%s[%s]', 'odwpmm', $key );
                $args = array_merge( [
                    'capability' => 'edit_theme_options',
                    'transport'  => 'refresh',// ["refresh","postMessage"]
                    'type'       => 'option',
                ], $val );

                $wp_customize->add_setting( $id, $val );
            }
        }

        /**
         * @internal Registers panels and settings for the theme customizer.
         * @param WP_Customize_Manager $wp_customize
         * @return void
         * @since 1.0.0
         */
        private function customize_register_structure( WP_Customize_Manager $wp_customize ) {
            // Panel
            $wp_customize->add_panel( 'odwpmm-panel', [
                'title'              => __( 'Mód údržby', 'odwp-maintenance_mode' ),
                'description'        => __( 'Mód údržby je plugin, kterým přesměrujete návštěvníky vašich stránek na speciální stránku po celou dobu, kdy trvají úpravy.', 'odwp-maintenance_mode' ),
                'priority'           => 160,
            ] );
            // Sections
            $wp_customize->add_section( 'odwpmm-section1', [
                'title'              => __( 'Hlavní nastavení', 'odwp-maintenance_mode' ),
                'panel'              => 'odwpmm-panel',
                'description_hidden' => true,
            ] );
            $wp_customize->add_section( 'odwpmm-section2', [
                'title'              => __( 'Pozadí stránky', 'odwp-maintenance_mode' ),
                'description'        => __( 'Nastavte vlastnosti pozadí pro stránku údržby.', 'odwp-maintenance_mode' ),
                'panel'              => 'odwpmm-panel',
                'description_hidden' => true,
            ] );
            $wp_customize->add_section( 'odwpmm-section3', [
                'title'              => __( 'Textový obsah', 'odwp-maintenance_mode' ),
                'description'        => __( 'Zde můžete nastavit texty zobrazené na stránce údržby. Pokud některý z textů necháte prázdný nebude na stránce vyrendrován.', 'odwp-maintenance_mode' ),
                'panel'              => 'odwpmm-panel',
                'description_hidden' => true,
            ] );
        }

        /**
         * @internal Registers controls for the first section.
         * @param WP_Customize_Manager $wp_customize
         * @return void
         * @since 1.0.0
         */
        private function customize_register_controls_section_1( WP_Customize_Manager $wp_customize ) {
            $wp_customize->add_control( 'odwpmm[enabled]', [
                'label'       => __( 'Povolit mód údržby', 'odwp-maintenance_mode' ),
                'description' => __( 'Zaškrtněte pokud chcete zobrazit stránku "Probíhá údržba" návštěvníkům vašeho webu.', 'odwp-maintenance_mode' ),
                'section'     => 'odwpmm-section1',
                'type'        => 'checkbox',
            ] );
            $wp_customize->add_control( 'odwpmm[role]', [
                'description' => __( 'Vyberte jednu ze skupin uživatelů, pro které bude stránka údržby skrytá a zobrazí se jim tak normální web.', 'odwp-maintenance_mode' ),
                'choices'     => [
                    'admin'   => __( 'Administrátoři', 'odwp-maintenance_mode' ),
                    'editor'  => __( 'Editoři a administrátoři', 'odwp-maintenance_mode' ),
                ],
                'label'       => __( 'Přeskočit pro', 'odwp-maintenance_mode' ),
                'section'     => 'odwpmm-section1',
                'type'        => 'select',
            ] );
        }

        /**
         * @internal Registers controls for the second section.
         * @param WP_Customize_Manager $wp_customize
         * @return void
         * @since 1.0.0
         */
        private function customize_register_controls_section_2( WP_Customize_Manager $wp_customize ) {
            $wp_customize->add_control(
                new WP_Customize_Color_Control( $wp_customize, 'odwpmm[background_color]', [
                    'label'     => __( 'Barva pozadí', 'odwp-maintenance_mode' ),
                    'section'   => 'odwpmm-section2',
                ] )
            );
            $wp_customize->add_control(
                new WP_Customize_Media_Control( $wp_customize, 'odwpmm[background_image]', [
                    'label'     => __( 'Vybraný obrázek', 'odwp-maintenance_mode' ),
                    'mime_type' => 'image',
                    'section'   => 'odwpmm-section2',
                ] )
            );
            $wp_customize->add_control( 'odwpmm[background_size]', [
                'description' => __( 'Vyberte, jakou velikost má mít obrázek na pozadí.', 'odwp-maintenance_mode' ),
                'choices'     => [
                    'cover'   => __( 'Zakrytí', 'odwp-maintenance_mode' ),
                    'contain' => __( 'Obsažení', 'odwp-maintenance_mode' ),
                ],
                'label'       => __( 'Velikost pozadí', 'odwp-maintenance_mode' ),
                'section'     => 'odwpmm-section2',
                'type'        => 'select',
            ] );
            /**
             * cover    Scale the background image to be as large as possible
             *          so that the background area is completely covered by
             *          the background image. Some parts of the background
             *          image may not be in view within the background
             *          positioning area
             * contain  Scale the image to the largest size such that both
             *          its width and its height can fit inside the content area
             */
        }

        /**
         * @internal Registers controls for the third section.
         * @param WP_Customize_Manager $wp_customize
         * @return void
         * @since 1.0.0
         */
        private function customize_register_controls_section_3( WP_Customize_Manager $wp_customize ) {
            $wp_customize->add_control( 'odwpmm[title]', [
                'label'   => __( 'Nadpis', 'odwp-maintenance_mode' ),
                'type'    => 'text',
                'section' => 'odwpmm-section3',
            ] );
            $wp_customize->add_control( 'odwpmm[body]', [
                'label'   => __( 'Hlavní text', 'odwp-maintenance_mode' ),
                'type'    => 'textarea',
                'section' => 'odwpmm-section3',
            ] );
            $wp_customize->add_control( 'odwpmm[footer]', [
                'label'   => __( 'Patička', 'odwp-maintenance_mode' ),
                'type'    => 'textarea',
                'section' => 'odwpmm-section3',
            ] );
            $wp_customize->add_control(
                new WP_Customize_Color_Control( $wp_customize, 'odwpmm[title_color]', [
                    'label'     => __( 'Barva nadpisu', 'odwp-maintenance_mode' ),
                    'section'   => 'odwpmm-section3',
                ] )
            );
            $wp_customize->add_control(
                new WP_Customize_Color_Control( $wp_customize, 'odwpmm[body_color]', [
                    'label'     => __( 'Barva hlavního textu', 'odwp-maintenance_mode' ),
                    'section'   => 'odwpmm-section3',
                ] )
            );
            $wp_customize->add_control(
                new WP_Customize_Color_Control( $wp_customize, 'odwpmm[footer_color]', [
                    'label'     => __( 'Barva patičky', 'odwp-maintenance_mode' ),
                    'section'   => 'odwpmm-section3',
                ] )
            );
        }

        /**
         * @return void
         * @since 1.0.0
         * @uses admin_url
         */
        public function plugin_action_links( $links ) {
            return array_merge( $links, [
                '<a href="' . admin_url( 'options-general.php?page=odwpmm' ) . '">' . __( 'Nastavení', 'odwp-maintenance_mode' ) . '</a>',
            ] );
        }

        /**
         * Load our JavaScript when Customizer is starting.
         * @return void
         * @since 1.0.0
         * @uses plugin_dir_url
         * @uses wp_enqueue_script
         */
        public function live_preview() {
            wp_enqueue_script(
                'odwp-maintenance_mode',
                plugin_dir_url( __FILE__ ) . '/assets/js/odwp-maintenance_mode.js',
                ['jquery', 'customize-preview'],
                '1.0.0',
                true
            );
        }

        /**
         * @internal Initializes options.
         * @return void
         * @since 1.0.0
         * @uses get_option
         */
        protected function init_options() {
            $current   = ( array ) get_option( 'odwpmm' );
            $default   = $this->get_customize_settings();
            $keys_arr  = array_keys( $default );

            // Go through all arrays and set up properties of this class
            array_walk( $keys_arr, function( $key ) use ( $current, $default ) {
                if( array_key_exists( $key, $current ) ) {
                    $this->$key = $current[$key];
                } else {
                    $this->$key = $default[$key]['default'];
                }
            } );
        }

        /**
         * Render maintenance page if maintenance mode is enabled.
         * @global WP_Customize_Manager
         * @param WP_Query $query
         * @return WP_Query
         * @since 1.0.0
         * @uses wp_get_current_user
         */
        public function pre_get_posts( WP_Query $query ) {
            global $wp_customize;

            //( $wp_customize instanceof WP_Customize_Manager )
            // Ensure that plugin's options are loaded
            $this->init_options();
            odwpdl_write_log( $this );

            // Is maintanence mode enabled?
            if( $this->enabled !== true ) {
                return $query;
            }

            // Get current user
            $user = wp_get_current_user();

            // And gather allowed roles
            $allowed_roles = ['administrator'];
            if( $this->role == 'editor' ) {
                $allowed_roles[] = 'editor';
            }

            // If user is in allowed roles than keep the query
            if( array_intersect( $allowed_roles, $user->roles ) ) {
                return $query;
            }
            else {
                if( is_customize_preview() ) {
                    return $query;
                } else {
                    // XXX Tady je právě ten moment, kdy musíme zobrazit "Maintanence Mode" page.
                    wp_die( 'Tady je právě ten moment, kdy musíme zobrazit "Maintanence Mode" page.' );
                }
            }

            // If maintenance mode page wasn't rendered than continue as WP normally does.
            return $query;
        }

        /**
         * @internal Hook for "wp_head" action.
         * @return void
         * @since 1.0.0
         */
        public function wp_head() {
            $img_atts = wp_get_attachment_image_src( $this->background_image, 'full' );
            $img_url = '';

            if( is_array( $img_atts ) ) {
                $img_url = $img_atts[0];
            }
?>
<style type="text/css">
body { overflow: hidden; }
.site-header, .site-nav, .cookies-usage-warning { visibility: collapsed ! important; display: none ! important; }
body {
    background-color: <?php echo $this->background_color ?>;
    <?php if( ! empty( $img_url ) ) : ?>
    background-image: url( "<?php echo $img_atts[0] ?>" ); }
    <?php endif ?>
    background-size: <?php echo background_size ?>;
    background-repeat: no-repeat;
    background-attachment: fixed;
    background-position: center;
}
<?php if( ! empty( $this->title_color ) ) : ?>
h1 { color: <?php echo $this->title_color ?>; }
<?php endif ?>
<?php if( ! empty( $this->body_color ) ) : ?>
.entry-content,
.entry-content h2,
.entry-content p,
.entry-content a,
.entry-content a:hover,
.entry-content a:active {
    color: <?php echo $this->body_color ?>;
}
<?php endif ?>
<?php if( ! empty( $this->footer_color ) ) : ?>
.site-footer,
.site-footer h2,
.site-footer p,
.site-footer a,
.site-footer a:hover,
.site-footer a:active {
    color: <?php echo $this->footer_color ?>;
}
<?php endif ?>
</style>
<?php
        }
    }
endif;

/**
 * @var ODWP_Maintenance_Mode_Plugin $ODWP_Maintenance_Mode_Plugin
 */
$ODWP_Maintenance_Mode_Plugin = new ODWP_Maintenance_Mode_Plugin();
