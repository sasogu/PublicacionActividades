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
                    <label for="pact_title"><?php echo esc_html__('Título', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_title" name="pact_title" type="text" required aria-required="true" autocomplete="off" />
                </div>

                <div class="pact-field">
                    <label for="pact_content"><?php echo esc_html__('Contenido', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <textarea id="pact_content" name="pact_content" rows="8" required aria-required="true"></textarea>
                </div>

                <div class="pact-field">
                    <label for="pact_excerpt"><?php echo esc_html__('Extracto (opcional)', 'publicacion-actividades'); ?></label>
                    <textarea id="pact_excerpt" name="pact_excerpt" rows="3"></textarea>
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
                    <?php echo esc_html__('La solicitud se creará como “Pendiente” para revisión.', 'publicacion-actividades'); ?>
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

    $title = isset($_POST['pact_title']) ? sanitize_text_field((string) $_POST['pact_title']) : '';
    $content = isset($_POST['pact_content']) ? wp_kses_post((string) $_POST['pact_content']) : '';
    $excerpt = isset($_POST['pact_excerpt']) ? sanitize_text_field((string) $_POST['pact_excerpt']) : '';
    $tag_id = isset($_POST['pact_tag_id']) ? absint((string) $_POST['pact_tag_id']) : 0;

    $errors = [];
    if ($title === '') {
        $errors[] = __('El título es obligatorio.', 'publicacion-actividades');
    }
    if (trim(wp_strip_all_tags($content)) === '') {
        $errors[] = __('El contenido es obligatorio.', 'publicacion-actividades');
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

    $post_id = wp_insert_post([
        'post_type' => 'post',
        'post_status' => 'pending',
        'post_title' => $title,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_author' => (int) $user->ID,
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

    // Aviso por email a los correos configurados al enviar.
    pact_send_submission_notification_email((int) $post_id, $user);

    $redirect = add_query_arg(['pact_status' => 'success'], $redirect);
    wp_safe_redirect($redirect);
    exit;
}
