<?php

if (!defined('ABSPATH')) {
    exit;
}

function pact_register_settings(): void {
    register_setting(
        'pact_settings',
        PACT_OPTION_KEY,
        [
            'type' => 'array',
            'sanitize_callback' => 'pact_sanitize_options',
            'default' => pact_options_default(),
        ]
    );

    add_settings_section(
        'pact_section_main',
        __('Publicación de Actividades', 'publicacion-actividades'),
        function (): void {
            echo '<p>' . esc_html__('Configura quién puede solicitar publicaciones, qué etiquetas se pueden elegir y cómo se envían los avisos.', 'publicacion-actividades') . '</p>';
        },
        'pact_settings'
    );

    add_settings_field(
        'allowed_roles',
        __('Roles permitidos', 'publicacion-actividades'),
        'pact_field_allowed_roles',
        'pact_settings',
        'pact_section_main'
    );

    add_settings_field(
        'allowed_activity_tag_ids',
        __('Etiquetas permitidas (Tipo actividad)', 'publicacion-actividades'),
        'pact_field_allowed_activity_tags',
        'pact_settings',
        'pact_section_main'
    );

    add_settings_field(
        'allowed_dojo_tag_ids',
        __('Etiquetas permitidas (Dojo solicitante)', 'publicacion-actividades'),
        'pact_field_allowed_dojo_tags',
        'pact_settings',
        'pact_section_main'
    );

    add_settings_field(
        'default_category_id',
        __('Categoría por defecto', 'publicacion-actividades'),
        'pact_field_default_category',
        'pact_settings',
        'pact_section_main'
    );

    add_settings_field(
        'email_to_submitter',
        __('Aviso al publicarse (al solicitante)', 'publicacion-actividades'),
        'pact_field_email_to_submitter',
        'pact_settings',
        'pact_section_main'
    );

    add_settings_field(
        'email_extra_recipients',
        __('Destinatarios extra (coma-separados)', 'publicacion-actividades'),
        'pact_field_email_extra_recipients',
        'pact_settings',
        'pact_section_main'
    );

    add_settings_field(
        'submission_notify_recipients',
        __('Aviso al enviar (destinatarios)', 'publicacion-actividades'),
        'pact_field_submission_notify_recipients',
        'pact_settings',
        'pact_section_main'
    );

    add_settings_field(
        'submission_email_templates',
        __('Plantillas email (aviso al enviar)', 'publicacion-actividades'),
        'pact_field_submission_email_templates',
        'pact_settings',
        'pact_section_main'
    );

    add_settings_field(
        'email_templates',
        __('Plantillas de email', 'publicacion-actividades'),
        'pact_field_email_templates',
        'pact_settings',
        'pact_section_main'
    );
}

function pact_register_settings_page(): void {
    add_options_page(
        __('Publicación de Actividades', 'publicacion-actividades'),
        __('Publicación Actividades', 'publicacion-actividades'),
        'manage_options',
        'pact_settings',
        'pact_render_settings_page'
    );
}

function pact_render_settings_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Publicación de Actividades', 'publicacion-actividades') . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('pact_settings');
    do_settings_sections('pact_settings');
    submit_button();
    echo '</form>';
    echo '<hr />';
    echo '<p><strong>' . esc_html__('Shortcode:', 'publicacion-actividades') . '</strong> <code>[publicacion_actividades_form]</code></p>';
    echo '</div>';
}

