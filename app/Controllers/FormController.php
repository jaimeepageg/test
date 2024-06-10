<?php

namespace Ceremonies\Controllers;

use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\FormSubmission;
use Ceremonies\Models\Package;
use Ceremonies\Repositories\FormsRepository;
use Ceremonies\Services\Mail;

class FormController
{

    public function __construct()
    {
        $this->repository = Bootstrap::container()->get(FormsRepository::class);
    }

    public function index()
    {
        $forms = FormSubmission::orderBy('id', 'desc')->get();
        $forms = $forms->map(function($form) {
            return [
                'id' => $form->id,
                'form' => $form->getName(),
                'sent_by' => $form->getUserEmail(),
                'sent_to' => $form->sent_to,
                'email_sent' => $form->hasEmailSent(),
                'sent_at' => $form->getSentDate(),
            ];
        });
        return new \WP_REST_Response($forms);
    }

    public function single(\WP_Rest_Request $request) {
        $form = FormSubmission::where('id', $request->get_param('id'))->first();
        $form->data = $form->getData();
        $form->email_sent = $form->hasEmailSent();
        $form->user_email = $form->getUserEmail();
        $form->sent_date = $form->getSentDate();
        $form->form = $form->getName();
        $form->message = $form->getMessage();
        return new \WP_Rest_Response($form);
    }

    /**
     * Handles a contact form submission from the
     * frontend.
     *
     * @return \WP_REST_Response
     */
    public function handle()
    {

        $request = sanitize_array($_REQUEST);
        $sendToEmail = get_field('contact_form_email', 'options');

        try {
            $this->repository->checkRecaptcha($request['recaptcha_token']);
            $formSubmission = $this->repository->storeSubmission($request, $sendToEmail);
            $this->repository->sendMail($formSubmission);
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => 'false',
                'message' => $e->getMessage()
            ], 500);
        }

        // Send response
        return new \WP_REST_Response(['success' => true, 'redirect' => home_url('/thank-you/')]);

    }

    public function mailTest()
    {
        header('Content-Type: text/html');
        $formSubmission = FormSubmission::all()->first();
        $this->repository->sendMail($formSubmission);
    }
}
