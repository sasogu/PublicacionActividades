<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Opciones del plugin.
 */
function pact_options_default(): array {
    return [
        // Roles que pueden ver/usar el formulario.
        // Por defecto: administradores y editores.
        'allowed_roles' => ['administrator', 'editor'],

        // Etiquetas (tags) permitidas para el desplegable (IDs).
        'allowed_tag_ids' => [],

        // Categoría por defecto para los posts creados (ID). 0 = sin asignar.
        'default_category_id' => 0,

        // Email: enviar al usuario solicitante.
        'email_to_submitter' => 1,

        // Email: destinatarios extra (separados por comas).
        'email_extra_recipients' => '',

        // Email: avisos cuando un usuario envía el formulario (destinatarios coma-separados).
        'submission_notify_recipients' => '',

        // Plantillas para aviso de envío.
        'submission_email_subject' => 'Nueva solicitud pendiente: {post_title}',
        'submission_email_body' => "Se ha enviado una nueva solicitud y queda pendiente de revisión.\n\nUsuario: {display_name}\nTítulo: {post_title}\nEditar: {edit_url}\n",

        // Plantillas.
        'email_subject' => 'Tu solicitud se ha publicado: {post_title}',
        'email_body' => "Hola {display_name},\n\nTu solicitud ya está publicada.\n\nTítulo: {post_title}\nEnlace: {post_url}\n\nGracias.",
    ];
}

function pact_options_get(): array {
    $defaults = pact_options_default();
    $saved = get_option(PACT_OPTION_KEY);

    if (!is_array($saved)) {
        return $defaults;
    }

    return array_merge($defaults, $saved);
}

function pact_options_ensure_defaults(): void {
    if (get_option(PACT_OPTION_KEY) === false) {
        add_option(PACT_OPTION_KEY, pact_options_default());
        return;
    }

    // Merge para añadir nuevas claves en upgrades.
    $merged = pact_options_get();
    update_option(PACT_OPTION_KEY, $merged);
}

function pact_user_has_allowed_role(?WP_User $user = null): bool {
    if (!$user) {
        $user = wp_get_current_user();
    }

    if (!$user || empty($user->ID)) {
        return false;
    }

    $options = pact_options_get();
    $allowed = $options['allowed_roles'] ?? [];

    if (!is_array($allowed) || empty($allowed)) {
        return false;
    }

    foreach ((array) $user->roles as $role) {
        if (in_array($role, $allowed, true)) {
            return true;
        }
    }

    return false;
}
