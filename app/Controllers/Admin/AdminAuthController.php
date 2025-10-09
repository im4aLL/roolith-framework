<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Rules;
use App\Core\SessionRateLimiter;
use App\Core\Storage;
use App\Core\Validator;
use App\Models\Admin\AdminUser;

class AdminAuthController extends AdminBaseController
{
    private AdminUser $user;
    private Validator $validator;

    private string $_changePassErrorMessageKey = 'change_pass_error';
    private string $_changePassMsgKey = 'change_pass_msg';

    public function __construct(AdminUser $adminUser, Validator $validator)
    {
        parent::__construct();

        $this->user = $adminUser;
        $this->validator = $validator;
    }

    /**
     *
     *
     * @return string
     */
    public function login(): string
    {
        // if user already logged in
        if (Storage::hasSession(APP_ADMIN_SESSION_KEY)) {
            redirectToRoute('admin.home');
        }

        $errors = Storage::getTemp('login_error');

        $data = [
            'title' => 'Roolith Admin',
        ];

        if ($errors) {
            $data['error_message'] = 'Nope. That’s not the dynamic duo we’re looking for.';
        }

        return $this->view('admin/admin-login', $data);
    }

    /**
     * Verify user credentials
     *
     * @return string
     */
    public function verifyCredential(): string
    {
        $data = Request::all();

        $rateLimiterKey = 'login_attempt';
        $rateLimiter = new SessionRateLimiter($rateLimiterKey);

        $this->validator->check([
            'email' => $data['email'],
            'password' => $data['password'],
        ], [
            'email' => Rules::set()->isEmail()->isRequired(),
            'password' => Rules::set()->isRequired(),
        ]);

        if ($rateLimiter->tooManyAttempts()) {
            return 'System is taking a coffee break after that login spam.';
        }

        if ($this->validator->fails()) {
            Storage::temp('login_error', $this->validator->errors());
            redirectToRoute('admin.auth.login');
        }

        if ($this->user->isValidUser($data['email'], $data['password'])) {
            $this->user->startSession($data['email']);
            $rateLimiter->clear();

            redirectToRoute('admin.home');
        } else {
            Storage::temp('login_error', 'invalid combination');
            redirectToRoute('admin.auth.login');
        }

        return 'The gates of the kingdom are sealed. Patience, young padawan.';
    }

    /**
     * Sign out
     *
     * @return void
     */
    public function logout(): void
    {
        $this->user->destroySession();

        redirectToRoute('admin.auth.login');
    }

    /**
     * Change a password form
     *
     * @return bool|string
     */
    public function changePassword(): bool|string
    {
        $errorMessage = Storage::getTemp($this->_changePassErrorMessageKey);
        $successMessage = Storage::getTemp($this->_changePassMsgKey);

        $data = [
            'title' => 'Change password',
        ];

        if ($errorMessage) {
            $data['error_message'] = $errorMessage;
        }

        if ($successMessage) {
            $data['success_message'] = $successMessage;
        }

        return $this->view('admin/admin-change-password', $data);
    }

    /**
     * Update password for admin user
     *
     * @return void
     */
    public function updatePassword(): void
    {
        $changePasswordRouteName = 'admin.auth.changePassword';
        $postedData = Request::only(['current_password', 'new_password', 're_new_password']);

        if (strlen($postedData['new_password']) < 6) {
            Storage::temp($this->_changePassErrorMessageKey, 'Password must be at least 6 characters.');
            redirectToRoute($changePasswordRouteName);
        }

        if ($postedData['new_password'] != $postedData['re_new_password']) {
            Storage::temp($this->_changePassErrorMessageKey, 'New change does not match');
            redirectToRoute($changePasswordRouteName);
        }

        $user = AdminUser::current();

        if (!password_verify($postedData['current_password'], $user->password)) {
            Storage::temp($this->_changePassErrorMessageKey, 'Current password does not match');
            redirectToRoute($changePasswordRouteName);
        }

        $newPassword = password_hash($postedData['new_password'], PASSWORD_DEFAULT);
        $result = AdminUser::orm()->update(['password' => $newPassword], ['id' => $user->id]);

        if ($result->success()) {
            Storage::temp($this->_changePassMsgKey, 'Password has been changed');
            redirectToRoute($changePasswordRouteName);
        }

        Storage::temp($this->_changePassErrorMessageKey, 'Unable to update password');
        redirectToRoute($changePasswordRouteName);
    }
}
