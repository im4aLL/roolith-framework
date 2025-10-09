<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\Traits\AdminFilterTrait;
use App\Core\ApiResponseTransformer;
use App\Core\Exceptions\Exception;
use App\Core\LazyLoad;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;
use App\Models\Admin\AdminCategory;
use App\Models\Admin\AdminModule;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminPageCategory;
use App\Models\Admin\AdminPageModule;
use App\Models\Admin\AdminUser;
use App\Utils\_;

class AdminPageController extends AdminBaseController
{
    use AdminFilterTrait;

    /**
     * Show a list of pages with pagination
     *
     * @return string|bool
     */
    public function index(): string|bool
    {
        [
            'selectArray' => $selectArray,
            'filterUrlString' => $filterUrlString,
            'filterInput' => $filterInput,
            'whereConditions' => $whereConditions,
        ] = $this->filterData();

        $total = AdminPage::orm()->select($selectArray)->count();

        $queryString = 'SELECT * FROM ' . AdminPage::tableName() . ' ORDER by id DESC';
        if ($filterInput) {
            $queryString = 'SELECT * FROM ' . AdminPage::tableName() . ' WHERE ' . implode(' AND ', $whereConditions) . ' ORDER by id DESC';
        }

        $pagination = AdminPage::raw()->query($queryString)->paginate([
            'perPage' => APP_ADMIN_PAGINATION_PER_PAGE,
            'total' => $total,
            'pageUrl' => route('admin.pages.index') . ($filterUrlString ? "?$filterUrlString" : "")
        ]);
        $paginationData = $pagination->getDetails();

        $lazyLoad = new LazyLoad($paginationData->data, ['add_array' => true]);
        $lazyLoad->with(AdminUser::class, 'user_id')->with(AdminPageCategory::class, 'id', 'page_id')->with(AdminPageModule::class, 'id', 'page_id')->get();

        $this->_attachCategoryNames($paginationData->data);

        $data = [
            'title' => 'Pages',
            'pages' => $paginationData,
            'pageNumbers' => $pagination->pageNumbers(),
            'total' => $total,
            'filterInput' => $filterInput,
        ];

        return $this->view('admin/page/admin-page', $data);
    }

    /**
     * Show add a new page form
     *
     * @return bool|string
     */
    public function create(): bool|string
    {
        $categories = AdminCategory::all();
        $modules = AdminModule::all();

        $data = [
            'title' => 'Create Page',
            'loadEditor' => true,
            'categories' => $categories,
            'modules' => $modules,
        ];

        return $this->view('admin/page/admin-page-create', $data);
    }

    /**
     * Save new page
     *
     * @return array
     */
    public function store(): array
    {
        $formData = Request::only(['title', 'type', 'status', 'body', 'category_id', 'module_id']);

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
        $formData['body'] = Request::unsafeInput('body');
        $categoryIds = $formData['category_id'];
        unset($formData['category_id']);
        $moduleIds = $formData['module_id'];
        unset($formData['module_id']);

        $insert = AdminPage::orm()->insert($formData, ['slug']);
        if ($insert->success()) {
            $this->_addCategoryToPage($categoryIds, $insert->insertedId());
            $this->_addModuleToPage($moduleIds, $insert->insertedId());

            return ApiResponseTransformer::success(['redirect' => route('admin.pages.index')]);
        } elseif ($insert->isDuplicate()) {
            return ApiResponseTransformer::error(null, 'Oops! Looks like that page name is already taken—just like all the good usernames on the internet.');
        }

        return ApiResponseTransformer::error(null, 'Uh-oh! Our page-maker just took a coffee break. Try again in a bit.');
    }

    /**
     * @throws Exception
     */
    public function show($id): int
    {
        throw new Exception("Nothing here for page id $id");
    }

    /**
     * Show an edit form for a page
     *
     * @param $id
     * @return bool|string
     */
    public function edit($id): bool|string
    {
        $page = AdminPage::orm()->find($id);
        $pageCategories = AdminPageCategory::orm()->where('page_id', $id)->get();
        $page->category_ids = _::pluck($pageCategories, 'category_id');
        $page->modules = AdminPageModule::orm()->select([
            'orderBy' => 'position ASC',
        ])->where('page_id', $id)->get();

        $categories = AdminCategory::all();
        $modules = AdminModule::all();

        $data = [
            'title' => 'Edit Page - ' . $page->title,
            'page' => $page,
            'loadEditor' => true,
            'categories' => $categories,
            'modules' => $modules,
        ];

        return $this->view('admin/page/admin-page-edit', $data);
    }

