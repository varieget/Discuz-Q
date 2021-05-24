<?php


namespace App\Api\Controller\UsersV3;

use App\Common\ResponseCode;
use App\Events\Users\UserFollowCreated;
use App\Models\User;
use App\Models\UserFollow;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Discuz\Foundation\EventsDispatchTrait;
use Illuminate\Contracts\Events\Dispatcher;

class CreateUserFollowController extends DzqController
{
    use EventsDispatchTrait;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var UserFollow 
     */
    protected $userFollow;

    public function __construct(Dispatcher $bus,UserFollow $userFollow)
    {
        $this->bus = $bus;
        $this->userFollow = $userFollow;
    }

    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return !$this->user->isGuest() && $userRepo->canFollowUser($this->user);
    }

    public function main(){

        $actor = $this->user;

        $to_user_id = $this->inPut('toUserId');

        if ($actor->id == $to_user_id) {
            $this->outPut(ResponseCode::INVALID_PARAMETER,'');
        }

        /** @var User $toUser */
        $toUser = User::query()
            ->where('id', $to_user_id)
            ->first();
        if (!$toUser) {
            $this->outPut(ResponseCode::INVALID_PARAMETER,'');
        }

        //在黑名单中，不能创建会话
        if (in_array($actor->id, array_column($toUser->deny->toArray(), 'id'))) {
            $this->outPut(ResponseCode::UNAUTHORIZED,'');
        }

        //判断是否已经关注
        $toFromUserFollow = $this->userFollow->where(['to_user_id'=>$to_user_id,'from_user_id'=>$actor->id])->first();
        if($toFromUserFollow){
            $this->outPut(ResponseCode::RESOURCE_EXIST,'');
        }

        //判断是否需要设置互相关注
        $toUserFollow = $this->userFollow->where(['from_user_id'=>$to_user_id,'to_user_id'=>$actor->id])->first();
        $is_mutual = UserFollow::NOT_MUTUAL;
        if ($toUserFollow) {
            $is_mutual = UserFollow::MUTUAL;
            $toUserFollow->is_mutual = $is_mutual;
            $toUserFollow->save();
        }

        $userFollow = $this->userFollow->firstOrCreate(
            ['from_user_id'=>$actor->id,'to_user_id'=>$to_user_id],
            ['is_mutual'=>$is_mutual]
        );
        $userFollows['id'] = $userFollow['id'];
        $userFollows['fromUserId'] = $userFollow['from_user_id'];
        $userFollows['toUserId'] = $userFollow['to_user_id'];
        $userFollows['isMutual'] = $userFollow['is_mutual'];
        $userFollows['updatedAt'] = $userFollow['updated_at'];
        $userFollows['createdAt'] = $userFollow['created_at'];

        $this->bus->dispatch(
            new UserFollowCreated($actor, $toUser)
        );

        return $this->outPut(ResponseCode::SUCCESS,'', $userFollows);
    }
}
