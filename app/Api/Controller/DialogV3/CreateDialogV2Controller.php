<?php

namespace App\Api\Controller\DialogV3;

use App\Commands\Dialog\CreateDialog;
use App\Common\ResponseCode;
use App\Providers\DialogMessageServiceProvider;
use Discuz\Base\DzqController;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;

class CreateDialogV2Controller extends DzqController
{
    protected $validation;

    /**
     * @var Dispatcher
     */
    protected $bus;

    public $providers = [
        DialogMessageServiceProvider::class,
    ];

    public function __construct(Dispatcher $bus, Factory $validation)
    {
        $this->validation = $validation;
        $this->bus = $bus;
    }

    public function main()
    {
        $actor = $this->user;
        $data = [
            'message_text'=>$this->inPut('messageText'),
            'recipient_username'=>$this->inPut('recipientUsername'),
        ];

        if(empty($data['message_text'])){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }
        if(empty($data['recipient_username'])){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }
        if(!empty($this->inPut('imageUrl'))){
            $data['image_url'] = $this->inPut('imageUrl');
        }
        if(!empty($this->inPut('attachmentId'))){
            $data['attachment_id'] = $this->inPut('attachmentId');
        }

        try {
            $this->validation->make($data, [
                'message_text' => 'required_without:messageText|max:450',
            ])->validate();
        } catch (ValidationException $e) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, $e->validator->getMessageBag()->first());
        }

        try {
          $res = $this->bus->dispatch(
                new CreateDialog($actor, $data)
            );
        } catch (\Exception $e) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, $e->getMessage());
        }

        $res = $res->toArray();
       
        $data = [
            'dialogId' =>$res['id'],
        ];

        $this->outPut(ResponseCode::SUCCESS, '已发送', $data);
    }
}
