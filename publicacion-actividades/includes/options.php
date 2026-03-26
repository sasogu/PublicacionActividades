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

        // Etiquetas (tags) permitidas (IDs).
        // Compat: allowed_tag_ids existía originalmente y se usará como fallback para actividad.
        'allowed_tag_ids' => [],
        'allowed_activity_tag_ids' => [],
        'allowed_dojo_tag_ids' => [],

        // Categoría por defecto para los posts creados (ID). 0 = sin asignar.
        'default_category_id' => 0,

        // Email: enviar al usuario solicitante.
        'email_to_submitter' => 1,

        // Email: destinatarios extra (separados por comas).
        'email_extra_recipients' => '',

        // Email: avisos cuando un usuario envía el formulario (destinatarios coma-separados).
        'submission_notify_recipients' => '',

        // Plantillas para el email principal con la solicitud completa.
        'submission_email_subject' => 'Nueva solicitud de actividad: {post_title}',
        'submission_email_body' => "Se ha recibido una nueva solicitud de actividad.\n\nSolicitante: {display_name}\nUsuario: {user_email}\nTítulo: {post_title}\n\n{submission_summary}\n",

        // Plantillas para copia/acuse al solicitante.
        'email_subject' => 'Hemos recibido tu solicitud: {post_title}',
        'email_body' => "Hola {display_name},\n\nHemos recibido correctamente tu solicitud de actividad con estos datos:\n\n{submission_summary}\n",
    ];
}

function pact_options_get(): array {
    $defaults = pact_options_default();
    $saved = get_option(PACT_OPTION_KEY);

    if (!is_array($saved)) {
        return $defaults;
    }

    $merged = array_merge($defaults, $saved);

    // Compat: si venimos de una versión anterior, allowed_tag_ids se interpretaba como actividad.
    if (empty($merged['allowed_activity_tag_ids']) && !empty($merged['allowed_tag_ids'])) {
        $merged['allowed_activity_tag_ids'] = (array) $merged['allowed_tag_ids'];
    }

    return $merged;
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
