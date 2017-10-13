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
                'enabled' => [ 'type' => 'option', 'default' => false ],
                'role' => [ 'type' => 'option', 'default', 'admin' ], // Either "admin" or "editor".
                'background' => [ 'type' => 'option', 'default' => 'color' ], // Either "color" or "image".
                'background_color' => [ 'type' => 'option', 'default' => '#fff' ],
                'background_image' => [ 'type' => 'option', 'default' => '' ],
                'title' => [ 'type' => 'option', 'default' => '' ],
                'title_color' => [ 'type' => 'option', 'default' => '#000' ],
                'body' => [
                    'type' => 'option',
                    'default' => __( 'Omlouváme se, ale probíhá údržba.', 'odwp-maintenance_mode' )
                ],
                'body_color' => [ 'type' => 'option', 'default' => '#000' ],
                'footer' => [ 'type' => 'option', 'default' => '' ],
                'footer_color' => [ 'type' => 'option', 'default' => '#000' ],
            ];
        }

        /**
         * Action on hook 'customize_register'.
         * @link https://developer.wordpress.org/reference/hooks/customize_register/
         * @param WP_Customize_Manager $wp_customize
         * @return WP_Customize_Manager Updated customizer manager.
         * @since 1.0.0
         */
        public function customize_register( WP_Customize_Manager $wp_customize ) {
            // Add settings
            foreach( $settings = $this->get_customize_settings() as $key => $val ) {
                $wp_customize->add_setting( sprintf( '%s[%s]', 'odwpmm', $key ), $val );
            }

            // Add panels
            $wp_customize->add_panel( 'odwpmm-panel', [
                'title' => __( 'Mód údržby', 'odwp-maintenance_mode' ),
                'description' => __( 'Mód údržby je plugin, kterým přesměrujete návštěvníky vašich stránek na speciální stránku po celou dobu, kdy trvají úpravy.', 'odwp-maintenance_mode' ),
                'priority' => 260,
            ] );

            // Add sections
            $wp_customize->add_section( 'odwpmm-section1', [
                'title' => __( 'Hlavní nastavení', 'odwp-maintenance_mode' ),
                'panel' => 'odwpmm-panel',
            ] );
            $wp_customize->add_section( 'odwpmm-section2', [
                'title' => __( 'Pozadí stránky', 'odwp-maintenance_mode' ),
                'panel' => 'odwpmm-panel',
            ] );
            $wp_customize->add_section( 'odwpmm-section3', [
                'title' => __( 'Textový obsah', 'odwp-maintenance_mode' ),
                'description' => __( 'Zde můžete nastavit texty zobrazené na stránce údržby. Pokud některý z textů necháte prázdný nebude na stránce vyrendrován.', 'odwp-maintenance_mode' ),
                'panel' => 'odwpmm-panel',
            ] );

            // Add controls
            $wp_customize->add_control( 'odwpmm[enabled]', [
                'label' => __( 'Povolit mód údržby', 'odwp-maintenance_mode' ),
                'description' => __( 'Zaškrtněte pokud chcete zobrazit stránku "Probíhá údržba" návštěvníkům vašeho webu.', 'odwp-maintenance_mode' ),
                'type' => 'checkbox',
                'section' => 'odwpmm-section1',
            ] );
            $wp_customize->add_control( 'odwpmm[role]', [
                'label' => __( 'Přeskočit pro', 'odwp-maintenance_mode' ),
                'description' => __( 'Vyberte jednu ze skupin uživatelů, pro které bude stránka údržby skrytá a zobrazí se jim tak normální web.', 'odwp-maintenance_mode' ),
                'type' => 'select',
                'choices' => [
                    'admin' => __( 'Administrátoři', 'odwp-maintenance_mode' ),
                    'editor' => __( 'Editoři a administrátoři', 'odwp-maintenance_mode' ),
                ],
                'section' => 'odwpmm-section1',
            ] );
            $wp_customize->add_control( 'odwpmm[background]', [
                'label' => __( 'Typ pozadí', 'odwp-maintenance_mode' ),
                'description' => __( 'Vyberte jaký typ pozadí chcete použít - buď jednolitou barvu nebo vybraný obrázek.', 'odwp-maintenance_mode' ),
                'type' => 'select',
                'choices' => [
                    'color' => __( 'Barva', 'odwp-maintenance_mode' ),
                    'image' => __( 'Obrázek', 'odwp-maintenance_mode' ),
                ],
                'section' => 'odwpmm-section2',
            ] );
            $wp_customize->add_control(
                new WP_Customize_Color_Control( $wp_customize, 'odwpmm[background_color]', [
                    'label' => __( 'Barva pozadí', 'odwp-maintenance_mode' ),
                    'section' => 'odwpmm-section2',
                ] )
            );
            $wp_customize->add_control(
                new WP_Customize_Media_Control( $wp_customize, 'odwpmm[background_image]', [
                    'label' => __( 'Vybraný obrázek', 'odwp-maintenance_mode' ),
                    'mime_type' => 'image',
                    'section' => 'odwpmm-section2',
                ] )
            );

            $wp_customize->add_control( 'odwpmm[title]', [
                'label' => __( 'Nadpis', 'odwp-maintenance_mode' ),
                'type' => 'text',
                'section' => 'odwpmm-section3',
            ] );
            $wp_customize->add_control( 'odwpmm[body]', [
                'label' => __( 'Hlavní text', 'odwp-maintenance_mode' ),
                'type' => 'textarea',
                'section' => 'odwpmm-section3',
            ] );
            $wp_customize->add_control( 'odwpmm[footer]', [
                'label' => __( 'Patička', 'odwp-maintenance_mode' ),
                'type' => 'textarea',
                'section' => 'odwpmm-section3',
            ] );
            $wp_customize->add_control(
                new WP_Customize_Color_Control( $wp_customize, 'odwpmm[title_color]', [
                    'label' => __( 'Barva nadpisu', 'odwp-maintenance_mode' ),
                    'section' => 'odwpmm-section3',
                ] )
            );
            $wp_customize->add_control(
                new WP_Customize_Color_Control( $wp_customize, 'odwpmm[body_color]', [
                    'label' => __( 'Barva hlavního textu', 'odwp-maintenance_mode' ),
                    'section' => 'odwpmm-section3',
                ] )
            );
            $wp_customize->add_control(
                new WP_Customize_Color_Control( $wp_customize, 'odwpmm[footer_color]', [
                    'label' => __( 'Barva patičky', 'odwp-maintenance_mode' ),
                    'section' => 'odwpmm-section3',
                ] )
            );

            return $wp_customize;
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
            $options = ( array ) get_option( 'odwpmm' );
            $enabled = array_key_exists( 'enabled', $options ) ? ( bool ) $options['enabled'] : false;

            if( $enabled === true ) {
                header( 'Content-type: text/html;charset=utf8' );
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>XXX</title>
    </head>
    <body>
        <h1>Maintenance mode</h1>
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