function pact_sanitize_options($input): array {
    $defaults = pact_options_default();
    $output = pact_options_get();

    if (!is_array($input)) {
        return $output;
    }

    // Roles.
    $roles = isset($input['allowed_roles']) ? (array) $input['allowed_roles'] : [];
    $wp_roles = wp_roles();
    $valid_roles = array_keys($wp_roles->roles);
    $roles = array_values(array_intersect($roles, $valid_roles));
    $output['allowed_roles'] = $roles;

    // Tags permitidas (actividad).
    $activity_tag_ids = isset($input['allowed_activity_tag_ids']) ? (array) $input['allowed_activity_tag_ids'] : [];
    $activity_tag_ids = array_values(array_filter(array_map('absint', $activity_tag_ids)));
    $output['allowed_activity_tag_ids'] = $activity_tag_ids;

    // Tags permitidas (dojo solicitante).
    $dojo_tag_ids = isset($input['allowed_dojo_tag_ids']) ? (array) $input['allowed_dojo_tag_ids'] : [];
    $dojo_tag_ids = array_values(array_filter(array_map('absint', $dojo_tag_ids)));
    $output['allowed_dojo_tag_ids'] = $dojo_tag_ids;

    // Compat: mantener allowed_tag_ids como alias de actividad.
    $output['allowed_tag_ids'] = $activity_tag_ids;

    // Default category.
    $cat_id = isset($input['default_category_id']) ? absint((string) $input['default_category_id']) : 0;
    if ($cat_id > 0) {
        $term = get_term($cat_id, 'category');
        $output['default_category_id'] = (!is_wp_error($term) && $term && !empty($term->term_id)) ? (int) $term->term_id : 0;
    } else {
        $output['default_category_id'] = 0;
    }

    // Email flags.
    $output['email_to_submitter'] = !empty($input['email_to_submitter']) ? 1 : 0;

    // Extra recipients.
    $extra = isset($input['email_extra_recipients']) ? (string) $input['email_extra_recipients'] : '';
    $extra = sanitize_text_field($extra);
    $output['email_extra_recipients'] = $extra;

    // Submission notify recipients.
    $sub = isset($input['submission_notify_recipients']) ? (string) $input['submission_notify_recipients'] : '';
    $sub = sanitize_text_field($sub);
    $output['submission_notify_recipients'] = $sub;

    // Submission templates.
    $sub_subject = isset($input['submission_email_subject']) ? (string) $input['submission_email_subject'] : $defaults['submission_email_subject'];
    $sub_body = isset($input['submission_email_body']) ? (string) $input['submission_email_body'] : $defaults['submission_email_body'];
    $output['submission_email_subject'] = sanitize_text_field($sub_subject);
    $output['submission_email_body'] = wp_kses_post($sub_body);

    // Templates.
    $subject = isset($input['email_subject']) ? (string) $input['email_subject'] : $defaults['email_subject'];
    $body = isset($input['email_body']) ? (string) $input['email_body'] : $defaults['email_body'];
    $output['email_subject'] = sanitize_text_field($subject);
    $output['email_body'] = wp_kses_post($body);

    return $output;
}

function pact_field_allowed_roles(): void {
    $options = pact_options_get();
    $selected = $options['allowed_roles'] ?? [];
    $roles = wp_roles()->roles;

    echo '<fieldset>';
    foreach ($roles as $role_key => $role_info) {
        $checked = in_array($role_key, (array) $selected, true) ? 'checked' : '';
        echo '<label style="display:block;margin:2px 0;">';
        echo '<input type="checkbox" name="' . esc_attr(PACT_OPTION_KEY) . '[allowed_roles][]" value="' . esc_attr($role_key) . '" ' . $checked . ' /> ';
        echo esc_html($role_info['name']);
        echo '</label>';
    }
    echo '<p class="description">' . esc_html__('Solo estos roles verán y podrán enviar el formulario.', 'publicacion-actividades') . '</p>';
    echo '</fieldset>';
}

