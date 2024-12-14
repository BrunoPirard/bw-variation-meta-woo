<?php
namespace BW\Variation;

defined('ABSPATH') || exit;

/**
 * Classe principale du plugin
 *
 * @package BW\Variation
 * @since 1.0.0
 */
class Uninstall {
    /**
     * Instance unique de la classe
     *
     * @var Core
     */
    private static $instance = null;

    /**
     * Constructeur privé
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Obtenir l'instance unique de la classe
     *
     * @return Core
     */
    public static function get_instance(): Core {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialisation
     */
    private function init(): void {
        // Enregistre le hook de désinstallation
        register_uninstall_hook(BW_VARIATION_PATH . 'custom-meta-woo.php', [__CLASS__, 'uninstall']);

    }

    /**
     * Méthode de désinstallation
     */
    public static function uninstall(): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Suppression des options
        delete_option('bw_variation_meta_fields');

        // Suppression des meta données
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
                '_bw_variation_%'
            )
        );
    }
}
