<?php
namespace App\Middlewares\Admin;

use Roolith\Route\Middleware;
use Roolith\Route\Request;
use Roolith\Route\Response;

class AdminAuthMiddleware extends Middleware
{
    public function process(Request $request, Response $response): bool
    {
        return true;
    }
}