function pact_get_all_tags_for_settings(): array {
    $tags = get_terms([
        'taxonomy' => 'post_tag',
        'hide_empty' => false,
        'number' => 200,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if (is_wp_error($tags) || empty($tags)) {
        $create_url = admin_url('edit-tags.php?taxonomy=post_tag');
        echo '<p class="description">' . esc_html__('No se han encontrado etiquetas (tags). Crea al menos una etiqueta para poder seleccionarla aquí.', 'publicacion-actividades') . '</p>';
        echo '<p><a href="' . esc_url($create_url) . '">' . esc_html__('Ir a Etiquetas', 'publicacion-actividades') . '</a></p>';
        return [];
    }

    return (array) $tags;
}

function pact_render_tag_multiselect(string $field_key, array $selected_ids, string $description): void {
    $tags = pact_get_all_tags_for_settings();

    echo '<select name="' . esc_attr(PACT_OPTION_KEY) . '[' . esc_attr($field_key) . '][]" multiple size="10" style="min-width:320px;">';
    foreach ($tags as $tag) {
        $is_selected = in_array((int) $tag->term_id, array_map('intval', (array) $selected_ids), true) ? 'selected' : '';
        echo '<option value="' . esc_attr((string) $tag->term_id) . '" ' . $is_selected . '>' . esc_html($tag->name) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">' . esc_html($description) . '</p>';
}

function pact_field_allowed_activity_tags(): void {
    $options = pact_options_get();
    $selected = (array) ($options['allowed_activity_tag_ids'] ?? $options['allowed_tag_ids'] ?? []);
    pact_render_tag_multiselect('allowed_activity_tag_ids', $selected, __('Estas etiquetas alimentan el desplegable “Tipo actividad” del formulario (selecciona al menos 1).', 'publicacion-actividades'));
}

function pact_field_allowed_dojo_tags(): void {
    $options = pact_options_get();
    $selected = (array) ($options['allowed_dojo_tag_ids'] ?? []);
    pact_render_tag_multiselect('allowed_dojo_tag_ids', $selected, __('Estas etiquetas alimentan el desplegable “Dojo solicitante” del formulario (selecciona al menos 1).', 'publicacion-actividades'));
}

function pact_field_default_category(): void {
    $options = pact_options_get();
    $selected = isset($options['default_category_id']) ? (int) $options['default_category_id'] : 0;

    wp_dropdown_categories([
        'taxonomy' => 'category',
        'hide_empty' => 0,
        'name' => esc_attr(PACT_OPTION_KEY) . '[default_category_id]',
        'selected' => $selected,
        'show_option_none' => __('— Sin categoría por defecto —', 'publicacion-actividades'),
        'option_none_value' => '0',
        'orderby' => 'name',
    ]);

    echo '<p class="description">' . esc_html__('Se asignará esta categoría automáticamente a los posts creados por el formulario (además de la etiqueta).', 'publicacion-actividades') . '</p>';
}

function pact_field_email_to_submitter(): void {
    $options = pact_options_get();
    $checked = !empty($options['email_to_submitter']) ? 'checked' : '';

    echo '<label>';
    echo '<input type="checkbox" name="' . esc_attr(PACT_OPTION_KEY) . '[email_to_submitter]" value="1" ' . $checked . ' /> ';
    echo esc_html__('Enviar email al usuario que envió la solicitud cuando el post pase a publicado (activado por defecto).', 'publicacion-actividades');
    echo '</label>';
}

function pact_field_email_extra_recipients(): void {
    $options = pact_options_get();
    $value = (string) ($options['email_extra_recipients'] ?? '');

    echo '<input type="text" class="regular-text" name="' . esc_attr(PACT_OPTION_KEY) . '[email_extra_recipients]" value="' . esc_attr($value) . '" placeholder="equipo@tu-dominio.com, admin@tu-dominio.com" />';
}

function pact_field_submission_notify_recipients(): void {
    $options = pact_options_get();
    $value = (string) ($options['submission_notify_recipients'] ?? '');

    echo '<input type="text" class="regular-text" name="' . esc_attr(PACT_OPTION_KEY) . '[submission_notify_recipients]" value="' . esc_attr($value) . '" placeholder="comunicacion@tu-dominio.com, admin@tu-dominio.com" />';
    echo '<p class="description">' . esc_html__('Se avisará a estos correos cuando un usuario envíe el formulario y se cree el post pendiente.', 'publicacion-actividades') . '</p>';
}

function pact_field_submission_email_templates(): void {
    $options = pact_options_get();
    $subject = (string) ($options['submission_email_subject'] ?? '');
    $body = (string) ($options['submission_email_body'] ?? '');

    echo '<p><label>' . esc_html__('Asunto', 'publicacion-actividades') . '</label><br />';
    echo '<input type="text" class="large-text" name="' . esc_attr(PACT_OPTION_KEY) . '[submission_email_subject]" value="' . esc_attr($subject) . '" /></p>';

    echo '<p><label>' . esc_html__('Cuerpo', 'publicacion-actividades') . '</label><br />';
    echo '<textarea class="large-text" rows="8" name="' . esc_attr(PACT_OPTION_KEY) . '[submission_email_body]">' . esc_textarea($body) . '</textarea></p>';

    echo '<p class="description">' . esc_html__('Variables disponibles: {display_name}, {post_title}, {edit_url}', 'publicacion-actividades') . '</p>';
}

function pact_field_email_templates(): void {
    $options = pact_options_get();
    $subject = (string) ($options['email_subject'] ?? '');
    $body = (string) ($options['email_body'] ?? '');

    echo '<p><label>' . esc_html__('Asunto', 'publicacion-actividades') . '</label><br />';
    echo '<input type="text" class="large-text" name="' . esc_attr(PACT_OPTION_KEY) . '[email_subject]" value="' . esc_attr($subject) . '" /></p>';

    echo '<p><label>' . esc_html__('Cuerpo', 'publicacion-actividades') . '</label><br />';
    echo '<textarea class="large-text" rows="8" name="' . esc_attr(PACT_OPTION_KEY) . '[email_body]">' . esc_textarea($body) . '</textarea></p>';

    echo '<p class="description">' . esc_html__('Variables disponibles: {display_name}, {post_title}, {post_url}', 'publicacion-actividades') . '</p>';
}
