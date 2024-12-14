<?php 
namespace BW\Variation;

defined('ABSPATH') || exit;

class Core {
    private static $instance = null;
    private $version_woo = '8.0';
    private $settings = null;
    private $plugin_file;

    /**
     * Retrieves the singleton instance of the BW_Variation_Core class.
     *
     * @return BW_Variation_Core The singleton instance of the class.
     */
    public static function get_instance(): Core {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor method. Called when the class is instantiated.
     *
     * Initializes the plugin by declaring compatibility with the WooCommerce HPOS
     * (Headless Commerce) feature and adding hooks for variation fields.
     *
     * @return void
     */
    public function __construct() {
        $this->plugin_file = BW_VARIATION_PATH . 'bw-variation-meta-woo.php';
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        
        // Ajout des hooks pour les variations
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_variation_fields'), 10, 3);
        add_filter('woocommerce_available_variation', array($this, 'add_variation_data'), 10, 3);
        
        $this->init();
    }

    /**
     * Initializes the plugin by verifying WooCommerce activation and adding necessary hooks.
     *
     * This method checks if WooCommerce is active and then loads both admin and frontend scripts.
     * It integrates settings into WooCommerce by adding a custom settings tab and handles the
     * updating of these settings. The method also manages hooks related to product variations,
     * including adding and saving custom variation fields. Additionally, it registers an uninstall
     * hook to ensure proper cleanup during plugin removal.
     */
    private function init() {
        // Vérifier WooCommerce
        if (!$this->is_woocommerce_active()) {
            return;
        }

        // Charger les scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));

        // Ajouter les settings dans WooCommerce
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_variation_meta', array($this, 'settings_tab'));
        add_action('woocommerce_update_options_variation_meta', array($this, 'update_settings'));
        add_action('woocommerce_admin_field_variation_meta_fields', array($this, 'meta_fields_callback'));

        // Hooks pour les variations
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_variation_fields'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_fields'), 10, 2);
        add_action('woocommerce_admin_process_variation_object', array($this, 'save_variation_options'), 10, 2);

        // Remplacer le template des variations
        add_action('woocommerce_variable_add_to_cart', array($this, 'custom_variation_template'), 30);

