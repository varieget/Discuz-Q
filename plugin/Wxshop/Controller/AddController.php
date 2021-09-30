<?php


namespace Plugin\Wxshop\Controller;


use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Plugin\Activity\Controller\WxshopTrait;

class AddController extends DzqController
{
    use WxshopTrait;
    private $activity = null;

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return $this->checkPermission($userRepo,true);
    }

    public function main()
    {

    }
}
