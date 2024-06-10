<?php

namespace Ceremonies\Controllers;

class AccountController
{

    public function update()
    {
        $request = sanitize_array($_REQUEST);
        $user = get_user_by('id', $request['user']);

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Map request data to ACF field
        $data = array(
            'public_contact_number' => $request['public_contact_number'],
            'address' => $request['address'],
            'contact_number' => $request['contact_number'],
            'contact_email' => $request['email'],
            'contact_name' => $request['contact_name'],
        );

        // Update custom field data
        foreach ($data as $key => $item) {
            update_field($key, $item, 'user_' . $user->ID);
        }

        // Update main email
        if ($request['email'] !== $user->user_email) {
            $user->user_email = $request['email'];
            wp_update_user($user);
        }

        return new \WP_REST_Response(['success' => true]);

    }

    public function password()
    {
        $request = sanitize_array($_REQUEST);

        if ($request['password'] !== $request['confirm']) {
            throw new \Exception('Passwords do not match');
        }

        $user = get_user_by('id', $request['user']);

        if (!$user) {
            throw new \Exception('User not found');
        }

        wp_set_password($request['password'], $user);

        return new \WP_REST_Response(['success' => true]);

    }


    /**
     * Takes an $email and checks if an account already exists.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function emailCheck(\WP_REST_Request $request)
    {

        if (!$request->get_param('email')) {
            throw new \Exception('No email provided');
        }

        $user = get_user_by('email', $request->get_param('email'));

        $response = array(
            'success' => true,
            'found' => (bool) $user, // Make sure to return boolean
        );

        return new \WP_REST_Response($response);
    }

}