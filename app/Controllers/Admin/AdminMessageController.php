<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ApiResponseTransformer;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;
use App\Models\Admin\AdminMessage;

class AdminMessageController extends Controller
{
    public function index(): bool|string
    {
        $total = AdminMessage::orm()->select([
            'field' => ['id']
        ])->count();

        $pagination = AdminMessage::raw()->query('SELECT * FROM ' . AdminMessage::tableName() . ' ORDER by created_at DESC')->paginate([
            'perPage' => APP_ADMIN_PAGINATION_PER_PAGE,
            'total' => $total,
            'pageUrl' => route('admin.messages.index')
        ]);
        $paginationData = $pagination->getDetails();

        $data = [
            'title' => 'Messages',
            'messages' => $paginationData,
            'pageNumbers' => $pagination->pageNumbers(),
            'total' => $total
        ];

        return $this->view('admin/message/admin-message', $data);
    }

    public function create()
    {
    }

    public function store()
    {
    }

    public function show($id): bool|string
    {
        $message = AdminMessage::orm()->find($id);
        $message->fields = json_decode($message->fields, true);

        $data = [
            'title' => 'Message',
            'message' => $message,
        ];

        return $this->view('admin/message/admin-message-show', $data);
    }

    public function edit($id)
    {
    }

    public function update($id): void
    {
        $message = AdminMessage::orm()->find($id);
        AdminMessage::orm()->update(['is_seen' => $message->is_seen === 1 ? 0 : 1], ['id' => $id]);

        redirectToRoute('admin.messages.index');
    }

    public function destroy($id): void
    {
        AdminMessage::orm()->delete(['id' => $id]);

        redirectToRoute('admin.messages.index');
    }

    /**
     * Store message from api
     *
     * @return array
     */
    public function sendMessage(): array
    {
        $formData = Request::only(['name', 'type', 'email', 'message', 'fields']);

        $validator = new Validator();
        $validator->check($formData, [
            'name' => Rules::set()->isRequired(),
            'type' => Rules::set()->isRequired(),
            'email' => Rules::set()->isRequired(),
            'message' => Rules::set()->isRequired(),
        ]);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors());
        }

        if (isset($formData['fields']) && is_array($formData['fields']) && count($formData['fields']) > 0) {
            $formData['fields'] = json_encode($formData['fields']);
        }
        $formData['reference'] = generateUniqueNumber();

        $insert = AdminMessage::orm()->insert($formData);

        if (!$insert->success()) {
            return ApiResponseTransformer::error(null, "Unable to send message");
        }

        return ApiResponseTransformer::success(['reference' => $formData['reference']], "Message sent successfully");
    }
}
