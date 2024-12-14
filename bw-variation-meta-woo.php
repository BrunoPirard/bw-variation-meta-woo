<?php namespace BW\Variation;
defined( 'ABSPATH' ) || exit;
/**
 * @author  Bruno Pirard
 * @package BW
 * @since   1.0.0
 * @version 0.1.0
 *
 * @wordpress-plugin
 * Plugin Name:     WooCommerce Custom Variation Meta
 * Description:     Add custom fields to variations.
 * Version:         0.1.0
 * Plugin URI:      https://bulgaweb.com/plugins
 * Author:          Bruno Pirard
 * Author URI:      https://bulgaweb.com/
 * Text Domain:     woocommerce-custom-variation-meta
 * Domain Path:     /languages/
 * 
 * Requires PHP:    7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.4.3
 * Requires at least: 5.0
 * WC HPOS Compatible: yes
 * 
 * License:         GPL-2.0 or later
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @copyright 2017-2024 Bruno Pirard
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * ( at your option ) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */



// Définitions des constantes
define('BW_VARIATION_VERSION', '0.1.0');
define('BW_VARIATION_URL', plugin_dir_url(__FILE__));
define('BW_VARIATION_PATH', plugin_dir_path(__FILE__));
define('BW_VARIATION_ASSETS_URL', BW_VARIATION_URL . 'assets/');

/**
 * Classe principale d'initialisation du plugin
 */
final class Plugin {
    /**
     * Instance unique de la classe
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Obtenir l'instance unique de la classe
     *
     * @return Plugin
     */
    public static function get_instance(): Plugin {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes the plugin hooks.
     *
     * This method registers the necessary WordPress actions and filters for the plugin.
     * It loads the text domain for translations, initializes the plugin when all plugins
     * are loaded, and adds custom settings links to the plugin's action links.
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'load_textdomain']);
        add_action('plugins_loaded', [$this, 'init_plugin']);
        // Correction ici - utilisez un array callback avec $this
        add_filter('plugin_action_links_bw-variation-meta-woo/bw-variation-meta-woo.php', 
            [$this, 'add_plugin_settings_link']
        );
    }


    /**
     * Charge le domaine de traduction du plugin.
     *
     * Appelé par l'action 'init', ce méthode charge le domaine de traduction
     * du plugin pour permettre la traduction des messages.
     *
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'woocommerce-custom-variation-meta',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Initializes the plugin by requiring the main files and instantiating the core.
     *
     * This method is called when the plugin is loaded and initializes the plugin by
     * requiring the main files and instantiating the core class.
     *
     * @return void
     */
    public function init_plugin(): void {
        // Chargement des fichiers principaux
        require_once BW_VARIATION_PATH . 'includes/class-bw-variation-core.php';
        
        // Initialisation du core avec le namespace correct
        \BW\Variation\Core::get_instance();
    }

    /**
     * Adds custom settings and documentation links to the plugin action links.
     *
     * This function modifies the default plugin action links by adding custom
     * links to the plugin's settings page and documentation. The settings link
     * redirects to the plugin's settings page within the WordPress admin, while
     * the docs link opens the documentation in a new tab.
     *
     * @param array $links An array of existing plugin action links.
     * @return array Modified array of plugin action links with added custom links.
     */
    public function add_plugin_settings_link($links) {
        // Add settings link
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=variation_meta') . '">' . __('Settings', 'woocommerce-custom-variation-meta') . '</a>';
        
        // Add documentation link
        $docs_link = '<a href="https://bulgaweb.com/plugins" target="_blank">' . __('Docs', 'woocommerce-custom-variation-meta') . '</a>';
        
        // Add links to the beginning of the array
        array_unshift($links, $settings_link, $docs_link);
        
        return $links;
    }

}

// Démarrage du plugin
Plugin::get_instance();
