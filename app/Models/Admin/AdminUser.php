<?php
namespace App\Models\Admin;

use App\Core\Storage;
use App\Models\Model;

class AdminUser extends Model
{
    protected $table = 'users';
    private static string $sessionKey = APP_ADMIN_SESSION_KEY;

    /**
     * Check if email and password combination are valid
     *
     * @param string $email
     * @param string $password
     * @return boolean
     */
    public function isValidUser(string $email, string $password): bool
    {
        $user = $this->orm()->where('email', $email, '=')->first();

        if ($user) {
            return password_verify($password, $user->password);
        }

        return false;
    }

    /**
     * Start user session
     *
     * @param string $email
     * @return boolean
     */
    public function startSession(string $email): bool
    {
        return Storage::setSession(self::$sessionKey, $email);
    }

    /**
     * Destroy user session
     *
     * @return boolean
     */
    public function destroySession(): bool
    {
        return Storage::deleteSession(self::$sessionKey);
    }

    /**
     * Get logged user ID
     *
     * @return bool|int
     */
    public static function getUserId(): bool|int
    {
        $user = self::current();

        if ($user) {
            return $user->id;
        }

        return false;
    }

    /**
     * Get current user
     *
     * @return bool|false|object
     */
    public static function current(): object|bool
    {
        $loggedInEmail = Storage::getSession(self::$sessionKey);
        if (!$loggedInEmail) {
            return false;
        }

        return AdminUser::orm()->where('email', $loggedInEmail, '=')->first();
    }
}
