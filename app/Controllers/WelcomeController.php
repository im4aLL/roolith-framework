<?php
namespace App\Controllers;


/**
 * Demo welcome controller.
 */
class WelcomeController extends Controller
{
    /**
     * Render the welcome page.
     *
     * @return string Rendered HTML.
     */
    public function index(): string
    {
        $data = [
            'content' => 'Welcome to Roolith framework!',
            'title' => 'Roolith Framework',
        ];

        return $this->view('home', $data);
    }

    /**
     * Show the create form (demo stub).
     *
     * @return void
     */
    public function create(): void
    {
    }

    /**
     * Store a new resource (demo stub).
     *
     * @return void
     */
    public function store(): void
    {
    }

    /**
     * Show a single resource (demo stub).
     *
     * @param string $id Resource identifier.
     * @return void
     */
    public function show(string $id): void
    {
    }

    /**
     * Show the edit form (demo stub).
     *
     * @param string $id Resource identifier.
     * @return void
     */
    public function edit(string $id): void
    {
    }

    /**
     * Update a resource (demo stub).
     *
     * @param string $id Resource identifier.
     * @return void
     */
    public function update(string $id): void
    {
    }

    /**
     * Delete a resource (demo stub).
     *
     * @param string $id Resource identifier.
     * @return void
     */
    public function destroy(string $id): void
    {
    }
}
