<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ApiResponseTransformer;
use App\Core\Dto\ApiResponseDTO;
use App\Core\LazyLoad;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;
use App\Models\Admin\AdminCategory;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminPageCategory;
use App\Models\Admin\AdminUser;
use App\Utils\_;

class AdminPageController extends Controller
{
    public function index(): string|bool
    {
        $total = AdminPage::orm()->select([
            'field' => ['id']
        ])->count();

        $pagination = AdminPage::orm()->query('SELECT * FROM ' . AdminPage::tableName() . ' ORDER by id DESC')->paginate([
            'perPage' => APP_ADMIN_PAGINATION_PER_PAGE,
            'total' => $total,
            'pageUrl' => route('admin.pages.index')
        ]);
        $paginationData = $pagination->getDetails();

        $lazyLoad = new LazyLoad($paginationData->data, ['add_array' => true]);
        $lazyLoad->with(AdminUser::class, 'user_id')->with(AdminPageCategory::class, 'id', 'page_id')->get();

        $this->attachCategoryNames($paginationData->data);

        $data = [
            'title' => 'Pages',
            'pages' => $paginationData,
            'pageNumbers' => $pagination->pageNumbers(),
            'total' => $total
        ];

        $data['isShowPagination'] = $paginationData->total > $paginationData->perPage;

//        p($data, true);

        return $this->view('admin/page/admin-page', $data);
    }

    public function create(): bool|string
    {
        $categories = AdminCategory::all();

        $data = [
            'title' => 'Create Page',
            'loadEditor' => true,
            'categories' => $categories,
        ];

        return $this->view('admin/page/admin-page-create', $data);
    }

    public function store(): ApiResponseDTO
    {
        $formData = Request::only(['title', 'type', 'status', 'body', 'category_id']);

        $validator = new Validator();
        $validator->check($formData, [
            'title' => Rules::set()->isRequired(),
            'type' => Rules::set()->isRequired(),
            'status' => Rules::set()->isRequired(),
            'body' => Rules::set()->isRequired(),
            'category_id' => Rules::set()->isArray(),
        ]);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors());
        }

        $formData['slug'] = _::slug($formData['title']);
        $formData['user_id'] = AdminUser::getUserId();
        $formData['body'] = $_POST['body']; // trusting in the name of admin :D
        $categories = $formData['category_id'];
        unset($formData['category_id']);

        $insert = AdminPage::orm()->insert($formData, ['slug']);
        if ($insert->success()) {
            $this->storeCategories($categories, $insert->insertedId());

            return ApiResponseTransformer::success(['redirect' => route('admin.pages.index')]);
        } elseif ($insert->isDuplicate()) {
            return ApiResponseTransformer::error([], 'Oops! Looks like that page name is already taken—just like all the good usernames on the internet.');
        }

        return ApiResponseTransformer::error([], 'Uh-oh! Our page-maker just took a coffee break. Try again in a bit.');
    }

    private function storeCategories(array $categories, int $pageId): void
    {
        $categories = _::compact($categories);

        if (count($categories) === 0) {
            return;
        }

        foreach ($categories as $categoryId) {
            $data = [];
            $data['page_id'] = $pageId;
            $data['category_id'] = $categoryId;

            AdminPageCategory::orm()->insert($data);
        }
    }

    public function show($id): int
    {
        return $id;
    }

    public function edit($id): bool|string
    {
        $page = AdminPage::orm()->find($id);
        $pageCategories = AdminPageCategory::orm()->where('page_id', $id)->get();
        $page->category_ids = _::pluck($pageCategories, 'category_id');
        $categories = AdminCategory::all();

        $data = [
            'title' => 'Edit Page - ' . $page->title,
            'page' => $page,
            'loadEditor' => true,
            'categories' => $categories,
        ];

        return $this->view('admin/page/admin-page-edit', $data);
    }

    public function update($id)
    {
        return Request::all();
    }

    public function destroy($id)
    {
    }

    private function attachCategoryNames(array $paginatedData): void
    {
        $categoryIds = [];
        foreach ($paginatedData as $data) {
            if (!$data->id_data_array) {
                continue;
            }

            $ids = _::pluck($data->id_data_array, 'category_id');
            $categoryIds = array_merge($categoryIds, $ids);
        }

        if (count($categoryIds) === 0) {
            return;
        }

        $categories = AdminCategory::orm()->select([
            'field' => ['id', 'name'],
            'condition' => 'WHERE id IN (' . implode(',', $categoryIds) . ')'
        ])->get();

        foreach ($paginatedData as $data) {
            if (!$data->id_data_array) {
                continue;
            }

            foreach ($data->id_data_array as $category) {
                $matchedCategory = _::find($categories, function ($cat) use ($category) {
                    return $cat->id == $category->category_id;
                });

                if (!$matchedCategory) {
                    continue;
                }

                $category->name = $matchedCategory->name;
            }

            $data->categoryNames = implode(', ', _::pluck($data->id_data_array, 'name'));
        }
    }
}
