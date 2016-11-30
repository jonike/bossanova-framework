<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Me (Social Network Plugin)
 */
namespace modules\Me;

use Bossanova\Module\Module;

class Me extends Module
{
    /**
     * Get user info
     *
     * @return string $json Return all user information in a json format
     */
    function info()
    {
        $row = array();

        if (isset($_SESSION['user_id'])) {
            // User model
            $user = new \models\Users();

            // Return the profile information
            $row = $user->getProfile($_SESSION['user_id']);
        }

        return $this->json_encode($row);
    }

    /**
     * Update user profile
     *
     * @param array $row User information
     */
    public function profile($row = null)
    {
        // User model
        $user = new \models\Users();

        // Update record
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            // Email is required
            if (! isset($_POST['user_email']) || ! $_POST['user_email']) {
                $data['message'] = "^^[E-mail is required]^^.";
            } else {
                // Verify if any user have the email
                $row = $user->getByEmail($_POST['user_email']);
            }

            // If the user exists update operations
            if (isset($_SESSION['user_id'])) {
                if (isset($row['user_id']) && $row['user_id'] != $_SESSION['user_id']) {
                    // This email is already registered for another user
                    $data['message'] = "^^[This e-mail is already registered]^^.
                        ^^[Please use the recovery tool to request access to your account]^^.";
                } else {
                    // Columns to be saved
                    $row = $this->getPost(array(
                        'user_email',
                        'user_name',
                        'user_facebook',
                        'user_category',
                        'user_location'
                    ));

                    // Update the user information
                    $data = $user->setProfile($row, $_SESSION['user_id']);
                }
            } else {
                // New user operations
                if (isset($row['user_id'])) {
                    // This email is already registered for another user
                    $data['message'] = "^^[This e-mail is already registered]^^.
                        ^^[Please use the recovery tool to request access to your account]^^.";
                } else {
                    // Columns to be saved
                    $row = $this->getPost(array(
                        'user_email',
                        'user_name',
                        'user_facebook',
                        'user_category',
                        'user_location'
                    ));

                    // Add a new user
                    $data = $user->setProfile($row);

                    // Data for macro replacement
                    $row['url'] = $this->getLink('me/login');

                    // Loading recovery email body
                    $content = file_get_contents("resources/texts/registration.txt");

                    // Replace macros
                    $content = $this->replaceMacros($content, $row);

                    // Send email
                    $this->sendmail(array(
                        array(
                            $row['user_email'],
                            $row['user_name']
                        )
                    ), EMAIL_REGISTRATION_SUBJECT, $content, array(
                        MS_CONFIG_FROM,
                        MS_CONFIG_NAME
                    ));

                    // Return text to the user
                    $data['message'] = "^^[User registered with success, a confirmartion link has been sent to your
                        email address. If you have problems to receive, please take a look in your spam box]^^.";
                }
            }
        } else {
            if (isset($_SESSION['user_id'])) {
                // Send the user information to the view
                $this->view = $user->getById($_SESSION['user_id']);
            } else {
                // Blank information to the view
                $this->view = array(
                    'user_email' => '',
                    'user_name' => '',
                    'user_city' => '',
                    'user_gender' => '',
                    'user_description' => ''
                );
            }
        }

        return isset($data) ? $this->json_encode($data) : null;
	}
}
