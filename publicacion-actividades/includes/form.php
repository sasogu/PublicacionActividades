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
    $allowed_tag_ids = (array) ($options['allowed_tag_ids'] ?? []);

    // Carga CSS del formulario.
    wp_enqueue_style('pact-form');

    $errors = [];
    $success = false;

    if (isset($_GET['pact_status'])) {
        $status = sanitize_text_field((string) $_GET['pact_status']);
        if ($status === 'success') {
            $success = true;
        } elseif ($status === 'error') {
            $raw = isset($_GET['pact_error']) ? (string) $_GET['pact_error'] : '';
            $errors = array_filter(array_map('sanitize_text_field', explode('|', $raw)));
        }
    }

    $tags = [];
    if (!empty($allowed_tag_ids)) {
        $tags = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'include' => array_map('intval', $allowed_tag_ids),
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        if (is_wp_error($tags)) {
            $tags = [];
        }
    }

    ob_start();
    ?>
    <div class="pact-form">
        <?php if ($success) : ?>
            <div class="pact-notice pact-notice--success" role="status" aria-live="polite">
                <?php echo esc_html__('Solicitud enviada. Queda pendiente de revisión.', 'publicacion-actividades'); ?>
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

        <?php if (empty($tags)) : ?>
            <div class="pact-notice pact-notice--warning" role="status" aria-live="polite">
                <?php echo esc_html__('Este formulario no está configurado: faltan etiquetas permitidas. Contacta con un administrador.', 'publicacion-actividades'); ?>
            </div>
        <?php else : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" novalidate>
                <input type="hidden" name="action" value="pact_submit" />
                <?php wp_nonce_field('pact_submit_form', 'pact_nonce'); ?>

                <div class="pact-field">
                    <label for="pact_tipo_actividad"><?php echo esc_html__('Tipo actividad', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_tipo_actividad" name="pact_tipo_actividad" type="text" required aria-required="true" autocomplete="off" />
                </div>

                <div class="pact-field">
                    <label for="pact_fecha"><?php echo esc_html__('Fecha', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_fecha" name="pact_fecha" type="date" required aria-required="true" />
                </div>

                <div class="pact-field">
                    <label for="pact_lugar"><?php echo esc_html__('Lugar', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_lugar" name="pact_lugar" type="text" required aria-required="true" />
                </div>

                <div class="pact-field">
                    <label for="pact_hora"><?php echo esc_html__('Hora', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_hora" name="pact_hora" type="time" required aria-required="true" />
                </div>

                <div class="pact-field">
                    <label for="pact_email_contacto"><?php echo esc_html__('Email contacto', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_email_contacto" name="pact_email_contacto" type="email" required aria-required="true" autocomplete="email" />
                </div>

                <div class="pact-field">
                    <label for="pact_persona_contacto"><?php echo esc_html__('Persona contacto', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_persona_contacto" name="pact_persona_contacto" type="text" required aria-required="true" autocomplete="name" />
                </div>

                <div class="pact-field">
                    <label for="pact_telefono_contacto"><?php echo esc_html__('Teléfono contacto', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_telefono_contacto" name="pact_telefono_contacto" type="tel" required aria-required="true" autocomplete="tel" />
                </div>

                <div class="pact-field">
                    <label for="pact_descripcion"><?php echo esc_html__('Descripción', 'publicacion-actividades'); ?></label>
                    <textarea id="pact_descripcion" name="pact_descripcion" rows="6"></textarea>
                </div>

                <div class="pact-field">
                    <label for="pact_tag_id"><?php echo esc_html__('Etiqueta (obligatoria)', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <select id="pact_tag_id" name="pact_tag_id" required aria-required="true">
                        <option value=""><?php echo esc_html__('Selecciona una etiqueta…', 'publicacion-actividades'); ?></option>
                        <?php foreach ($tags as $tag) : ?>
                            <option value="<?php echo esc_attr((string) $tag->term_id); ?>"><?php echo esc_html($tag->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pact-actions">
                    <button type="submit"><?php echo esc_html__('Enviar solicitud', 'publicacion-actividades'); ?></button>
                </div>

                <p class="pact-help" id="pact_help">
                    <?php echo esc_html__('La solicitud se creará como “Pendiente” para revisión. Los campos marcados con * son obligatorios.', 'publicacion-actividades'); ?>
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
    $allowed_tag_ids = array_map('intval', (array) ($options['allowed_tag_ids'] ?? []));
    $default_category_id = isset($options['default_category_id']) ? (int) $options['default_category_id'] : 0;

    $tipo_actividad = isset($_POST['pact_tipo_actividad']) ? sanitize_text_field((string) wp_unslash($_POST['pact_tipo_actividad'])) : '';
    $fecha = isset($_POST['pact_fecha']) ? sanitize_text_field((string) wp_unslash($_POST['pact_fecha'])) : '';
    $lugar = isset($_POST['pact_lugar']) ? sanitize_text_field((string) wp_unslash($_POST['pact_lugar'])) : '';
    $hora = isset($_POST['pact_hora']) ? sanitize_text_field((string) wp_unslash($_POST['pact_hora'])) : '';
    $email_contacto = isset($_POST['pact_email_contacto']) ? sanitize_email((string) wp_unslash($_POST['pact_email_contacto'])) : '';
    $persona_contacto = isset($_POST['pact_persona_contacto']) ? sanitize_text_field((string) wp_unslash($_POST['pact_persona_contacto'])) : '';
    $telefono_contacto = isset($_POST['pact_telefono_contacto']) ? sanitize_text_field((string) wp_unslash($_POST['pact_telefono_contacto'])) : '';
    $descripcion = isset($_POST['pact_descripcion']) ? sanitize_textarea_field((string) wp_unslash($_POST['pact_descripcion'])) : '';
    $tag_id = isset($_POST['pact_tag_id']) ? absint((string) wp_unslash($_POST['pact_tag_id'])) : 0;

    $errors = [];
    if ($tipo_actividad === '') {
        $errors[] = __('El tipo de actividad es obligatorio.', 'publicacion-actividades');
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
    if ($persona_contacto === '') {
        $errors[] = __('La persona de contacto es obligatoria.', 'publicacion-actividades');
    }
    if ($telefono_contacto === '') {
        $errors[] = __('El teléfono de contacto es obligatorio.', 'publicacion-actividades');
    }
    if ($tag_id <= 0) {
        $errors[] = __('Debes seleccionar una etiqueta.', 'publicacion-actividades');
    } elseif (!in_array($tag_id, $allowed_tag_ids, true)) {
        $errors[] = __('La etiqueta seleccionada no está permitida.', 'publicacion-actividades');
    }

    $redirect = wp_get_referer();
    if (!$redirect) {
        $redirect = home_url('/');
    }

    if (!empty($errors)) {
        $redirect = add_query_arg([
            'pact_status' => 'error',
            'pact_error' => rawurlencode(implode('|', $errors)),
        ], $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    $user = wp_get_current_user();

    $post_title = trim($tipo_actividad . ' - ' . $fecha . ' ' . $hora . ' - ' . $lugar);
    if ($post_title === '') {
        $post_title = __('Solicitud de actividad', 'publicacion-actividades');
    }

    $content_html = '<div class="pact-actividad">';
    $content_html .= '<h2>' . esc_html__('Datos de la actividad', 'publicacion-actividades') . '</h2>';
    $content_html .= '<ul>';
    $content_html .= '<li><strong>' . esc_html__('Tipo actividad:', 'publicacion-actividades') . '</strong> ' . esc_html($tipo_actividad) . '</li>';
    $content_html .= '<li><strong>' . esc_html__('Fecha:', 'publicacion-actividades') . '</strong> ' . esc_html($fecha) . '</li>';
    $content_html .= '<li><strong>' . esc_html__('Hora:', 'publicacion-actividades') . '</strong> ' . esc_html($hora) . '</li>';
    $content_html .= '<li><strong>' . esc_html__('Lugar:', 'publicacion-actividades') . '</strong> ' . esc_html($lugar) . '</li>';
    $content_html .= '</ul>';

    $content_html .= '<h2>' . esc_html__('Contacto', 'publicacion-actividades') . '</h2>';
    $content_html .= '<ul>';
    $content_html .= '<li><strong>' . esc_html__('Persona:', 'publicacion-actividades') . '</strong> ' . esc_html($persona_contacto) . '</li>';
    $content_html .= '<li><strong>' . esc_html__('Email:', 'publicacion-actividades') . '</strong> ' . esc_html($email_contacto) . '</li>';
    $content_html .= '<li><strong>' . esc_html__('Teléfono:', 'publicacion-actividades') . '</strong> ' . esc_html($telefono_contacto) . '</li>';
    $content_html .= '</ul>';

    if ($descripcion !== '') {
        $content_html .= '<h2>' . esc_html__('Descripción', 'publicacion-actividades') . '</h2>';
        $content_html .= wpautop(esc_html($descripcion));
    }

    $content_html .= '</div>';

    $post_id = wp_insert_post([
        'post_type' => 'post',
        'post_status' => 'pending',
        'post_title' => $post_title,
        'post_content' => $content_html,
        'post_excerpt' => '',
        'post_author' => (int) $user->ID,
        'post_category' => ($default_category_id > 0) ? [$default_category_id] : [],
    ], true);

    if (is_wp_error($post_id)) {
        $redirect = add_query_arg([
            'pact_status' => 'error',
            'pact_error' => rawurlencode(__('No se pudo crear la solicitud. Inténtalo más tarde.', 'publicacion-actividades')),
        ], $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    // Asignar etiqueta.
    wp_set_post_terms((int) $post_id, [$tag_id], 'post_tag', false);

    // Marcar el post como creado vía plugin (para emails).
    update_post_meta((int) $post_id, '_pact_submission', [
        'user_id' => (int) $user->ID,
        'submitted_at' => time(),
    ]);

    // Guardar datos como meta (útil para administración/exports).
    update_post_meta((int) $post_id, '_pact_tipo_actividad', $tipo_actividad);
    update_post_meta((int) $post_id, '_pact_fecha', $fecha);
    update_post_meta((int) $post_id, '_pact_lugar', $lugar);
    update_post_meta((int) $post_id, '_pact_hora', $hora);
    update_post_meta((int) $post_id, '_pact_email_contacto', $email_contacto);
    update_post_meta((int) $post_id, '_pact_persona_contacto', $persona_contacto);
    update_post_meta((int) $post_id, '_pact_telefono_contacto', $telefono_contacto);
    update_post_meta((int) $post_id, '_pact_descripcion', $descripcion);

    // Aviso por email a los correos configurados al enviar.
    pact_send_submission_notification_email((int) $post_id, $user);

    $redirect = add_query_arg(['pact_status' => 'success'], $redirect);
    wp_safe_redirect($redirect);
    exit;
}
