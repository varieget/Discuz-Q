<?php

namespace App\Api\Controller\DialogV3;

use App\Common\ResponseCode;
use App\Models\Dialog;
use App\Models\User;
use App\Providers\DialogMessageServiceProvider;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\NotAuthenticatedException;
use Discuz\Base\DzqController;

class ListDialogV2Controller extends DzqController
{
    use AssertPermissionTrait;

    public $providers = [
        DialogMessageServiceProvider::class,
    ];

    public function main()
    {
        $user = $this->user;
        try {
            $this->assertRegistered($user);
        } catch (NotAuthenticatedException $e) {
            $this->outPut(ResponseCode::JUMP_TO_LOGIN);
        }

        $page = $this->inPut('page') ?: 1;
        $perPage = $this->inPut('perPage') ?: 10;

        $pageData = $this->search($user, $perPage, $page);

        $this->outPut(ResponseCode::SUCCESS, '', $pageData);
    }

    public function search(User $user, $perPage, $page)
    {
        $query = Dialog::query()
            ->with([
                'sender:id,username,avatar',
                'recipient:id,username,avatar',
                'dialogMessage',
            ]);
        $tablePrefix = config('database.connections.mysql.prefix');

        $query->distinct('dialog.id')
            ->select('dialog.*')
            ->join(
                'dialog_message',
                'dialog.id',
                '=',
                'dialog_message.dialog_id'
            )
            ->where(function ($query) use ($tablePrefix, $user) {
                $query->where('dialog.sender_user_id', $user->id)
                    ->whereRaw("{$tablePrefix}dialog_message.`created_at` > IFNULL({$tablePrefix}dialog.`sender_deleted_at`, 0 )");
            })
            ->orWhere(function ($query) use ($tablePrefix, $user) {
                $query->where('dialog.recipient_user_id', $user->id)
                    ->whereRaw("{$tablePrefix}dialog_message.`created_at` > IFNULL({$tablePrefix}dialog.`recipient_deleted_at`, 0 )");
            })
            ->orderBy('dialog_message_id', 'desc');

        $pageData = $this->pagination($page, $perPage, $query, false);
        $pageData['pageData'] = $pageData['pageData']->map(function (Dialog $i) {
            $msg = $i->dialogMessage;
            $msg = $msg
                ? [
                    'id' => $msg->id,
                    'userId' => $msg->user_id,
                    'dialogId' => $msg->dialog_id,
                    'attachmentId' => $msg->attachment_id,
                    'summary' => $msg->summary,
                    'messageText' => $msg->getMessageText(),
                    'messageTextHtml' => $msg->formatMessageText(),
                    'imageUrl' => $msg->getImageUrlMessageText(),
                    'updatedAt' => optional($msg->updated_at)->format('Y-m-d H:i:s'),
                    'createdAt' => optional($msg->created_at)->format('Y-m-d H:i:s'),
                ]
                : null;
            return [
                'id' => $i->id,
                'dialogMessageId' => $i->dialog_message_id ?: 0,
                'senderUserId' => $i->sender_user_id,
                'recipientUserId' => $i->recipient_user_id,
                'senderReadAt' => optional($i->sender_read_at)->format('Y-m-d H:i:s'),
                'recipientReadAt' => optional($i->recipient_read_at)->format('Y-m-d H:i:s'),
                'updatedAt' => optional($i->updated_at)->format('Y-m-d H:i:s'),
                'createdAt' => optional($i->created_at)->format('Y-m-d H:i:s'),
                'sender' => $i->sender,
                'recipient' => $i->recipient,
                'dialogMessage' => $msg,
            ];
        });

        return $pageData;
    }
}
