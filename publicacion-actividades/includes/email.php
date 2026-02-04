<?php

if (!defined('ABSPATH')) {
    exit;
}

function pact_send_submission_notification_email(int $post_id, WP_User $user): void {
    $options = pact_options_get();

    $raw = (string) ($options['submission_notify_recipients'] ?? '');
    if ($raw === '') {
        return;
    }

    $recipients = [];
    $parts = array_map('trim', explode(',', $raw));
    foreach ($parts as $email) {
        if ($email !== '' && is_email($email)) {
            $recipients[] = $email;
        }
    }

    $recipients = array_values(array_unique(array_filter($recipients)));
    if (empty($recipients)) {
        return;
    }

    $post_title = get_the_title($post_id);
    $edit_url = admin_url('post.php?post=' . (int) $post_id . '&action=edit');

    $subject_tpl = (string) ($options['submission_email_subject'] ?? '');
    $body_tpl = (string) ($options['submission_email_body'] ?? '');

    $replacements = [
        '{display_name}' => (string) $user->display_name,
        '{post_title}' => (string) $post_title,
        '{edit_url}' => (string) $edit_url,
    ];

    $subject = strtr($subject_tpl, $replacements);
    $body = strtr($body_tpl, $replacements);

    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    wp_mail($recipients, $subject, $body, $headers);
}

function pact_maybe_send_publish_email(string $new_status, string $old_status, WP_Post $post): void {
    // Solo cuando se publique.
    if ($new_status !== 'publish' || $old_status === 'publish') {
        return;
    }

    // Solo posts normales.
    if ($post->post_type !== 'post') {
        return;
    }

    // Solo posts creados por este formulario.
    $submission = get_post_meta($post->ID, '_pact_submission', true);
    if (!is_array($submission) || empty($submission['user_id'])) {
        return;
    }

    $options = pact_options_get();

    $recipients = [];

    // Email al solicitante.
    if (!empty($options['email_to_submitter'])) {
        $user = get_user_by('id', (int) $submission['user_id']);
        if ($user instanceof WP_User && !empty($user->user_email)) {
            $recipients[] = $user->user_email;
        }
    }

    // Destinatarios extra.
    $extra = (string) ($options['email_extra_recipients'] ?? '');
    if ($extra !== '') {
        $parts = array_map('trim', explode(',', $extra));
        foreach ($parts as $email) {
            if ($email !== '' && is_email($email)) {
                $recipients[] = $email;
            }
        }
    }

    $recipients = array_values(array_unique(array_filter($recipients)));
    if (empty($recipients)) {
        return;
    }

    $user = null;
    if (!empty($submission['user_id'])) {
        $user = get_user_by('id', (int) $submission['user_id']);
    }

    $display_name = ($user instanceof WP_User) ? $user->display_name : '';
    $post_title = get_the_title($post);
    $post_url = get_permalink($post);

    $subject_tpl = (string) ($options['email_subject'] ?? '');
    $body_tpl = (string) ($options['email_body'] ?? '');

    $replacements = [
        '{display_name}' => $display_name,
        '{post_title}' => $post_title,
        '{post_url}' => $post_url,
    ];

    $subject = strtr($subject_tpl, $replacements);
    $body = strtr($body_tpl, $replacements);

    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    wp_mail($recipients, $subject, $body, $headers);
}
