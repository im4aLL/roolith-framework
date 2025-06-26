<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Rules;
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

    public function login(): string
    {
        $errors = Storage::getTemp('login_error');

        $data = [
            'title' => 'Roolith Admin',
        ];

        if ($errors) {
            $data['error_message'] = 'Invalid email and password combination!';
        }

        return $this->view('admin/admin-login', $data);
    }

    public function verifyCredential()
    {
        $data = Request::all();

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

        if ($this->user->isValidUser($data['email'], $data['password'])) {
            $this->user->startSession($data['email']);

            redirectToRoute('home');
        } else {
            Storage::temp('login_error', 'invalid combination');
            redirectToRoute('auth.login');
        }
    }
}
