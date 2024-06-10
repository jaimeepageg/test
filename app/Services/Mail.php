<?php

namespace Ceremonies\Services;

use PHPMailer\PHPMailer\PHPMailer;

class Mail
{

    /**
     * Example class usage:
     * Mail::create()->with()->sendTo()->send();
     */

    /**
     * PHPMailer properties
     */
    private string $host = 'staffordshireceremonies.co.uk';
    private string $username = 'noreply@staffordshireceremonies.co.uk';
    private string $password = 'Xc^ARkT@bC.g';
    private string $fromAddress = 'noreply@staffordshireceremonies.co.uk';
    private string $fromName = 'Staffordshire Ceremonies';
    private int $port = 465;
    private string $replyToAddress = 'ceremonysupport@staffordshire.gov.uk';
    private PHPMailer $mail;

    /**
     * Mail data properties
     */
    private string $template = '';
    private $data = null;
    public $sent = false;

    /**
     * Creates a new mailable.
     *
     * @param string $template
     * @return Mail
     */
    public static function create($subject)
    {
        // Create instance of self
        $self = new self();

        // Setup mailer properties
        $self->mail = new PHPMailer;
        $self->mail->setFrom($self->fromAddress, $self->fromName);
        $self->mail->addReplyTo($self->replyToAddress);
        $self->mail->isHTML();

        $self->loadCredentials();

        // Set template to load and set subject
        $template = strtolower($subject);
        $self->template = str_replace(' ', '-', $template);
        $self->mail->Subject = sprintf('%s | Staffordshire Ceremonies', $subject);

        // Return instance of self to chain methods
        return $self;

    }

    private function loadCredentials()
    {
        if (in_array($_SERVER['HTTP_HOST'], ['ceremonies.local', 'sc.local', 'ceremonies.thrv.uk'])) {
            $this->mail->Host = 'mail.thrv.uk';
            $this->mail->Username = 'dev@thrv.uk';
            $this->mail->Password = 'L(&gqAnm[)#s';
            $this->mail->Port = $this->port;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mail->isSMTP();
            $this->mail->SMTPAuth = true;
            $this->mail->SMTPSecure = 'ssl';
        } else {
            $this->mail->Host = $this->host;
            $this->mail->Username = $this->username;
            $this->mail->Password = $this->password;
            $this->mail->Port = $this->port;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mail->isSMTP();
            $this->mail->SMTPAuth = true;
            $this->mail->SMTPSecure = 'ssl';
        }
    }

    /**
     * The data that should be passed to the mailable, either
     * a body of text or a flat array.
     *
     * @param string|array $data
     * @return Mail
     */
    public function with($data)
    {

        // Make sure data is either array or sting.
        if (!is_array($data) && !is_string($data)) {
            throw new \TypeError('Data sent in an email must be either Array or String. If passing an Eloquent model, use the toArray() method.');
        }

        // Set on object and return
        $this->data = $data;
        return $this;

    }

    public function sendTo($email)
    {
        $this->mail->addAddress($email);
        return $this;
    }

    /**
     * Send this mailable to an array of emails
     *
     * @param array $emails
     * @return $this
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendToMany(array $emails)
    {
        foreach ($emails as $email) {
            $this->mail->addAddress($email);
        }
        return $this;
    }

    /**
     * Send to a specific registration office.
     *
     * @param $office
     * @return Mail
     */
    public function sendToOffice($office)
    {
        $email = match ($office) {
            "Stafford" => get_field('stafford', 'options'),
            "Lichfield" => get_field('lichfield', 'options'),
            "Burton" => get_field('burton', 'options'),
            "Newcastle" => get_field('newcastle', 'options'),
            "Cannock" => get_field('cannock', 'options'),
            default => get_field('general', 'options'),
        };
        $this->sendTo($email);
        return $this;
    }

    /**
     * Attaches a file to the email.
     *
     * @param $file
     * @return void
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function attach($file)
    {
        $this->mail->addAttachment($file);
    }

    /**
     * Builds the mailable and sends it.
     *
     * @return Mail
     */
    public function send()
    {

        try {
            // Setup data for use in template
            $data = $this->data;

            // Build the HTML template for the email
            ob_start();
            include CER_RESOURCES_ROOT . sprintf('emails/%s.php', $this->template);
            $emailContents = ob_get_contents();
            ob_end_clean();

            // Send email
            if (CEREMONIES_DEV_MODE) {
                $this->sent = mail('c.underhill@wethrive.agency', $this->mail->Subject, $emailContents, 'Content-Type: text/html');
            } else {
                $this->mail->Body = $emailContents;
                $this->sent = $this->mail->send();

                if (!$this->sent) {
                    throw new \Exception($this->mail->ErrorInfo);
                }
            }
        } catch (\Exception $e) {
            Logger::notifySlack('Failed to send ' . $this->template . ' email ```Error message: ' . $e->getMessage() . '```');
        }


        return $this;

    }

    /**
     * Displays the email instead of sending it, useful
     * for testing.
     *
     * @return Mail
     */
    public function display()
    {

        // Setup data for use in template
        $data = $this->data;

        // Build the HTML template for the email
        include CER_RESOURCES_ROOT . sprintf('emails/%s.php', $this->template);
        exit();

//        return $this;

    }

    private function formatKey($key)
    {
        $key = str_replace('_', ' ', $key);
        return ucfirst($key);
    }

}