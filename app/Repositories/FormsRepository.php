<?php

namespace Ceremonies\Repositories;

use Ceremonies\Models\FormSubmission;
use Ceremonies\Services\Helpers;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class FormsRepository
{

    public function checkRecaptcha($token): bool
    {
        // Data sent to recaptcha
        $data = array(
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $token,
        );

        // Send request
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($verify);

        // Decode response
        $recaptcha = json_decode($response);

        // Return as bool
        return (bool)$recaptcha->success;
    }

    public function sendMail(FormSubmission $submission)
    {

        // Setup mailer
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = 'staffordshireceremonies.co.uk';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreply@staffordshireceremonies.co.uk';
        $mail->Password = 'Xc^ARkT@bC.g';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom("noreply@staffordshireceremonies.co.uk", 'Staffordshire Ceremonies');
        $mail->addReplyTo("noreply@staffordshireceremonies.co.uk");
        $mail->addAddress($submission->sent_to);
        $mail->isHTML();
        $mail->Subject = "Contact Form Submission | Staffordshire Ceremonies";

        if (isset($_FILES['files']) && !empty($_FILES['files'])) {

            $files = Helpers::getFiles('files');
            $totalSize = array_sum(array_map(function($item) {
                return $item['size'];
            }, $files));

            // Check if attachments combined are less than 25MB.
            if ($totalSize >= 26214400) {
                throw new \Exception('Attached files are too large - Must be less than 25MB combined');
            }

            foreach ($files as $file) {
                $mail->addAttachment($file['tmp_name'], $file['name']);
            }

        }

        // Build the HTML template for the email
        ob_start();
        include CER_RESOURCES_ROOT . 'emails/default-response.php';
        $mail->Body = ob_get_contents();
        ob_end_clean();

        // Send email and update DB.
        $sent = $mail->send();
        $submission->email_sent = $sent;
        $submission->save();

    }

    public function storeSubmission($data, $sentTo = '')
    {
        $formSubmission = new FormSubmission();
        $formSubmission->setData($data);
        $formSubmission->form = $data['form_name'];
        $formSubmission->sent_to = $sentTo;
        $formSubmission->save();
        return $formSubmission;
    }

	/**
	 * Gets a registration office's email address.
	 *
	 * @param $office
	 * @return void
	 */
	public function getRegistrationOfficeEmail($office): string {
		return match ( $office ) {
			"Stafford" => get_field( 'stafford', 'options' ),
			"Lichfield" => get_field( 'lichfield', 'options' ),
			"Burton" => get_field( 'burton', 'options' ),
			"Newcastle-under-Lyme" => get_field( 'newcastle', 'options' ),
			"Cannock" => get_field( 'cannock', 'options' ),
			default => get_field( 'general', 'options' ),
		};
	}

    public function templateTest(FormSubmission $submission)
    {
        ob_start();
        include CER_RESOURCES_ROOT . 'emails/default-response.php';
        header('Content-Type: text/html');
        echo ob_get_clean();
        exit();
    }

    public function formatKey($key)
    {
        $key = str_replace('_', ' ', $key);
        return ucfirst($key);
    }

}