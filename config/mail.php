<?php
/**
 * MeetingRoom Manager - Mail Configuration (PHPMailer)
 * Usa configurações do banco de dados via Settings model
 */

require_once __DIR__ . '/../models/Settings.php';

/**
 * Envia email usando PHPMailer com configurações do banco
 */
function sendMail(string $to, string $subject, string $body, array $cc = []): bool
{
    $settings = Settings::instance();

    // Verificar se email está habilitado
    if ($settings->get('mail_enabled') !== '1') {
        error_log("Email desabilitado nas configurações do sistema.");
        return false;
    }

    $host       = $settings->get('mail_host');
    $port       = (int) $settings->get('mail_port');
    $username   = $settings->get('mail_username');
    $password   = $settings->get('mail_password');
    $fromAddr   = $settings->get('mail_from_address') ?: $username;
    $fromName   = $settings->get('mail_from_name');
    $encryption = $settings->get('mail_encryption');

    if (empty($host) || empty($username)) {
        error_log("Configurações de email incompletas.");
        return false;
    }

    require_once __DIR__ . '/../vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = $encryption;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = 0;

        $mail->setFrom($fromAddr, $fromName);
        $mail->addAddress($to);

        foreach ($cc as $email) {
            $mail->addCC($email);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar email: " . $mail->ErrorInfo);
        return false;
    }
}
