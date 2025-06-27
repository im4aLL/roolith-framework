<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Rules;
use App\Core\SessionRateLimiter;
use App\Core\Storage;
use App\Core\Validator;
use App\Models\Admin\AdminUser;

class AdminAuthController extends Controller
{
    private AdminUser $user;
    private Validator $validator;

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

        if ($this->validator->fails()) {
            Storage::temp('login_error', $this->validator->errors());
            redirectToRoute('admin.auth.login');
        }

        if ($rateLimiter->tooManyAttempts()) {
            return 'System is taking a coffee break after that login spam.';
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
}