    /**
     * Update a page
     *
     * @param $id
     * @return array
     */
    public function update($id): array
    {
        $page = AdminPage::orm()->find($id);

        if (!$page) {
            return ApiResponseTransformer::error(null, 'Well, this is awkward. Looks like the page you\'re looking for went on a coffee break and forgot to tell anyone.');
        }

        $pageCategories = AdminPageCategory::orm()->where('page_id', $id)->get();
        $oldCategoryIds = _::pluck($pageCategories, 'category_id');

        $pageModules = AdminPageModule::orm()->where('page_id', $id)->get();
        $oldModuleIds = _::pluck($pageModules, 'module_id');

        $formData = Request::all();

        $validator = new Validator();
        $validator->check($formData, [
            'title' => Rules::set()->isRequired(),
            'type' => Rules::set()->isRequired(),
            'status' => Rules::set()->isRequired(),
            'body' => Rules::set()->isRequired(),
            'category_id' => Rules::set()->isArray(),
            'slug' => Rules::set()->isRequired(),
            'module_id' => Rules::set()->isArray(),
        ]);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors());
        }

        $formData['body'] = Request::unsafeInput('body');
        $newCategoryIds = $formData['category_id'];
        unset($formData['category_id']);

        $newModuleIds = $formData['module_id'];
        $isModuleChanged = $this->_isModuleChanged($oldModuleIds, $newModuleIds);
        unset($formData['module_id']);

        $update = AdminPage::orm()->update($formData, ['id' => $page->id], ['slug']);

        if ($update->isDuplicate()) {
            return ApiResponseTransformer::error(null, 'Oops! That slug\'s taken. Try a new one!');
        }

        if ($update->success()) {
            $categoryChangeResult = _::compareArrays($oldCategoryIds, $newCategoryIds);
            $this->_removeCategoryFromPage($categoryChangeResult['removed'], $page->id);
            $this->_addCategoryToPage($categoryChangeResult['added'], $page->id);

            if ($isModuleChanged) {
                $this->_updatePageModules($newModuleIds, $page->id);
            }

            return ApiResponseTransformer::success(['redirect' => route('admin.pages.edit', ['param' => $page->id])]);
        }

        return ApiResponseTransformer::error(null, 'We checked. It\'s perfect. Nothing to update here!');
    }

    /**
     * Delete a page
     *
     * @param $id
     * @return array
     */
    public function destroy($id): array
    {
        $page = AdminPage::orm()->find($id);

        if (!$page) {
            return ApiResponseTransformer::error(null, 'Well, this is awkward. You\'re trying to delete a page that\'s already living its best ghost life.');
        }

        $pageCategories = AdminPageCategory::orm()->where('page_id', $id)->get();
        $categoryIds = _::pluck($pageCategories, 'category_id');

        $this->_removeCategoryFromPage($categoryIds, $page->id);
        $this->_deleteAllPageModules($page->id);
        $delete = AdminPage::orm()->delete(['id' => $id]);

        if (!$delete->success()) {
            return ApiResponseTransformer::error(null, 'Error! This item seems to have developed an unhealthy attachment to us. We just can\'t seem to get rid of it!');
        }

        return ApiResponseTransformer::success(['redirect' => route('admin.pages.index')]);
    }

    public function fileUpload(): string
    {
        $fileInput = Request::file('image');
        $fileName = $fileInput->upload(APP_ADMIN_FILE_MANAGER_MODULE_DATA_DIR);

        return url(APP_ADMIN_FILE_MANAGER_MODULE_DATA_DIR . $fileName);
    }

    /**
     * Add categories to a page
     *
     * @param array $categoryIds
     * @param int $pageId
     * @return void
     */
    private function _addCategoryToPage(array $categoryIds, int $pageId): void
    {
        $categories = _::compact($categoryIds);

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

    /**
     * Add module to pages
     *
     * @param array $moduleIds
     * @param int $pageId
     * @return void
     */
    private function _addModuleToPage(array $moduleIds, int $pageId): void
    {
        $moduleIdArray = _::compact($moduleIds);

        if (count($moduleIdArray) === 0) {
            return;
        }

        foreach ($moduleIdArray as $index => $moduleId) {
            $data = [];
            $data['page_id'] = $pageId;
            $data['module_id'] = $moduleId;
            $data['position'] = $index + 1;

            AdminPageModule::orm()->insert($data);
        }
    }

    /**
     * Remove categories from a page
     *
     * @param array $categoryIds
     * @param int $pageId
     * @return void
     */
    private function _removeCategoryFromPage(array $categoryIds, int $pageId): void
    {
        $categories = _::compact($categoryIds);

        if (count($categories) === 0) {
            return;
        }

        foreach ($categories as $categoryId) {
            $data = [];
            $data['page_id'] = $pageId;
            $data['category_id'] = $categoryId;

            AdminPageCategory::orm()->delete($data);
        }
    }

    /**
     * Attach category names to a paginated result
     *
     * @param array $paginatedData
     * @return void
     */
    private function _attachCategoryNames(array $paginatedData): void
    {
        $categoryIds = [];
        foreach ($paginatedData as $data) {
            if (!$data->admin_page_category_array) {
                continue;
            }

            $ids = _::pluck($data->admin_page_category_array, 'category_id');
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
            if (!$data->admin_page_category_array) {
                continue;
            }

            foreach ($data->admin_page_category_array as $category) {
                $matchedCategory = _::find($categories, function ($cat) use ($category) {
                    return $cat->id == $category->category_id;
                });

                if (!$matchedCategory) {
                    continue;
                }

                $category->name = $matchedCategory->name;
            }

            $data->categoryNames = implode(', ', _::pluck($data->admin_page_category_array, 'name'));
        }
    }

    /**
     * Remove page modules and add new modules
     *
     * @param array $moduleIds
     * @param int $pageId
     * @return void
     */
    private function _updatePageModules(array $moduleIds, int $pageId): void
    {
        $delete = $this->_deleteAllPageModules($pageId);

        if (!$delete) {
            return;
        }

        $this->_addModuleToPage($moduleIds, $pageId);
    }

    /**
     * Delete page modules
     *
     * @param int $pageId
     * @return bool
     */
    private function _deleteAllPageModules(int $pageId): bool
    {
        $delete = AdminPageModule::orm()->delete(['page_id' => $pageId]);

        return $delete->success();
    }

    /**
     * Is module order changed or added new modules?
     *
     * @param array $oldModuleArray
     * @param array $newModuleArray
     * @return bool
     */
    private function _isModuleChanged(array $oldModuleArray, array $newModuleArray): bool
    {
        $isModuleChanged = false;

        if (count($oldModuleArray) != count($newModuleArray)) {
            return true;
        }

        foreach ($oldModuleArray as $index => $oldModuleId) {
            if ($oldModuleId != $newModuleArray[$index]) {
                $isModuleChanged = true;
                break;
            }
        }

        return $isModuleChanged;
    }
}
