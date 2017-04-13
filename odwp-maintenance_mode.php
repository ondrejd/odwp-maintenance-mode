<?php
/**
 * Plugin Name: Maintenance Mode for Customizer
 * Plugin URI: https://ondrejd.com/wordpress-plugins/odwp-maintenance_mode
 * Description: Maintenance mode with settings included in theme customizer.
 * Version: 1.0.0
 * Author: ondrejd
 * Author URI: https://ondrejd.com/
 * License: GPLv3
 *
 * Requires at least: 4.7
 * Tested up to: 4.7.3
 *
 * Text Domain: odwp-maintenance_mode
 * Domain Path: /languages
 *
 * @author  Ondřej Doněk, <ondrejd@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @link https://github.com/ondrejd/odwp-maintenance_mode for the canonical source repository
 * @link https://ondrejd.com/wordpress-plugins/odwp-maintenance_mode for the home page
 * @package odwp-maintenance_mode
 * 
 * @todo Before activating the plugin check if WooCommerce is installed!
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

// Constants
defined( 'ODWPMM_SLUG' ) || define( 'ODWPMM_SLUG', 'odwp-maintenance_mode' );
defined( 'ODWPMM_FILE' ) || define( 'ODWPMM_FILE', __FILE__ );
defined( 'ODWPMM_PATH' ) || define( 'ODWPMM_PATH', dirname( ODWPMM_FILE ) );


if ( ! function_exists( 'odwpmm_get_customize_settings' ) ) :
    /**
     * @return array Settings for customizer.
     */
    function odwpmm_get_customize_settings() {
        return [
            'enabled' => [ 'type' => 'option', 'default' => true ],
            'role' => [ 'type' => 'option', 'default', 'admin' ], // Either "admin" or "editor".
            'background' => [ 'type' => 'option', 'default' => 'color' ], // Either "color" or "image".
            'background_color' => [ 'type' => 'option', 'default' => '#fff' ],
            'background_image' => [ 'type' => 'option', 'default' => '' ],
            'title' => [ 'type' => 'option', 'default' => '' ],
            'title_color' => [ 'type' => 'option', 'default' => '#000' ],
            'body' => [
                'type' => 'option',
                'default' => 'Omlouváme se, ale probíhá údržba.'
            ],
            'body_color' => [ 'type' => 'option', 'default' => '#000' ],
            'footer' => [ 'type' => 'option', 'default' => '' ],
            'footer_color' => [ 'type' => 'option', 'default' => '#000' ],
        ];
    }
endif;

