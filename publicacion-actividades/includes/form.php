<?php

if (!defined('ABSPATH')) {
    exit;
}

function pact_render_form_shortcode($atts = []): string {
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__('Debes iniciar sesión para enviar una solicitud.', 'publicacion-actividades') . '</p>';
    }

    if (!pact_user_has_allowed_role()) {
        return '<p>' . esc_html__('No tienes permisos para acceder a este formulario.', 'publicacion-actividades') . '</p>';
    }

    $options = pact_options_get();
    $allowed_activity_tag_ids = (array) ($options['allowed_activity_tag_ids'] ?? $options['allowed_tag_ids'] ?? []);
    $allowed_dojo_tag_ids = (array) ($options['allowed_dojo_tag_ids'] ?? []);

    // Carga CSS del formulario.
    wp_enqueue_style('pact-form');

    $errors = [];
    $success = false;
    $form_values = pact_get_empty_form_values();

    if (isset($_GET['pact_status'])) {
        $status = sanitize_text_field((string) $_GET['pact_status']);
        if ($status === 'success') {
            $success = true;
            pact_clear_saved_form_data_for_current_user();
        } elseif ($status === 'error') {
            $raw = isset($_GET['pact_error']) ? (string) $_GET['pact_error'] : '';
            $errors = array_filter(array_map('sanitize_text_field', explode('|', $raw)));
            $form_values = pact_get_saved_form_data_from_request();
        }
    }

    $get_tags_by_ids = static function (array $ids): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }

        $terms = [];
        foreach ($ids as $id) {
            $term = get_term($id, 'post_tag');
            if (!is_wp_error($term) && $term && !empty($term->term_id)) {
                $terms[] = $term;
            }
        }

        usort($terms, static function ($a, $b): int {
            return strcasecmp((string) $a->name, (string) $b->name);
        });

        return $terms;
    };

    $activity_tags = $get_tags_by_ids($allowed_activity_tag_ids);
    $dojo_tags = $get_tags_by_ids($allowed_dojo_tag_ids);

    ob_start();
    ?>
    <div class="pact-form">
        <?php if ($success) : ?>
            <div class="pact-notice pact-notice--success" role="status" aria-live="polite">
                <?php echo esc_html__('Solicitud enviada por correo correctamente.', 'publicacion-actividades'); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)) : ?>
            <div class="pact-notice pact-notice--error" role="alert" aria-live="assertive">
                <p><strong><?php echo esc_html__('Revisa los siguientes errores:', 'publicacion-actividades'); ?></strong></p>
                <ul>
                    <?php foreach ($errors as $err) : ?>
                        <li><?php echo esc_html($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($activity_tags) || empty($dojo_tags)) : ?>
            <div class="pact-notice pact-notice--warning" role="status" aria-live="polite">
                <?php echo esc_html__('Este formulario no está configurado: faltan etiquetas permitidas para Tipo actividad y/o Dojo solicitante. Contacta con un administrador.', 'publicacion-actividades'); ?>
            </div>
        <?php else : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="pact_submit" />
                <?php wp_nonce_field('pact_submit_form', 'pact_nonce'); ?>

                <div class="pact-field">
                    <label for="pact_tipo_actividad"><?php echo esc_html__('Tipo actividad', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <select id="pact_tipo_actividad" name="pact_tipo_actividad" required aria-required="true">
                        <option value=""><?php echo esc_html__('Selecciona un tipo…', 'publicacion-actividades'); ?></option>
                        <?php foreach ($activity_tags as $tag) : ?>
                            <option value="<?php echo esc_attr((string) $tag->term_id); ?>" <?php selected((string) $form_values['pact_tipo_actividad'], (string) $tag->term_id); ?>><?php echo esc_html($tag->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pact-field">
                    <label for="pact_dojo_solicitante"><?php echo esc_html__('Dojo solicitante', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <select id="pact_dojo_solicitante" name="pact_dojo_solicitante" required aria-required="true">
                        <option value=""><?php echo esc_html__('Selecciona un dojo…', 'publicacion-actividades'); ?></option>
                        <?php foreach ($dojo_tags as $tag) : ?>
                            <option value="<?php echo esc_attr((string) $tag->term_id); ?>" <?php selected((string) $form_values['pact_dojo_solicitante'], (string) $tag->term_id); ?>><?php echo esc_html($tag->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pact-field">
                    <label for="pact_fecha"><?php echo esc_html__('Fecha', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_fecha" name="pact_fecha" type="date" value="<?php echo esc_attr($form_values['pact_fecha']); ?>" required aria-required="true" />
                </div>

                <div class="pact-field">
                    <label for="pact_lugar"><?php echo esc_html__('Lugar', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_lugar" name="pact_lugar" type="text" value="<?php echo esc_attr($form_values['pact_lugar']); ?>" required aria-required="true" />
                </div>

                <div class="pact-field">
                    <label for="pact_hora"><?php echo esc_html__('Hora', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_hora" name="pact_hora" type="time" value="<?php echo esc_attr($form_values['pact_hora']); ?>" required aria-required="true" />
                </div>

                <div class="pact-field">
                    <label for="pact_aportacion"><?php echo esc_html__('Aportación', 'publicacion-actividades'); ?></label>
                    <input id="pact_aportacion" name="pact_aportacion" type="text" value="<?php echo esc_attr($form_values['pact_aportacion']); ?>" placeholder="" />
                </div>

                <div class="pact-field">
                    <label for="pact_email_contacto"><?php echo esc_html__('Email contacto', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_email_contacto" name="pact_email_contacto" type="email" value="<?php echo esc_attr($form_values['pact_email_contacto']); ?>" required aria-required="true" autocomplete="email" />
                </div>

                <div class="pact-field">
                    <label for="pact_persona_contacto"><?php echo esc_html__('Persona contacto', 'publicacion-actividades'); ?></label>
                    <input id="pact_persona_contacto" name="pact_persona_contacto" type="text" value="<?php echo esc_attr($form_values['pact_persona_contacto']); ?>" autocomplete="name" />
                </div>

                <div class="pact-field">
                    <label for="pact_telefono_contacto"><?php echo esc_html__('Teléfono contacto', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_telefono_contacto" name="pact_telefono_contacto" type="tel" value="<?php echo esc_attr($form_values['pact_telefono_contacto']); ?>" required aria-required="true" autocomplete="tel" />
                </div>

                <div class="pact-field">
                    <label for="pact_descripcion"><?php echo esc_html__('Descripción', 'publicacion-actividades'); ?></label>
                    <textarea id="pact_descripcion" name="pact_descripcion" rows="6"><?php echo esc_textarea($form_values['pact_descripcion']); ?></textarea>
                </div>

                <div class="pact-field">
                    <label for="pact_images"><?php echo esc_html__('Imágenes (opcional)', 'publicacion-actividades'); ?></label>
                    <input id="pact_images" name="pact_images[]" type="file" accept="image/*" multiple />
                    <p class="pact-help">
                        <?php
                        /* translators: %s: maximum upload size */
                        printf(
                            esc_html__('Puedes adjuntar una o varias imágenes. Tamaño máximo por archivo: %s.', 'publicacion-actividades'),
                            esc_html(size_format((int) wp_max_upload_size()))
                        );
                        ?>
                    </p>
                </div>

                <div class="pact-actions">
                    <button type="submit"><?php echo esc_html__('Enviar solicitud', 'publicacion-actividades'); ?></button>
                </div>

                <p class="pact-help" id="pact_help">
                    <?php echo esc_html__('La solicitud se enviará por correo con todos los datos del formulario. Los campos marcados con * son obligatorios.', 'publicacion-actividades'); ?>
                </p>
            </form>
        <?php endif; ?>
    </div>
    <?php

    return (string) ob_get_clean();
}

function pact_handle_form_submission(): void {
    if (!is_user_logged_in()) {
        wp_die(esc_html__('No autorizado.', 'publicacion-actividades'), 403);
    }

    if (!pact_user_has_allowed_role()) {
        wp_die(esc_html__('No autorizado.', 'publicacion-actividades'), 403);
    }

    if (empty($_POST['pact_nonce']) || !wp_verify_nonce(sanitize_text_field((string) $_POST['pact_nonce']), 'pact_submit_form')) {
        wp_die(esc_html__('Solicitud inválida.', 'publicacion-actividades'), 400);
    }

    $options = pact_options_get();
    $allowed_activity_tag_ids = array_map('intval', (array) ($options['allowed_activity_tag_ids'] ?? $options['allowed_tag_ids'] ?? []));
    $allowed_dojo_tag_ids = array_map('intval', (array) ($options['allowed_dojo_tag_ids'] ?? []));
    $tipo_actividad_tag_id = isset($_POST['pact_tipo_actividad']) ? absint((string) wp_unslash($_POST['pact_tipo_actividad'])) : 0;
    $dojo_solicitante_tag_id = isset($_POST['pact_dojo_solicitante']) ? absint((string) wp_unslash($_POST['pact_dojo_solicitante'])) : 0;
    $fecha = isset($_POST['pact_fecha']) ? sanitize_text_field((string) wp_unslash($_POST['pact_fecha'])) : '';
    $lugar = isset($_POST['pact_lugar']) ? sanitize_text_field((string) wp_unslash($_POST['pact_lugar'])) : '';
    $hora = isset($_POST['pact_hora']) ? sanitize_text_field((string) wp_unslash($_POST['pact_hora'])) : '';
    $aportacion = isset($_POST['pact_aportacion']) ? sanitize_text_field((string) wp_unslash($_POST['pact_aportacion'])) : '';
    $email_contacto = isset($_POST['pact_email_contacto']) ? sanitize_email((string) wp_unslash($_POST['pact_email_contacto'])) : '';
    $persona_contacto = isset($_POST['pact_persona_contacto']) ? sanitize_text_field((string) wp_unslash($_POST['pact_persona_contacto'])) : '';
    $telefono_contacto = isset($_POST['pact_telefono_contacto']) ? sanitize_text_field((string) wp_unslash($_POST['pact_telefono_contacto'])) : '';
    $descripcion = isset($_POST['pact_descripcion']) ? sanitize_textarea_field((string) wp_unslash($_POST['pact_descripcion'])) : '';
    $tipo_actividad = '';
    $dojo_solicitante = '';

    $uploaded_images = pact_get_uploaded_files('pact_images');

    $errors = [];
    if ($tipo_actividad_tag_id <= 0) {
        $errors[] = __('El tipo de actividad es obligatorio.', 'publicacion-actividades');
    } elseif (!in_array($tipo_actividad_tag_id, $allowed_activity_tag_ids, true)) {
        $errors[] = __('El tipo de actividad seleccionado no está permitido.', 'publicacion-actividades');
    } else {
        $term = get_term($tipo_actividad_tag_id, 'post_tag');
        if (is_wp_error($term) || !$term || empty($term->term_id)) {
            $errors[] = __('El tipo de actividad seleccionado no es válido.', 'publicacion-actividades');
        } else {
            $tipo_actividad = (string) $term->name;
        }
    }

    if ($dojo_solicitante_tag_id <= 0) {
        $errors[] = __('El dojo solicitante es obligatorio.', 'publicacion-actividades');
    } elseif (!in_array($dojo_solicitante_tag_id, $allowed_dojo_tag_ids, true)) {
        $errors[] = __('El dojo solicitante seleccionado no está permitido.', 'publicacion-actividades');
    } else {
        $term = get_term($dojo_solicitante_tag_id, 'post_tag');
        if (is_wp_error($term) || !$term || empty($term->term_id)) {
            $errors[] = __('El dojo solicitante seleccionado no es válido.', 'publicacion-actividades');
        } else {
            $dojo_solicitante = (string) $term->name;
        }
    }
    if ($fecha === '') {
        $errors[] = __('La fecha es obligatoria.', 'publicacion-actividades');
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $errors[] = __('La fecha debe tener el formato AAAA-MM-DD.', 'publicacion-actividades');
    }
    if ($lugar === '') {
        $errors[] = __('El lugar es obligatorio.', 'publicacion-actividades');
    }
    if ($hora === '') {
        $errors[] = __('La hora es obligatoria.', 'publicacion-actividades');
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
        $errors[] = __('La hora debe tener el formato HH:MM.', 'publicacion-actividades');
    }
    if ($email_contacto === '' || !is_email($email_contacto)) {
        $errors[] = __('El email de contacto es obligatorio y debe ser válido.', 'publicacion-actividades');
    }
    if ($telefono_contacto === '') {
        $errors[] = __('El teléfono de contacto es obligatorio.', 'publicacion-actividades');
    }

    $max_images = (int) apply_filters('pact_max_images', 6);
    if ($max_images < 0) {
        $max_images = 0;
    }

    if (!empty($uploaded_images) && count($uploaded_images) > $max_images) {
        /* translators: %d: maximum number of images */
        $errors[] = sprintf(__('Puedes subir como máximo %d imágenes.', 'publicacion-actividades'), $max_images);
    }

    foreach ($uploaded_images as $file) {
        $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        $tmp_name = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        $name = isset($file['name']) ? (string) $file['name'] : '';
        $size = isset($file['size']) ? (int) $file['size'] : 0;

        if ($error === UPLOAD_ERR_NO_FILE || $name === '') {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = __('No se pudo subir una de las imágenes. Revisa el archivo e inténtalo de nuevo.', 'publicacion-actividades');
            continue;
        }

        if ($size <= 0 || $size > (int) wp_max_upload_size()) {
            $errors[] = __('Una de las imágenes supera el tamaño máximo permitido.', 'publicacion-actividades');
            continue;
        }

        if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
            $errors[] = __('Una de las imágenes no es válida.', 'publicacion-actividades');
            continue;
        }

        $filetype = wp_check_filetype_and_ext($tmp_name, $name);
        $allowed_mimes = pact_allowed_image_mimes();
        if (empty($filetype['type']) || !in_array((string) $filetype['type'], array_values($allowed_mimes), true)) {
            $errors[] = __('Solo se permiten imágenes JPG, PNG, GIF o WebP.', 'publicacion-actividades');
            continue;
        }

        if (@getimagesize($tmp_name) === false) {
            $errors[] = __('Una de las imágenes no parece ser un archivo de imagen válido.', 'publicacion-actividades');
            continue;
        }
    }

    $redirect = wp_get_referer();
    if (!$redirect) {
        $redirect = home_url('/');
    }

    if (!empty($errors)) {
        $form_state_key = pact_save_form_data_for_current_user([
            'pact_tipo_actividad' => (string) $tipo_actividad_tag_id,
            'pact_dojo_solicitante' => (string) $dojo_solicitante_tag_id,
            'pact_fecha' => $fecha,
            'pact_lugar' => $lugar,
            'pact_hora' => $hora,
            'pact_aportacion' => $aportacion,
            'pact_email_contacto' => $email_contacto,
            'pact_persona_contacto' => $persona_contacto,
            'pact_telefono_contacto' => $telefono_contacto,
            'pact_descripcion' => $descripcion,
        ]);

        $redirect = add_query_arg([
            'pact_status' => 'error',
            'pact_error' => rawurlencode(implode('|', $errors)),
            'pact_form_state' => $form_state_key,
        ], $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    $user = wp_get_current_user();

    $fecha_display = $fecha;
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    if ($dt instanceof DateTime) {
        $fecha_display = $dt->format('d/m/Y');
    }
    $post_title = trim($tipo_actividad . ' - ' . $fecha . ' ' . $hora . ' - ' . $lugar);
    if ($post_title === '') {
        $post_title = __('Solicitud de actividad', 'publicacion-actividades');
    }

    $attachments = [];
    $image_names = [];
    foreach ($uploaded_images as $file) {
        $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        $tmp_name = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        $name = isset($file['name']) ? (string) $file['name'] : '';

        if ($error === UPLOAD_ERR_NO_FILE || $name === '' || $tmp_name === '') {
            continue;
        }

        $safe_name = sanitize_file_name($name);
        $attachments[] = [
            'tmp_name' => $tmp_name,
            'name' => $safe_name,
        ];
        $image_names[] = $safe_name;
    }

    $submission = [
        'title' => $post_title,
        'tipo_actividad' => $tipo_actividad,
        'tipo_actividad_tag_id' => $tipo_actividad_tag_id,
        'dojo_solicitante' => $dojo_solicitante,
        'dojo_solicitante_tag_id' => $dojo_solicitante_tag_id,
        'fecha' => $fecha,
        'fecha_display' => $fecha_display,
        'hora' => $hora,
        'lugar' => $lugar,
        'aportacion' => $aportacion,
        'email_contacto' => $email_contacto,
        'persona_contacto' => $persona_contacto,
        'telefono_contacto' => $telefono_contacto,
        'descripcion' => $descripcion,
        'image_names' => $image_names,
    ];

    $sent = pact_send_submission_emails($submission, $user, $attachments);
    if (!$sent) {
        $redirect = add_query_arg([
            'pact_status' => 'error',
            'pact_error' => rawurlencode(__('No se pudo enviar el correo de la solicitud. Revisa la configuración de destinatarios e inténtalo de nuevo.', 'publicacion-actividades')),
        ], $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    $redirect = add_query_arg(['pact_status' => 'success'], $redirect);
    wp_safe_redirect($redirect);
    exit;
}

function pact_allowed_image_mimes(): array {
    return [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];
}

function pact_get_uploaded_files(string $input_name): array {
    if (empty($_FILES[$input_name]) || !is_array($_FILES[$input_name]) || empty($_FILES[$input_name]['name'])) {
        return [];
    }

    $names = (array) $_FILES[$input_name]['name'];
    $types = (array) ($_FILES[$input_name]['type'] ?? []);
    $tmp_names = (array) ($_FILES[$input_name]['tmp_name'] ?? []);
    $errors = (array) ($_FILES[$input_name]['error'] ?? []);
    $sizes = (array) ($_FILES[$input_name]['size'] ?? []);

    $out = [];
    foreach ($names as $i => $name) {
        $name = (string) $name;
        if ($name === '') {
            continue;
        }
        $out[] = [
            'name' => $name,
            'type' => (string) ($types[$i] ?? ''),
            'tmp_name' => (string) ($tmp_names[$i] ?? ''),
            'error' => (int) ($errors[$i] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($sizes[$i] ?? 0),
        ];
    }

    return $out;
}

function pact_get_empty_form_values(): array {
    return [
        'pact_tipo_actividad' => '',
        'pact_dojo_solicitante' => '',
        'pact_fecha' => '',
        'pact_lugar' => '',
        'pact_hora' => '',
        'pact_aportacion' => '',
        'pact_email_contacto' => '',
        'pact_persona_contacto' => '',
        'pact_telefono_contacto' => '',
        'pact_descripcion' => '',
    ];
}

function pact_get_form_data_transient_key(int $user_id): string {
    return 'pact_form_state_' . $user_id;
}

function pact_save_form_data_for_current_user(array $values): string {
    $user = wp_get_current_user();
    if (!$user instanceof WP_User || empty($user->ID)) {
        return '';
    }

    $sanitized = pact_get_empty_form_values();
    foreach ($sanitized as $key => $default) {
        if (!array_key_exists($key, $values)) {
            continue;
        }

        if ($key === 'pact_descripcion') {
            $sanitized[$key] = sanitize_textarea_field((string) $values[$key]);
        } elseif ($key === 'pact_email_contacto') {
            $sanitized[$key] = sanitize_email((string) $values[$key]);
        } else {
            $sanitized[$key] = sanitize_text_field((string) $values[$key]);
        }
    }

    set_transient(pact_get_form_data_transient_key((int) $user->ID), $sanitized, 15 * MINUTE_IN_SECONDS);

    return (string) $user->ID;
}

function pact_get_saved_form_data_from_request(): array {
    $user = wp_get_current_user();
    if (!$user instanceof WP_User || empty($user->ID)) {
        return pact_get_empty_form_values();
    }

    $requested_key = isset($_GET['pact_form_state']) ? sanitize_text_field((string) $_GET['pact_form_state']) : '';
    if ($requested_key !== (string) $user->ID) {
        return pact_get_empty_form_values();
    }

    $saved = get_transient(pact_get_form_data_transient_key((int) $user->ID));
    if (!is_array($saved)) {
        return pact_get_empty_form_values();
    }

    return array_merge(pact_get_empty_form_values(), $saved);
}

function pact_clear_saved_form_data_for_current_user(): void {
    $user = wp_get_current_user();
    if ($user instanceof WP_User && !empty($user->ID)) {
        delete_transient(pact_get_form_data_transient_key((int) $user->ID));
    }
}
