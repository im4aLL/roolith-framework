<?php
namespace App\Middlewares\Admin;

use App\Core\Storage;
use Roolith\Route\Middleware;
use Roolith\Route\Request;
use Roolith\Route\Response;

class AdminAuthMiddleware extends Middleware
{
    /**
     * Check if user is authenticated
     *
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    public function process(Request $request, Response $response): bool
    {
        if (!Storage::hasSession(APP_ADMIN_SESSION_KEY)) {
            redirectToRoute('admin.auth.login');
        }

        return true;
    }
}
