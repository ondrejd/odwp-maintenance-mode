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
 * @link https://ondrejd.com/wordpress-plugins/odwp-maintenance_mode for the home page
 * @package odwp-maintenance_mode
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
         * Constructor.
         * @return void
         * @since 1.0.0
         * @uses add_filter
         * @uses plugin_basename
         */
        public function __construct() {
            add_action( 'customize_register', [$this, 'customize_register'] );
            add_action( 'customize_preview_init', [$this, 'live_preview'] );
            add_action( 'pre_get_posts', [$this, 'pre_get_posts'] );
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links'] );
        }

        /**
         * @return array Settings for customizer.
         * @since 1.0.0
         */
        public function get_customize_settings() {
            return [
                'enabled'          => [ 'type' => 'option', 'default' => false ],
                'role'             => [ 'type' => 'option', 'default' => 'admin' ], //["admin","editor"]
                'background'       => [ 'type' => 'option', 'default' => 'color' ], //["color", "image"]
                'background_color' => [ 'type' => 'option', 'default' => '#fff' ],
                'background_image' => [ 'type' => 'option', 'default' => '' ],
                'title'            => [ 'type' => 'option', 'default' => '' ],
                'title_color'      => [ 'type' => 'option', 'default' => '#000' ],
                'body'             => [ 'type' => 'option', 'default' => __( 'Omlouváme se, ale probíhá údržba.', 'odwp-maintenance_mode' ), ],
                'body_color'       => [ 'type' => 'option', 'default' => '#000' ],
                'footer'           => [ 'type' => 'option', 'default' => '' ],
                'footer_color'     => [ 'type' => 'option', 'default' => '#000' ],
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
                'title'       => __( 'Mód údržby', 'odwp-maintenance_mode' ),
                'description' => __( 'Mód údržby je plugin, kterým přesměrujete návštěvníky vašich stránek na speciální stránku po celou dobu, kdy trvají úpravy.', 'odwp-maintenance_mode' ),
                'priority'    => 160,
            ] );
            // Sections
            $wp_customize->add_section( 'odwpmm-section1', [
                'title'       => __( 'Hlavní nastavení', 'odwp-maintenance_mode' ),
                'panel'       => 'odwpmm-panel',
            ] );
            $wp_customize->add_section( 'odwpmm-section2', [
                'title'       => __( 'Pozadí stránky', 'odwp-maintenance_mode' ),
                'panel'       => 'odwpmm-panel',
            ] );
            $wp_customize->add_section( 'odwpmm-section3', [
                'title'       => __( 'Textový obsah', 'odwp-maintenance_mode' ),
                'description' => __( 'Zde můžete nastavit texty zobrazené na stránce údržby. Pokud některý z textů necháte prázdný nebude na stránce vyrendrován.', 'odwp-maintenance_mode' ),
                'panel'       => 'odwpmm-panel',
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
            $wp_customize->add_control( 'odwpmm[background]', [
                'description' => __( 'Vyberte jaký typ pozadí chcete použít - buď jednolitou barvu nebo vybraný obrázek.', 'odwp-maintenance_mode' ),
                'choices'     => [
                    'color'   => __( 'Barva', 'odwp-maintenance_mode' ),
                    'image'   => __( 'Obrázek', 'odwp-maintenance_mode' ),
                ],
                'label'       => __( 'Typ pozadí', 'odwp-maintenance_mode' ),
                'section'     => 'odwpmm-section2',
                'type'        => 'select',
            ] );
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
         * Render maintenance page if maintenance mode is enabled.
         * @param WP_Query $query
         * @return WP_Query
         * @since 1.0.0
         * @uses get_option
         */
        public function pre_get_posts( WP_Query $query ) {
            // Load Maintenance mode options
            $options      = ( array ) get_option( 'odwpmm' );
            $default      = $this->get_customize_settings();
            $enabled      = array_key_exists( 'enabled', $options ) ? $options['enabled'] : $default['enabled']['default'];
            $role         = array_key_exists( 'role', $options ) ? $options['role'] : $default['role']['default'];
            $bckg         = array_key_exists( 'background', $options ) ? $options['background'] : $default['background']['default'];
            $bckg_color   = array_key_exists( 'background_color', $options ) ? $options['background_color'] : $default['background_color']['default'];
            $bckg_image   = array_key_exists( 'background_image', $options ) ? $options['background_image'] : $default['background_image']['default'];
            $title        = array_key_exists( 'title', $options ) ? $options['title'] : $default['title']['default'];
            $title_color  = array_key_exists( 'title_color', $options ) ? $options['title_color'] : $default['title_color']['default'];
            $body         = array_key_exists( 'body', $options ) ? $options['body'] : $default['body']['default'];
            $body_color   = array_key_exists( 'body_color', $options ) ? $options['body_color'] : $default['body_color']['default'];
            $footer       = array_key_exists( 'footer', $options ) ? $options['footer'] : $default['footer']['default'];
            $footer_color = array_key_exists( 'footer_color', $options ) ? $options['footer_color'] : $default['footer_color']['default'];

            if( $enabled === true ) {
                header( 'Content-type: text/html;charset=utf8' );
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title><?php echo $title ?></title>
        <style type="text/css">
<?php if( $bckg == 'color' ) : ?>
body { background-color: <?php echo $bckg_color ?>; }
<?php else : ?>
body { background-image: url( <?php echo $bckg_image ?> ); }
<?php endif ?>
        </style>
    </head>
    <body>
        <div>
            <header>
                <h1><?php echo $title ?></h1>
            </header>
            <div>
                <p><?php echo $body ?></p>
            </div>
    </body>
</html>
<?php
                exit();
            }

            return $query;
        }
    }
endif;

/**
 * @var ODWP_Maintenance_Mode_Plugin $ODWP_Maintenance_Mode_Plugin
 */
$ODWP_Maintenance_Mode_Plugin = new ODWP_Maintenance_Mode_Plugin();