        // Charger la désinstallation
        register_uninstall_hook($this->plugin_file, array('BW_Variation_Uninstall', 'uninstall'));
        require_once BW_VARIATION_PATH . 'includes/class-bw-variation-uninstall.php';
    }
    
    /**
     * Adds custom fields to the variation fields metabox on the product edit page.
     *
     * This method is called when displaying the variation fields metabox on the product
     * edit page. It loops through all the custom fields defined in the plugin settings
     * and displays them as text inputs. The values are retrieved from the variation's
     * post meta and saved when the product is updated.
     *
     * @param int $loop The loop index of the variation being displayed.
     * @param array $variation_data The variation data.
     * @param WC_Product_Variation $variation The variation object.
     *
     * @return void
     */
    public function add_variation_fields($loop, $variation_data, $variation) {
        //error_log('=== Debug add_variation_fields ===');
        //error_log('Loop: ' . $loop);
        //error_log('Variation ID: ' . $variation->ID);
        
        $options = get_option('bw_variation_meta_fields', array());
        //error_log('Options: ' . print_r($options, true));

        $options = get_option('bw_variation_meta_fields', array());
        
        if (empty($options['labels'])) {
            return;
        }

        echo '<div class="variation-custom-fields">';
        
        foreach ($options['labels'] as $key => $label) {
            if (empty($label)) continue;
            
            $field_id = isset($options['fields'][$key]) ? $options['fields'][$key] : sanitize_title($label);
            $description = isset($options['descriptions'][$key]) ? $options['descriptions'][$key] : '';
            
            // Récupérer la valeur
            $value = get_post_meta($variation->ID, '_' . $field_id, true);
            
            // Alterner entre form-row-first et form-row-last
            $wrapper_class = ($key % 2 === 0) ? 'form-row form-row-first' : 'form-row form-row-last';
            
            woocommerce_wp_text_input(array(
                'id' => $field_id . '[' . $loop . ']', // Important : garder ce format
                'name' => $field_id . '[' . $loop . ']', // Important : garder ce format
                'label' => $label,
                'class' => 'short',
                'desc_tip' => true,
                'description' => $description,
                'value' => $value,
                'wrapper_class' => $wrapper_class,
            ));

            if ($key % 2 === 1) {
                echo '<div class="clear"></div>';
            }
        }
        
        if (count($options['labels']) % 2 === 1) {
            echo '<div class="clear"></div>';
        }
        
        echo '</div>';
    }
 
    /**
     * Save custom variation fields.
     *
     * This function is called when saving a product, and it loops through all
     * the custom fields defined in the plugin settings and saves their value
     * for the current variation.
     *
     * @param int $variation_id The ID of the variation being saved.
     * @param int $i The index of the variation in the loop.
     */
    public function save_variation_fields($variation_id, $i) {
        //error_log('=== Debug save_variation_fields ===');
        //error_log('Variation ID: ' . $variation_id);
        //error_log('Index: ' . $i);
        
        $options = get_option('bw_variation_meta_fields', array());
        
        if (empty($options['labels'])) {
            //error_log('No labels defined in options');
            return;
        }

        foreach ($options['labels'] as $key => $label) {
            $field_id = sanitize_title($label); // Générer l'ID du champ à partir du label
            //error_log('Processing field: ' . $field_id);
            
            if (isset($_POST[$field_id]) && isset($_POST[$field_id][$i])) {
                $value = wc_clean($_POST[$field_id][$i]);
                //error_log('Saving value: ' . $value);
                update_post_meta($variation_id, '_' . $field_id, $value);
                
                // Vérification immédiate
                $saved_value = get_post_meta($variation_id, '_' . $field_id, true);
                //error_log('Verified saved value: ' . $saved_value);
            } else {
                //error_log('Field ' . $field_id . ' not found in POST data');
                //error_log('POST data: ' . print_r($_POST, true));
            }
        }
    }

    /**
     * Sauvegarde les champs personnalisés de variation
     *
     * Gère la sauvegarde des champs personnalisés de variation
     * lors de la modification d'une variation de produit.
     *
     * @param WC_Product_Variation $variation La variation de produit.
     * @param int $i L'index de la variation.
     */
    public function save_variation_options($variation, $i) {
        //error_log('=== Debug save_variation_options ===');
        //error_log('Variation ID: ' . $variation->get_id());
        
        $options = get_option('bw_variation_meta_fields', array());
        
        if (empty($options['labels'])) {
            //error_log('No labels defined in options');
            return;
        }

        foreach ($options['labels'] as $key => $label) {
            $field_id = sanitize_title($label);
            //error_log('Processing field: ' . $field_id);
            
            if (isset($_POST[$field_id]) && isset($_POST[$field_id][$i])) {
                $value = wc_clean($_POST[$field_id][$i]);
                //error_log('Updating meta data for ' . $field_id . ' with value ' . $value);
                $variation->update_meta_data('_' . $field_id, $value);
            }
        }
        
        $variation->save();
    }

    /**
     * Ajoute les champs personnalisés de variation dans le tableau des détails de variation
     *
     * @param array $variation_data Les données de la variation.
     * @param WC_Product $product Le produit parent.
     * @param WC_Product_Variation $variation La variation.
     * @return array Les données de la variation mises à jour.
     */
    public function add_variation_data($variation_data, $product, $variation) {
        $options = get_option('bw_variation_meta_fields', array());
        
        if (empty($options['fields'])) {
            return $variation_data;
        }

        foreach ($options['fields'] as $key => $field_id) {
            // Vérifier si le champ doit être caché
            if (!empty($options['hide']) && in_array($field_id, $options['hide'])) {
                continue;
            }

            $value = get_post_meta($variation->get_id(), '_' . $field_id, true);
            if ($value) {
                // Créer une clé HTML spécifique pour chaque champ
                $html_key = $field_id . '_html';
                $variation_data[$html_key] = esc_html($value);
            }
        }

        return $variation_data;
    }

    /**
     * Declare compatibility with HPOS (High Performance Order Storage) for all the features.
     *
     * @since 0.1.0
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', $this->plugin_file, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('remote_management_api', $this->plugin_file, true);
        }
    }

    /**
     * Checks if WooCommerce is installed and activated, and verifies the version.
     *
     * If WooCommerce is not installed or activated, or if the installed version
     * is lower than the required version, the plugin is deactivated and a notice
     * is displayed with a link to download WooCommerce or update it to the required
     * version.
     *
     * @return bool Returns true if WooCommerce is active and the version is sufficient.
     */
    public function is_woocommerce_active() {

        if (!defined('WC_VERSION')) {
            $this->deactivate_with_notice(
                sprintf(
                    /* translators: %1$s: WooCommerce URL, %2$s: Plugins page URL */
                    __('This plugin requires WooCommerce to be installed and activated. You can download %1$s here. <br><a href="%2$s">Back to plugins page</a>', 'woocommerce-custom-variation-meta'),
                    '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>',
                    admin_url('plugins.php')
                )
            );
            return false;
        }

        if (version_compare(WC_VERSION, $this->version_woo, '<')) {
            $this->deactivate_with_notice(
                sprintf(
                    /* translators: %1$s: Minimum WooCommerce version, %2$s: WooCommerce URL, %3$s: Plugins page URL */
                    __('This plugin requires WooCommerce version %1$s or higher. You can download %2$s here. <br><a href="%3$s">Back to plugins page</a>', 'woocommerce-custom-variation-meta'),
                    $this->version_woo,
                    '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>',
                    admin_url('plugins.php')
                )
            );
            return false;
        }

        return true;
    }

    /**
     * Enqueues the admin CSS and JavaScript files in the WooCommerce settings page.
     *
     * @param string $hook The current admin page hook.
     */
    public function admin_scripts($hook) {
        if ('woocommerce_page_wc-settings' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'bw-variation-admin',
            BW_VARIATION_ASSETS_URL . 'css/bw-variation-admin.css',
            array(),
            BW_VARIATION_VERSION
        );

        wp_enqueue_script(
            'bw-variation-admin',
            BW_VARIATION_ASSETS_URL . 'js/bw-variation-admin.js',
            array(),
            BW_VARIATION_VERSION,
            true
        );
    }

    /**
     * Enqueues the frontend CSS and JavaScript files in the product page.
     *
     * This function is hooked to `wp_enqueue_scripts` and is only executed when
     * the current page is a product page.
     */
    public function frontend_scripts() {
        if (!is_product()) {
            return;
        }

        wp_enqueue_style(
            'bw-variation-frontend',
            BW_VARIATION_ASSETS_URL . 'css/bw-variation-styles.css',
            array(),
            BW_VARIATION_VERSION
        );

        wp_enqueue_script(
            'bw-variation-frontend',
            BW_VARIATION_ASSETS_URL . 'js/bw-variation-frontend.js',
            array(),
            BW_VARIATION_VERSION,
            true
        );
    }

    /**
     * Adds a new settings tab to the WooCommerce settings page for the Variation Meta Fields.
     *
     * This method is hooked to `woocommerce_get_settings_pages` and is responsible for adding a
     * new settings tab to the WooCommerce settings page. The title of the tab is "Variation Meta".
     *
     * @param array $settings_tabs Array of settings tabs.
     * @return array Modified settings tabs array.
     */
    public function add_settings_tab($settings_tabs) {
        $settings_tabs['variation_meta'] = __('Variation Meta', 'woocommerce-custom-variation-meta');
        return $settings_tabs;
    }

    /**
     * Renders the settings tab for the Variation Meta Fields.
     *
     * This method is responsible for displaying the settings fields for the
     * Variation Meta Fields in the WooCommerce settings tab. It utilizes
     * WooCommerce's `woocommerce_admin_fields` function to generate the form
     * fields based on the settings array retrieved from the `get_settings` method.
     *
     * @return void
     */
    public function settings_tab() {
        woocommerce_admin_fields($this->get_settings());
    }

    /**
     * Updates the settings for the Variation Meta Fields.
     *
     * This method is hooked to `woocommerce_update_options_variation_meta` and is responsible
     * for updating the settings for the Variation Meta Fields in the WooCommerce settings page.
     * It retrieves the settings from the `get_settings` method and uses WooCommerce's API to
     * update them in the database.
     *
     * @return void
     */
    public function update_settings() {
        woocommerce_update_options($this->get_settings());
    }

    /**
     * Retrieves the settings for the Variation Meta Fields settings tab.
     *
     * This method returns an array of settings that are used to generate the
     * Variation Meta Fields settings tab in the WooCommerce settings page. The
     * array contains settings for the title, description, and the custom meta
     * fields themselves.
     *
     * @return array The settings for the Variation Meta Fields settings tab.
     */
    public function get_settings() {
        $settings = array(
            array(
                'title' => __('Variation Meta Fields Settings', 'woocommerce-custom-variation-meta'),
                'type'  => 'title',
                'desc'  => __('Add your custom variation meta fields below:', 'woocommerce-custom-variation-meta'),
                'id'    => 'variation_meta_options'
            ),
            array(
                'type'     => 'variation_meta_fields',
                'id'       => 'bw_variation_meta_fields'
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'variation_meta_options'
            )
        );

        return apply_filters('woocommerce_variation_meta_settings', $settings);
    }

    /**
     * Generates the custom meta fields HTML for the settings tab.
     *
     * This method is hooked to `woocommerce_admin_field_variation_meta_fields` and is responsible for
     * rendering the custom meta fields form in the settings tab. It retrieves the current values from the
     * database and generates the required HTML elements for each field, including the label, description,
     * hide checkbox, and remove button. The method also adds a button to add a new field and handles the
     * JavaScript required to make the HTML interactive.
     *
     * @param array $value The current value of the custom meta fields.
     *
     * @since 0.1.0
     */
    public function meta_fields_callback($value) {
        $options = get_option('bw_variation_meta_fields', array());
        $fields = isset($options['fields']) ? $options['fields'] : array();
        $labels = isset($options['labels']) ? $options['labels'] : array();
        $descriptions = isset($options['descriptions']) ? $options['descriptions'] : array();
        $hide_fields = isset($options['hide']) ? $options['hide'] : array();
        $templates = isset($options['templates']) ? $options['templates'] : array(); // Nouveau tableau pour les templates
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="meta_fields"><?php _e('Meta Fields', 'woocommerce-custom-variation-meta'); ?></label>
            </th>
            <td class="forminp forminp-text">
                <div id="meta-fields-container">
                    <?php
                    if (!empty($labels)) {
                        foreach ($labels as $key => $label) {
                            $description_value = isset($descriptions[$key]) ? $descriptions[$key] : '';
                            $field_value = isset($fields[$key]) ? $fields[$key] : sanitize_title($label);
                            $template_value = isset($templates[$key]) ? $templates[$key] : 'data.variation.' . sanitize_title($label) . '_html';
                            ?>
                            <div class="meta-field">
                                <input type="text" 
                                    name="bw_variation_meta_fields[labels][]" 
                                    value="<?php echo esc_attr($label); ?>" 
                                    placeholder="<?php _e('Field Label', 'woocommerce-custom-variation-meta'); ?>"
                                    class="field-label">
                                <input type="text" 
                                    name="bw_variation_meta_fields[descriptions][]" 
                                    value="<?php echo esc_attr($description_value); ?>" 
                                    placeholder="<?php _e('Field Description', 'woocommerce-custom-variation-meta'); ?>">
                                <input type="text" 
                                    name="bw_variation_meta_fields[templates][]" 
                                    value="<?php echo esc_attr($template_value); ?>" 
                                    placeholder="<?php _e('Template Variable', 'woocommerce-custom-variation-meta'); ?>"
                                    class="field-template">
                                <label class="hide-field-label">
                                    <input type="checkbox" 
                                        name="bw_variation_meta_fields[hide][]" 
                                        value="<?php echo esc_attr($field_value); ?>" 
                                        <?php checked(in_array($field_value, $hide_fields)); ?>>
                                    <?php _e('Hide on product', 'woocommerce-custom-variation-meta'); ?>
                                </label>
                                <button type="button" class="button remove-field">
                                    <?php _e('Remove', 'woocommerce-custom-variation-meta'); ?>
                                </button>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
                <button type="button" class="button" id="add-field">
                    <?php _e('Add Field', 'woocommerce-custom-variation-meta'); ?>
                </button>
            </td>
        </tr>
        <?php
    }


    /**
     * Saves the variation meta field settings to the database.
     *
     * This method is hooked to `woocommerce_update_options_variation_meta` and is
     * responsible for sanitizing and saving the meta field settings to the database.
     *
     * @return void
     */
    public function save_settings() {
        if (!isset($_POST['bw_variation_meta_fields'])) {
            return;
        }

        $input = $_POST['bw_variation_meta_fields'];
        $clean = array(
            'labels' => array(),
            'descriptions' => array(),
            'fields' => array(),
            'hide' => array(),
            'templates' => array() // Ajout du tableau templates
        );

        if (!empty($input['labels'])) {
            foreach ($input['labels'] as $key => $label) {
                if (!empty($label)) {
                    $clean['labels'][] = sanitize_text_field($label);
                    $clean['fields'][] = sanitize_title($label);
                    $clean['descriptions'][] = isset($input['descriptions'][$key]) ? 
                        sanitize_text_field($input['descriptions'][$key]) : '';
                    $clean['templates'][] = isset($input['templates'][$key]) ? 
                        sanitize_text_field($input['templates'][$key]) : 'data.variation.' . sanitize_title($label) . '_html';
                }
            }
        }

        if (!empty($input['hide'])) {
            $clean['hide'] = array_map('sanitize_text_field', $input['hide']);
        }

        update_option('bw_variation_meta_fields', $clean);
    }


    public function custom_variation_template() {
        global $product;
        $options = get_option('bw_variation_meta_fields', array());
        
        if (empty($options['fields'])) {
            return;
        }

        // Commencer le template
        $template = '
        <script type="text/template" id="tmpl-variation-template">
            <div class="woocommerce-variation-description">{{{ data.variation.variation_description }}}</div>
            <div class="woocommerce-variation-price">{{{ data.variation.price_html }}}</div>';

        // Ajouter une div pour chaque champ personnalisé
        foreach ($options['fields'] as $key => $field_id) {
            if (!empty($options['hide']) && in_array($field_id, $options['hide'])) {
                continue;
            }
            
            $label = isset($options['labels'][$key]) ? $options['labels'][$key] : $field_id;
            $template .= sprintf(
                '<div class="woocommerce-variation-%s"><span class="label">%s: </span>{{{ data.variation.%s_html }}}</div>',
                esc_attr($field_id),
                esc_html($label),
                esc_attr($field_id)
            );
        }

        // Fermer le template
        $template .= '
        </script>
        <script type="text/template" id="tmpl-unavailable-variation-template">
            <p>' . esc_html__('Sorry, this product is unavailable. Please choose a different combination.', 'woocommerce') . '</p>
        </script>';

        echo $template;
        // Afficher le sélecteur de variations
        woocommerce_variable_add_to_cart();

    }


    /**
     * Deactivate plugin and show notice
     *
     * @param string $message
     */
    public function deactivate_with_notice($message) {
        deactivate_plugins(plugin_basename(WOO_DAY_EVENTS_PATH . 'woocommerce-day-events.php'));
        wp_die($message);
    }        
}
