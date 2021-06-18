<?php

namespace App\Api\Controller\DialogV3;

use App\Commands\Dialog\CreateDialogMessage;
use App\Common\ResponseCode;
use App\Common\Utils;
use App\Providers\DialogMessageServiceProvider;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;

class CreateDialogMessageV2Controller extends DzqController
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Factory
     */
    protected $validation;

    public $providers = [
        DialogMessageServiceProvider::class,
    ];

    public function __construct(Dispatcher $bus, Factory $validation)
    {
        $this->bus = $bus;
        $this->validation = $validation;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        if ($this->user->isGuest()) {
            $this->outPut(ResponseCode::JUMP_TO_LOGIN);
        }
        return $userRepo->canCreateDialog($this->user);
    }

    public function main()
    {
        $user = $this->user;
        $data = $this->request->getParsedBody()->toArray();

        try {
            $this->validation->make($data, [
                'dialogId' => 'required|int',
                'messageText' => 'sometimes|max:450',
                'attachmentId' => 'sometimes|int',
            ])->validate();
        } catch (ValidationException $e) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, $e->validator->getMessageBag()->first());
        }

        try {
            $data = Utils::arrayKeysToSnake($data);
            $this->bus->dispatch(
                new CreateDialogMessage($user, $data)
            );
        } catch (\Exception $e) {
            $this->outPut(ResponseCode::INVALID_PARAMETER, $e->getMessage());
        }

        $this->outPut(ResponseCode::SUCCESS, '已发送');
    }
}
