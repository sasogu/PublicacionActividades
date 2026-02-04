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

    if (isset($_GET['pact_status'])) {
        $status = sanitize_text_field((string) $_GET['pact_status']);
        if ($status === 'success') {
            $success = true;
        } elseif ($status === 'error') {
            $raw = isset($_GET['pact_error']) ? (string) $_GET['pact_error'] : '';
            $errors = array_filter(array_map('sanitize_text_field', explode('|', $raw)));
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
                            <option value="<?php echo esc_attr((string) $tag->term_id); ?>"><?php echo esc_html($tag->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pact-field">
                    <label for="pact_dojo_solicitante"><?php echo esc_html__('Dojo solicitante', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <select id="pact_dojo_solicitante" name="pact_dojo_solicitante" required aria-required="true">
                        <option value=""><?php echo esc_html__('Selecciona un dojo…', 'publicacion-actividades'); ?></option>
                        <?php foreach ($dojo_tags as $tag) : ?>
                            <option value="<?php echo esc_attr((string) $tag->term_id); ?>"><?php echo esc_html($tag->name); ?></option>
                        <?php endforeach; ?>
                    </select>
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
                    <label for="pact_aportacion"><?php echo esc_html__('Aportación', 'publicacion-actividades'); ?></label>
                    <input id="pact_aportacion" name="pact_aportacion" type="text" placeholder="" />
                </div>

                <div class="pact-field">
                    <label for="pact_email_contacto"><?php echo esc_html__('Email contacto', 'publicacion-actividades'); ?> <span aria-hidden="true">*</span></label>
                    <input id="pact_email_contacto" name="pact_email_contacto" type="email" required aria-required="true" autocomplete="email" />
                </div>

                <div class="pact-field">
                    <label for="pact_persona_contacto"><?php echo esc_html__('Persona contacto', 'publicacion-actividades'); ?></label>
                    <input id="pact_persona_contacto" name="pact_persona_contacto" type="text" autocomplete="name" />
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
    $allowed_activity_tag_ids = array_map('intval', (array) ($options['allowed_activity_tag_ids'] ?? $options['allowed_tag_ids'] ?? []));
    $allowed_dojo_tag_ids = array_map('intval', (array) ($options['allowed_dojo_tag_ids'] ?? []));
    $default_category_id = isset($options['default_category_id']) ? (int) $options['default_category_id'] : 0;

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

    $fecha_display = $fecha;
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    if ($dt instanceof DateTime) {
        $fecha_display = $dt->format('d/m/Y');
    }

    // Generar contenido como bloques de Gutenberg.
    $heading_block = static function (string $text): string {
        return "<!-- wp:heading {\"level\":2} -->\n<h2>" . esc_html($text) . "</h2>\n<!-- /wp:heading -->\n";
    };
    $list_block = static function (array $items_html): string {
        return "<!-- wp:list -->\n<ul>\n" . implode("\n", $items_html) . "\n</ul>\n<!-- /wp:list -->\n";
    };
    $paragraph_block = static function (string $text): string {
        return "<!-- wp:paragraph -->\n<p>" . esc_html($text) . "</p>\n<!-- /wp:paragraph -->\n";
    };

    $content_blocks = '';

    $content_blocks .= $heading_block(__('Datos de la actividad', 'publicacion-actividades'));
    $activity_items = [
        '<li><strong>' . esc_html__('Fecha:', 'publicacion-actividades') . '</strong> ' . esc_html($fecha_display) . '</li>',
        '<li><strong>' . esc_html__('Hora:', 'publicacion-actividades') . '</strong> ' . esc_html($hora) . '</li>',
        '<li><strong>' . esc_html__('Lugar:', 'publicacion-actividades') . '</strong> ' . esc_html($lugar) . '</li>',
    ];

    if ($aportacion !== '') {
        $activity_items[] = '<li><strong>' . esc_html__('Aportación:', 'publicacion-actividades') . '</strong> ' . esc_html($aportacion) . '</li>';
    }

    $content_blocks .= $list_block($activity_items);

    $content_blocks .= $heading_block(__('Contacto', 'publicacion-actividades'));
    $contact_items = [
        '<li><strong>' . esc_html__('Email:', 'publicacion-actividades') . '</strong> ' . esc_html($email_contacto) . '</li>',
        '<li><strong>' . esc_html__('Teléfono:', 'publicacion-actividades') . '</strong> ' . esc_html($telefono_contacto) . '</li>',
    ];

    if ($persona_contacto !== '') {
        array_unshift($contact_items, '<li><strong>' . esc_html__('Persona:', 'publicacion-actividades') . '</strong> ' . esc_html($persona_contacto) . '</li>');
    }

    $content_blocks .= $list_block($contact_items);

    if ($descripcion !== '') {
        $content_blocks .= $heading_block(__('Descripción', 'publicacion-actividades'));
        $parts = preg_split("/\R{2,}/", trim($descripcion)) ?: [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $content_blocks .= $paragraph_block($p);
            }
        }
    }

    $post_id = wp_insert_post([
        'post_type' => 'post',
        'post_status' => 'pending',
        'post_title' => $post_title,
        'post_content' => $content_blocks,
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

    // Asignar tags: tipo actividad + dojo solicitante.
    $post_tags = array_values(array_unique(array_filter([
        (int) $tipo_actividad_tag_id,
        (int) $dojo_solicitante_tag_id,
    ])));
    if (!empty($post_tags)) {
        wp_set_post_terms((int) $post_id, $post_tags, 'post_tag', false);
    }

    // Marcar el post como creado vía plugin (para emails).
    update_post_meta((int) $post_id, '_pact_submission', [
        'user_id' => (int) $user->ID,
        'submitted_at' => time(),
    ]);

    // Guardar datos como meta (útil para administración/exports).
    update_post_meta((int) $post_id, '_pact_tipo_actividad', $tipo_actividad);
    update_post_meta((int) $post_id, '_pact_tipo_actividad_tag_id', $tipo_actividad_tag_id);
    update_post_meta((int) $post_id, '_pact_dojo_solicitante', $dojo_solicitante);
    update_post_meta((int) $post_id, '_pact_dojo_solicitante_tag_id', $dojo_solicitante_tag_id);
    update_post_meta((int) $post_id, '_pact_fecha', $fecha);
    update_post_meta((int) $post_id, '_pact_lugar', $lugar);
    update_post_meta((int) $post_id, '_pact_hora', $hora);
    update_post_meta((int) $post_id, '_pact_aportacion', $aportacion);
    update_post_meta((int) $post_id, '_pact_email_contacto', $email_contacto);
    update_post_meta((int) $post_id, '_pact_persona_contacto', $persona_contacto);
    update_post_meta((int) $post_id, '_pact_telefono_contacto', $telefono_contacto);
    update_post_meta((int) $post_id, '_pact_descripcion', $descripcion);

    // Procesar imágenes (opcional): crear adjuntos y añadirlos al contenido como bloques.
    $image_ids = [];
    if (!empty($uploaded_images)) {
        $result = pact_upload_images_as_attachments((int) $post_id, $uploaded_images);
        if (is_wp_error($result)) {
            // Si falla la subida, eliminar el post para no dejar solicitudes incompletas.
            wp_delete_post((int) $post_id, true);
            $redirect = add_query_arg([
                'pact_status' => 'error',
                'pact_error' => rawurlencode($result->get_error_message()),
            ], $redirect);
            wp_safe_redirect($redirect);
            exit;
        }

        $image_ids = (array) $result;
        if (!empty($image_ids)) {
            update_post_meta((int) $post_id, '_pact_image_ids', $image_ids);

            if (function_exists('has_post_thumbnail') && !has_post_thumbnail((int) $post_id)) {
                set_post_thumbnail((int) $post_id, (int) $image_ids[0]);
            }

            $content_blocks .= $heading_block(__('Imágenes', 'publicacion-actividades'));

            if (count($image_ids) > 1) {
                $gallery_ids = array_values(array_map('intval', $image_ids));
                $content_blocks .= "<!-- wp:gallery {\"linkTo\":\"none\",\"ids\":" . wp_json_encode($gallery_ids) . "} -->\n";
                $content_blocks .= '<figure class="wp-block-gallery has-nested-images columns-default is-cropped">' . "\n";

                foreach ($image_ids as $attach_id) {
                    $attach_id = (int) $attach_id;
                    $url = wp_get_attachment_url($attach_id);
                    if (!$url) {
                        continue;
                    }

                    $content_blocks .= "<!-- wp:image {\"id\":" . $attach_id . ",\"sizeSlug\":\"large\",\"linkDestination\":\"none\"} -->\n";
                    $content_blocks .= '<figure class="wp-block-image size-large"><img src="' . esc_url($url) . '" alt="" class="wp-image-' . esc_attr((string) $attach_id) . '" /></figure>' . "\n";
                    $content_blocks .= "<!-- /wp:image -->\n";
                }

                $content_blocks .= "</figure>\n";
                $content_blocks .= "<!-- /wp:gallery -->\n";
            } else {
                $attach_id = (int) $image_ids[0];
                $url = wp_get_attachment_url($attach_id);
                if ($url) {
                    $content_blocks .= "<!-- wp:image {\"id\":" . $attach_id . ",\"sizeSlug\":\"large\",\"linkDestination\":\"none\"} -->\n";
                    $content_blocks .= '<figure class="wp-block-image size-large"><img src="' . esc_url($url) . '" alt="" class="wp-image-' . esc_attr((string) $attach_id) . '" /></figure>' . "\n";
                    $content_blocks .= "<!-- /wp:image -->\n";
                }
            }

            wp_update_post([
                'ID' => (int) $post_id,
                'post_content' => $content_blocks,
            ]);
        }
    }

    // Aviso por email a los correos configurados al enviar.
    pact_send_submission_notification_email((int) $post_id, $user);

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

/**
 * @param array<int, array{name:string,type:string,tmp_name:string,error:int,size:int}> $files
 * @return array<int,int>|WP_Error
 */
function pact_upload_images_as_attachments(int $post_id, array $files) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $allowed_mimes = pact_allowed_image_mimes();
    $attachment_ids = [];

    foreach ($files as $file) {
        $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE || (string) ($file['name'] ?? '') === '') {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return new WP_Error('pact_upload_error', __('No se pudo subir una de las imágenes.', 'publicacion-actividades'));
        }

        $upload = wp_handle_upload(
            $file,
            [
                'test_form' => false,
                'mimes' => $allowed_mimes,
            ]
        );

        if (!is_array($upload) || !empty($upload['error'])) {
            return new WP_Error('pact_upload_error', __('Error al procesar una de las imágenes.', 'publicacion-actividades'));
        }

        $file_path = (string) $upload['file'];
        $mime_type = (string) ($upload['type'] ?? '');
        $url = (string) ($upload['url'] ?? '');

        $attachment = [
            'post_mime_type' => $mime_type,
            'post_title' => sanitize_file_name(pathinfo($file_path, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
        if (is_wp_error($attach_id) || !$attach_id) {
            return new WP_Error('pact_upload_error', __('No se pudo adjuntar una de las imágenes.', 'publicacion-actividades'));
        }

        $attach_data = wp_generate_attachment_metadata((int) $attach_id, $file_path);
        if (is_array($attach_data)) {
            wp_update_attachment_metadata((int) $attach_id, $attach_data);
        }

        // Guardar la URL como fallback (no es imprescindible, pero útil si algún tema rompe urls).
        if ($url !== '') {
            update_post_meta((int) $attach_id, '_pact_upload_url', esc_url_raw($url));
        }

        $attachment_ids[] = (int) $attach_id;
    }

    return $attachment_ids;
}
