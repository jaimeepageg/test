<?php

namespace Ceremonies\Models;

use Ceremonies\Core\Model;

class FormSubmission extends Model
{

    public function setData($data)
    {
        $this->data = json_encode($data);
    }

    public function getData()
    {
        if (is_array($this->data)) {
            return $this->data;
        }
        return json_decode($this->data, true);
    }

    public function getEmailData() {
        $data = $this->getData();
        unset($data['recaptcha_token']);
        return $data;
    }

    public function getName()
    {
        $name = str_replace('_', ' ', $this->form);
        return ucfirst($name);
    }

    public function getSentDate()
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $this->updated_at);
        return $date->format('H:i d/m/Y');
    }

    public function hasEmailSent()
    {
        return (bool) $this->email_sent ? 'Sent' : 'Failed';
    }

    /**
     * Checks common keys in the form submission JSON data
     * for the submitted email address.
     *
     * @return mixed|string
     */
    public function getUserEmail()
    {

        $data = $this->getData();

        if (isset($data['email'])) {
            return $data['email'];
        }

        if (isset($data['email_address'])) {
            return $data['email_address'];
        }

        return 'Not Found';

    }


    public function getMessage()
    {
        $data = $this->getData();
        return $data['message'];
    }

}