<?php

namespace App\Api\Controller\DialogV3;

use App\Common\ResponseCode;
use App\Models\Dialog;
use App\Models\DialogMessage;
use App\Models\User;
use App\Providers\DialogMessageServiceProvider;
use App\Repositories\DialogMessageRepository;
use App\Repositories\DialogRepository;
use App\Repositories\UserRepository;
use Discuz\Auth\Exception\NotAuthenticatedException;
use Discuz\Base\DzqController;

class ListDialogMessageV2Controller extends DzqController
{
    /**
     * @var DialogRepository
     */
    protected $dialogs;

    /**
     * @var DialogMessageRepository
     */
    protected $dialogMessage;

    public $providers = [
        DialogMessageServiceProvider::class,
    ];

    public function __construct(DialogRepository $dialogs, DialogMessageRepository $dialogMessage)
    {
        $this->dialogs = $dialogs;
        $this->dialogMessage = $dialogMessage;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if ($this->user->isGuest()) {
            $this->outPut(ResponseCode::JUMP_TO_LOGIN,'');
        }
        return true;
    }

    public function main()
    {
        $user = $this->user;

        $filters = $this->inPut('filter') ?: [];
        $page = $this->inPut('page') ?: 1;
        $perPage = $this->inPut('perPage') ?: 10;

        if(empty($filters) || empty($filters['dialogId'])){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }
        $dialogData = Dialog::query()->where("id",$filters['dialogId'])->first();
        if(empty($dialogData)){
            $this->outPut(ResponseCode::INVALID_PARAMETER, '对话ID'.$filters['dialogId'].'记录不存在');
        }

        //设置登录用户已读
        $dialog = $this->dialogs->findOrFail($filters['dialogId'], $user);
        if ($dialog->sender_user_id == $user->id) {
            $type = 'sender';
        } else {
            $type = 'recipient';
        }
        $dialog->setRead($type);

        $pageData = $this->search($user, $filters, $dialog, $perPage, $page);
        $this->outPut(ResponseCode::SUCCESS, '', $pageData);
    }

    public function search(User $user, $filters, $dialog, $perPage, $page)
    {
        $query = $this->dialogMessage->query()
            ->with([
                'user:id,username,avatar,avatar_at',
            ]);

        $query->select('dialog_message.*');
        $query->where('dialog_id', $filters['dialogId']);

        $query->join(
            'dialog',
            'dialog.id',
            '=',
            'dialog_message.dialog_id'
        )->where(function ($query) use ($user) {
            $query->where('dialog.sender_user_id', $user->id);
            $query->orWhere('dialog.recipient_user_id', $user->id);
        });

        // 按照登陆用户的删除情况过滤数据
        if ($dialog->sender_user_id == $user->id && $dialog->sender_deleted_at) {
            $query->whereColumn(
                'dialog_message.created_at',
                '>',
                'dialog.sender_deleted_at'
            );
        }
        if ($dialog->recipient_user_id == $user->id && $dialog->recipient_deleted_at) {
            $query->whereColumn(
                'dialog_message.created_at',
                '>',
                'dialog.recipient_deleted_at'
            );
        }

        $query->orderBy('created_at', 'desc');

        $pageData = $this->pagination($page, $perPage, $query, false);
        $pageData['pageData'] = $pageData['pageData']->map(function (DialogMessage $i) {

            $user = [
                'id'=>$i->user->id,
                'avatar'=>$i->user->avatar,
                'username'=>$i->user->username,
            ];

            return [
                'id' => $i->id,
                'userId' => $i->user_id,
                'dialogId' => $i->dialog_id,
                'attachmentId' => $i->attachment_id,
                'summary' => $i->summary,
                'messageText' => $i->getMessageText(),
                'messageTextHtml' => $i->formatMessageText(),
                'imageUrl' => $i->getImageUrlMessageText(),
                'updatedAt' => optional($i->updated_at)->format('Y-m-d H:i:s'),
                'createdAt' => optional($i->created_at)->format('Y-m-d H:i:s'),
                'user' => $user,
            ];
        });

        return $pageData;
    }
}
