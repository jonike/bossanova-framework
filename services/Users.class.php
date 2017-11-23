<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Services
 */
namespace services;

class Users
{
    private $userModel = null;
    private $PermissionsModel = null;

    public function __construct()
    {
        $this->userModel = new \models\Users();
        $this->PermissionsModel = new \models\Permissions();
    }

    /**
     * Select
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function select($id)
    {
        $data = [];

        if ((int)$id > 0) {
            $data = $this->userModel->getById($id);

            if (count($data) > 0) {
                if (! $this->PermissionsModel->isAllowedHierarchy($data['permission_id'])) {
                    $data = [
                        'error' => 1,
                        'message' => '^^[You do not have permission to load this record]^^'
                    ];
                }
            } else {
                $data = [
                    'error' => 1,
                    'message' => '^^[No record found]^^'
                ];
            }
        }

        return $data;
    }

    /**
     * Insert
     *
     * @param  array  $data
     * @return array  $data
     */
    public function insert($row)
    {
        // Permission is a mandatory field
        if ($row['permission_id']) {
            if (! $this->PermissionsModel->isAllowedHierarchy($row['permission_id'])) {
                $data = [
                    'error' => 1,
                    'message' => '^^[Permission denied]^^'
                ];
            } else {
                // Avoid duplicate user email
                $email = $this->userModel->getByEmail($row['user_email']);
                $login = $this->userModel->getByLogin($row['user_login']);

                if ((isset($email['user_id']) && $email['user_id']) || isset($login['user_id']) && $login['user_id']) {
                    $data = [
                        'error' => 1,
                        'message' => '^^[This email or login is already in registered]^^'
                    ];
                } else {
                    // Generate user Password
                    $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
                    $generated = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 6)), 0, 6);
                    $pass = hash('sha512', hash('sha512', $generated) . $salt);
                    $row['user_salt'] = $salt;
                    $row['user_password'] = $pass;

                    // Avoid errors
                    if (isset($row['user_id'])) {
                        unset($row['user_id']);
                    }

                    // Add a new record
                    $data = $this->userModel->column($row)->insert();

                    if (isset($data['id']) && $data['id']) {
                        // Loading recovery email body
                        $registrationFile = defined('EMAIL_REGISTRATION_FILE') ?
                            EMAIL_REGISTRATION_FILE : "resources/texts/registration.txt";

                        // Loading registration text template
                        $content = file_get_contents($registrationFile);

                        // Replace macros
                        $content = $this->mail->replaceMacros($content, $row);
                        $content = $this->mail->translate($content);

                        // Destination
                        $to = [
                            $row['user_email'],
                            $row['user_name']
                        ];

                        // From
                        $from = [
                            MS_CONFIG_FROM,
                            MS_CONFIG_NAME
                        ];

                        // Send email
                        $this->sendmail($to, EMAIL_REGISTRATION_SUBJECT, $content, $from);
                    }
                }
            }
        } else {
            $data = [
                'error' => 1,
                'message' => '^^[Permission is a mandatory field]^^'
            ];
        }

        return $data;
    }

    /**
     * Update
     *
     * @param  array  $data
     * @return array  $data
     */
    public function update($id, $row)
    {
        $data = $this->userModel->getById($id);

        if (count($data) > 0) {
            if (! $this->PermissionsModel->isAllowedHierarchy($data['permission_id'])) {
                $data = [ 'error' => 1, 'message' => '^^[Permission denied]^^' ];
            } else {
                // Password
                if (isset($row['user_password']) && ! $row['user_password']) {
                    unset($row['user_password']);
                } else {
                    // Update password information
                    $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
                    $pass = hash('sha512', hash('sha512', $row['user_password']) . $salt);
                    $row['user_salt'] = $salt;
                    $row['user_password'] = $pass;
                }

                $result = $this->userModel->column($row)->update($id);
                $data = [ 'data' => $result, 'message' => '^^[Successfully saved]^^' ];
            }
        } else {
            $data = [ 'error' => 1, 'message' => '^^[No record found]^^' ];
        }

        return $data;
    }

    /**
     * Logical delete a user based on the user_id
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function delete($user_id)
    {
        $data = $this->userModel->getById($user_id);

        if (count($data) > 0) {
            if (! $this->PermissionsModel->isAllowedHierarchy($data['permission_id'])) {
                $data = [ 'error' => 1, 'message' => '^^[Permission denied]^^' ];
            } else {
                $this->userModel->delete($user_id);
                $data = [ 'success' => 1, 'message' => '^^[Successfully deleted]^^' ];
            }
        } else {
            $data = [ 'error' => 1, 'message' => '^^[No record found]^^' ];
        }

        return $data;
    }

    public function grid()
    {
        $data = $this->userModel->grid();

        // Convert to grid
        $grid = new \services\Grid();
        $data = $grid->get($data);

        return $data;
    }

    /**
     * Select Permissions
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function getPermissions($id = null)
    {
        $permissions = $this->PermissionsModel->combo();

        return count($permissions) > 0 ? $permissions : [ 'error' => 1, 'message' => '^^[No record found]^^' ];
    }
}