if ( ! function_exists( 'odwpmm_customize_register' ) ) :
    /**
     * Action on hook 'customize_register'.
     * @link https://developer.wordpress.org/reference/hooks/customize_register/
     * @param WP_Customize_Manager $wp_customize
     * @return WP_Customize_Manager Updated customizer manager.
     */
    function odwpmm_customize_register( $wp_customize ) {
        // Add settings
        foreach( $settings = odwpmm_get_customize_settings() as $key => $val ) {
            $wp_customize->add_setting( sprintf( '%s[%s]', ODWPMM_SLUG, $key ), $val );
        }

        // Add panels
        $wp_customize->add_panel( ODWPMM_SLUG . '-panel', [
            'title' => __( 'Mód údržby', ODWPMM_SLUG ),
            'description' => __( 'Mód údržby je plugin, kterým přesměrujete návštěvníky vašich stránek na speciální stránku po celou dobu, kdy trvají úpravy.', ODWPMM_SLUG ),
            'priority' => 260,
        ] );

        // Add sections
        $wp_customize->add_section( ODWPMM_SLUG . '-section1', [
            'title' => __( 'Hlavní nastavení', ODWPMM_SLUG ),
            'panel' => ODWPMM_SLUG . '-panel',
        ] );, ODWPMM_SLUG )
        $wp_customize->add_section( ODWPMM_SLUG . '-section2', [
            'title' => __( 'Pozadí stránky', ODWPMM_SLUG ),
            'panel' => ODWPMM_SLUG . '-panel',
        ] );
        $wp_customize->add_section( ODWPMM_SLUG . '-section3', [
            'title' => __( 'Textový obsah', ODWPMM_SLUG ),
            'description' => __( 'Zde můžete nastavit texty zobrazené na stránce údržby. Pokud některý z textů necháte prázdný nebude na stránce vyrendrován.', ODWPMM_SLUG ),
            'panel' => ODWPMM_SLUG . '-panel',
        ] );

        // Add controls
        $wp_customize->add_control( ODWPMM_SLUG . '[enabled]', [
            'label' => __( 'Povolit mód údržby', ODWPMM_SLUG ),
            'description' => __( 'Zaškrtněte pokud chcete zobrazit stránku "Probíhá údržba" návštěvníkům vašeho webu.', ODWPMM_SLUG ),
            'type' => 'checkbox',
            'section' => ODWPMM_SLUG . '-section1',
        ] );
        $wp_customize->add_control( ODWPMM_SLUG . '[role]', [
            'label' => __( 'Přeskočit pro', ODWPMM_SLUG ),
            'description' => __( 'Vyberte jednu ze skupin uživatelů, pro které bude stránka údržby skrytá a zobrazí se jim tak normální web.', ODWPMM_SLUG ),
            'type' => 'select',
            'choices' => [
                'admin' => __( 'Administrátoři', ODWPMM_SLUG ),
                'editor' => __( 'Editoři a administrátoři', ODWPMM_SLUG ),
            ],
            'section' => ODWPMM_SLUG . '-section1',
        ] );
        $wp_customize->add_control( ODWPMM_SLUG . '[background]', [
            'label' => __( 'Typ pozadí', ODWPMM_SLUG ),
            'description' => __( 'Vyberte jaký typ pozadí chcete použít - buď jednolitou barvu nebo vybraný obrázek.', ODWPMM_SLUG ),
            'type' => 'select',
            'choices' => [
                'color' => __( 'Barva', ODWPMM_SLUG ),
                'image' => __( 'Obrázek', ODWPMM_SLUG ),
            ],
            'section' => ODWPMM_SLUG . '-section2',
        ] );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, ODWPMM_SLUG . '[background_color]', [
            'label' => __( 'Barva pozadí', ODWPMM_SLUG ), 
            'section' => ODWPMM_SLUG . '-section2',
        ] ) );
        $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, ODWPMM_SLUG . '[background_image]', [
            'label' => __( 'Vybraný obrázek', ODWPMM_SLUG ),
            'mime_type' => 'image',
            'section' => ODWPMM_SLUG . '-section2',
        ] ) );
        
        $wp_customize->add_control( ODWPMM_SLUG . '[title]', [
            'label' => __( 'Nadpis', ODWPMM_SLUG ),
            'type' => 'text',
            'section' => ODWPMM_SLUG . '-section3',
        ] );
        $wp_customize->add_control( ODWPMM_SLUG . '[body]', [
            'label' => __( 'Hlavní text', ODWPMM_SLUG ),
            'type' => 'textarea',
            'section' => ODWPMM_SLUG . '-section3',
        ] );
        $wp_customize->add_control( ODWPMM_SLUG . '[footer]', [
            'label' => __( 'Patička', ODWPMM_SLUG ),
            'type' => 'textarea',
            'section' => ODWPMM_SLUG . '-section3',
        ] );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, ODWPMM_SLUG . '[title_color]', [
            'label' => __( 'Barva nadpisu', ODWPMM_SLUG ), 
            'section' => ODWPMM_SLUG . '-section3',
        ] ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, ODWPMM_SLUG . '[body_color]', [
            'label' => __( 'Barva hlavního textu', ODWPMM_SLUG ), 
            'section' => ODWPMM_SLUG . '-section3',
        ] ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, ODWPMM_SLUG . '[footer_color]', [
            'label' => __( 'Barva patičky', ODWPMM_SLUG ), 
            'section' => ODWPMM_SLUG . '-section3',
        ] ) );

        return $wp_customize;
    }
endif;

add_action( 'customize_register', 'odwpmm_customize_register' );


