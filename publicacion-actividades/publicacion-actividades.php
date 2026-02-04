<?php
/**
 * Plugin Name: Publicación de Actividades
 * Description: Formulario (por roles) para solicitar publicaciones; crea posts en estado pendiente y notifica por email al publicarse.
 * Version: 0.1.0
 * Author: Tu equipo
 * Text Domain: publicacion-actividades
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PACT_PLUGIN_VERSION', '0.1.0');
define('PACT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PACT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PACT_OPTION_KEY', 'pact_options');

autoload_pact();

function autoload_pact(): void {
    require_once PACT_PLUGIN_DIR . 'includes/options.php';
    require_once PACT_PLUGIN_DIR . 'includes/admin.php';
    require_once PACT_PLUGIN_DIR . 'includes/form.php';
    require_once PACT_PLUGIN_DIR . 'includes/email.php';
}

register_activation_hook(__FILE__, function (): void {
    pact_options_ensure_defaults();
});

add_action('init', function (): void {
    // Shortcode para incrustar el formulario en una página.
    add_shortcode('publicacion_actividades_form', 'pact_render_form_shortcode');
});

add_action('admin_init', function (): void {
    pact_register_settings();
});

add_action('admin_menu', function (): void {
    pact_register_settings_page();
});

add_action('wp_enqueue_scripts', function (): void {
    wp_register_style('pact-form', PACT_PLUGIN_URL . 'assets/form.css', [], PACT_PLUGIN_VERSION);
});

// Handler del formulario (solo usuarios logueados).
add_action('admin_post_pact_submit', 'pact_handle_form_submission');

// Notificación al publicarse (solo posts creados por este plugin).
add_action('transition_post_status', 'pact_maybe_send_publish_email', 10, 3);
