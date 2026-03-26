<?php

if (!defined('ABSPATH')) {
    exit;
}

function pact_parse_recipient_list(string $raw): array {
    if ($raw === '') {
        return [];
    }

    $recipients = [];
    $parts = array_map('trim', explode(',', $raw));
    foreach ($parts as $email) {
        if ($email !== '' && is_email($email)) {
            $recipients[] = $email;
        }
    }

    return array_values(array_unique(array_filter($recipients)));
}

function pact_build_submission_summary(array $submission): string {
    $lines = [
        __('Tipo de actividad:', 'publicacion-actividades') . ' ' . (string) ($submission['tipo_actividad'] ?? ''),
        __('Dojo solicitante:', 'publicacion-actividades') . ' ' . (string) ($submission['dojo_solicitante'] ?? ''),
        __('Fecha:', 'publicacion-actividades') . ' ' . (string) ($submission['fecha_display'] ?? $submission['fecha'] ?? ''),
        __('Hora:', 'publicacion-actividades') . ' ' . (string) ($submission['hora'] ?? ''),
        __('Lugar:', 'publicacion-actividades') . ' ' . (string) ($submission['lugar'] ?? ''),
        __('Email de contacto:', 'publicacion-actividades') . ' ' . (string) ($submission['email_contacto'] ?? ''),
        __('Teléfono de contacto:', 'publicacion-actividades') . ' ' . (string) ($submission['telefono_contacto'] ?? ''),
    ];

    if (!empty($submission['persona_contacto'])) {
        $lines[] = __('Persona de contacto:', 'publicacion-actividades') . ' ' . (string) $submission['persona_contacto'];
    }

    if (!empty($submission['aportacion'])) {
        $lines[] = __('Aportación:', 'publicacion-actividades') . ' ' . (string) $submission['aportacion'];
    }

    if (!empty($submission['descripcion'])) {
        $lines[] = '';
        $lines[] = __('Descripción:', 'publicacion-actividades');
        $lines[] = (string) $submission['descripcion'];
    }

    if (!empty($submission['image_names']) && is_array($submission['image_names'])) {
        $lines[] = '';
        $lines[] = __('Imágenes adjuntas:', 'publicacion-actividades') . ' ' . implode(', ', array_map('sanitize_file_name', $submission['image_names']));
    }

    return trim(implode("\n", $lines));
}

function pact_inject_submission_summary(string $body_tpl, array $replacements): string {
    $body = strtr($body_tpl, $replacements);
    $summary = (string) ($replacements['{submission_summary}'] ?? '');

    if ($summary === '') {
        return $body;
    }

    if (strpos($body_tpl, '{submission_summary}') !== false) {
        return $body;
    }

    $trimmed = rtrim($body);
    if ($trimmed !== '') {
        $trimmed .= "\n\n";
    }

    return $trimmed . $summary . "\n";
}

function pact_prepare_email_attachments(array $files): array {
    $prepared = [];
    $upload_dir = wp_upload_dir();
    $base_dir = '';

    if (empty($upload_dir['error']) && !empty($upload_dir['basedir'])) {
        $base_dir = trailingslashit((string) $upload_dir['basedir']) . 'pact-temp';
        if (!wp_mkdir_p($base_dir)) {
            $base_dir = '';
        }
    }

    if ($base_dir === '') {
        $base_dir = untrailingslashit(get_temp_dir());
    }

    foreach ($files as $file) {
        $tmp_name = (string) ($file['tmp_name'] ?? '');
        $name = sanitize_file_name((string) ($file['name'] ?? ''));

        if ($tmp_name === '' || $name === '' || !file_exists($tmp_name)) {
            continue;
        }

        $target = trailingslashit($base_dir) . wp_unique_filename($base_dir, 'pact-' . $name);
        if (!@copy($tmp_name, $target)) {
            continue;
        }

        $prepared[] = $target;
    }

    return $prepared;
}

function pact_cleanup_email_attachments(array $paths): void {
    foreach ($paths as $path) {
        $path = (string) $path;
        if ($path !== '' && file_exists($path)) {
            @unlink($path);
        }
    }
}

function pact_send_submission_emails(array $submission, WP_User $user, array $attachments = []): bool {
    $options = pact_options_get();

    $primary_recipients = pact_parse_recipient_list((string) ($options['submission_notify_recipients'] ?? ''));
    $extra_recipients = pact_parse_recipient_list((string) ($options['email_extra_recipients'] ?? ''));

    $main_recipients = array_values(array_unique(array_merge($primary_recipients, $extra_recipients)));
    $submitter_recipients = [];
    if (!empty($options['email_to_submitter']) && !empty($user->user_email) && is_email($user->user_email)) {
        $submitter_recipients[] = (string) $user->user_email;
    }

    if (empty($main_recipients) && empty($submitter_recipients)) {
        return false;
    }

    $replacements = [
        '{display_name}' => (string) $user->display_name,
        '{user_email}' => (string) $user->user_email,
        '{post_title}' => (string) ($submission['title'] ?? ''),
        '{submission_summary}' => pact_build_submission_summary($submission),
        '{tipo_actividad}' => (string) ($submission['tipo_actividad'] ?? ''),
        '{dojo_solicitante}' => (string) ($submission['dojo_solicitante'] ?? ''),
        '{fecha}' => (string) ($submission['fecha_display'] ?? $submission['fecha'] ?? ''),
        '{hora}' => (string) ($submission['hora'] ?? ''),
        '{lugar}' => (string) ($submission['lugar'] ?? ''),
        '{aportacion}' => (string) ($submission['aportacion'] ?? ''),
        '{email_contacto}' => (string) ($submission['email_contacto'] ?? ''),
        '{persona_contacto}' => (string) ($submission['persona_contacto'] ?? ''),
        '{telefono_contacto}' => (string) ($submission['telefono_contacto'] ?? ''),
        '{descripcion}' => (string) ($submission['descripcion'] ?? ''),
    ];

    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    $sent = true;
    $prepared_attachments = pact_prepare_email_attachments($attachments);

    if (!empty($main_recipients)) {
        $subject_tpl = (string) ($options['submission_email_subject'] ?? '');
        $body_tpl = (string) ($options['submission_email_body'] ?? '');

        $subject = strtr($subject_tpl, $replacements);
        $body = pact_inject_submission_summary($body_tpl, $replacements);

        $result = wp_mail($main_recipients, $subject, $body, $headers, $prepared_attachments);
        if (!$result && !empty($prepared_attachments)) {
            $result = wp_mail($main_recipients, $subject, $body, $headers);
        }
        $sent = $sent && (bool) $result;
    }

    if (!empty($submitter_recipients)) {
        $subject_tpl = (string) ($options['email_subject'] ?? '');
        $body_tpl = (string) ($options['email_body'] ?? '');

        $subject = strtr($subject_tpl, $replacements);
        $body = pact_inject_submission_summary($body_tpl, $replacements);

        $result = wp_mail($submitter_recipients, $subject, $body, $headers, $prepared_attachments);
        if (!$result && !empty($prepared_attachments)) {
            $result = wp_mail($submitter_recipients, $subject, $body, $headers);
        }
        $sent = $sent && (bool) $result;
    }

    pact_cleanup_email_attachments($prepared_attachments);

    return $sent;
}
