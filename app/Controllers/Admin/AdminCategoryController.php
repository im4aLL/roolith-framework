<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ApiResponseTransformer;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;
use App\Models\Admin\AdminCategory;
use App\Utils\_;

class AdminCategoryController extends Controller
{
    public function index(): bool|string
    {
        $total = AdminCategory::orm()->select([
            'field' => ['id']
        ])->count();

        $pagination = AdminCategory::raw()->query('SELECT * FROM ' . AdminCategory::tableName() . ' ORDER by id DESC')->paginate([
            'perPage' => APP_ADMIN_PAGINATION_PER_PAGE,
            'total' => $total,
            'pageUrl' => route('admin.categories.index')
        ]);
        $paginationData = $pagination->getDetails();

        $data = [
            'title' => 'Categories',
            'categories' => $paginationData,
            'pageNumbers' => $pagination->pageNumbers(),
            'total' => $total
        ];

        return $this->view('admin/category/admin-category', $data);
    }

    public function create(): bool|string
    {
        $data = [
            'title' => 'Create Category',
            'loadEditor' => true,
        ];

        return $this->view('admin/category/admin-category-create', $data);
    }

    public function store(): array
    {
        $formData = Request::only(['name', 'body']);

        $validator = new Validator();
        $validator->check($formData, [
            'name' => Rules::set()->isRequired(),
        ]);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors());
        }

        $formData['slug'] = _::slug($formData['name']);
        $formData['body'] = Request::unsafeInput('body');

        $insert = AdminCategory::orm()->insert($formData, ['slug']);

        if ($insert->success()) {
            return ApiResponseTransformer::success(['redirect' => route('admin.categories.index')]);
        } elseif ($insert->isDuplicate()) {
            return ApiResponseTransformer::error(null, 'Oops! Looks like that page name is already taken—just like all the good usernames on the internet.');
        }

        return ApiResponseTransformer::error(null, 'Uh-oh! Our page-maker just took a coffee break. Try again in a bit.');
    }

    public function show($id)
    {
    }

    public function edit($id): bool|string
    {
        $category = AdminCategory::orm()->find($id);

        $data = [
            'title' => 'Edit Category - ' . $category->name,
            'category' => $category,
            'loadEditor' => true,
        ];

        return $this->view('admin/category/admin-category-edit', $data);
    }

    public function update($id): array
    {
        $category = AdminCategory::orm()->find($id);

        if (!$category) {
            return ApiResponseTransformer::error(null, 'Well, this is awkward. Looks like the category you\'re looking for went on a coffee break and forgot to tell anyone.');
        }

        $formData = Request::all();

        $validator = new Validator();
        $validator->check($formData, [
            'name' => Rules::set()->isRequired(),
            'slug' => Rules::set()->isRequired(),
        ]);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors());
        }

        $formData['body'] = Request::unsafeInput('body');

        $update = AdminCategory::orm()->update($formData, ['id' => $category->id], ['slug']);

        if ($update->isDuplicate()) {
            return ApiResponseTransformer::error(null, 'Oops! That slug\'s taken. Try a new one!');
        }

        if ($update->success()) {
            return ApiResponseTransformer::success(['redirect' => route('admin.categories.edit', ['param' => $category->id])]);
        }

        return ApiResponseTransformer::error(null, 'We checked. It\'s perfect. Nothing to update here!');
    }

    public function destroy($id): array
    {
        $category = AdminCategory::orm()->find($id);

        if (!$category) {
            return ApiResponseTransformer::error(null, 'Well, this is awkward. You\'re trying to delete a category that\'s already living its best ghost life.');
        }

        $delete = AdminCategory::orm()->delete(['id' => $id]);

        if (!$delete->success()) {
            return ApiResponseTransformer::error(null, 'Error! This item seems to have developed an unhealthy attachment to us. We just can\'t seem to get rid of it!');
        }

        return ApiResponseTransformer::success(['redirect' => route('admin.categories.index')]);
    }
}
