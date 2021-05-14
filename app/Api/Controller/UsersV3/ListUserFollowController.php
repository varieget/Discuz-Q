<?php

namespace App\Api\Controller\UsersV3;

use App\Common\ResponseCode;
use App\Models\GroupUser;
use App\Models\User;
use App\Models\UserFollow;
use App\Repositories\UserFollowRepository;
use App\Repositories\UserRepository;
use Discuz\Base\DzqController;
use Discuz\Http\UrlGenerator;
use Illuminate\Support\Arr;
use Discuz\Auth\AssertPermissionTrait;
use Illuminate\Support\Str;

class ListUserFollowController extends DzqController
{
    use AssertPermissionTrait;

    protected $userFollow;

    public function __construct(UserFollowRepository $userFollow, UrlGenerator $url, UserRepository $user)
    {
        $this->userFollow = $userFollow;
        $this->user = $user;
        $this->url = $url;
    }

    public function main(){
        $filter = $this->inPut('filter');
        $currentPage = $this->inPut('page');
        $perPage = $this->inPut('perPage');
        $sort = $this->inPut('sort');

        $actor = $this->user;
        if (!Arr::get($filter, 'userId')) {
            $this->assertRegistered($actor);
        }

        $UserFollows = $this->filterUserFollow($filter,$currentPage, $perPage,$actor,$sort);
        $userFollowList = $UserFollows['pageData'];
        if(!empty($userFollowList)){
            //1我的关注  2我的粉丝
            $type = (int) Arr::get($filter, 'type', 0);

            if($type==1){
                $userIds = array_unique(array_column($userFollowList, 'to_user_id'));
            }elseif ($type==2){
                $userIds = array_unique(array_column($userFollowList, 'from_user_id'));
            }else{
                $to_user_id = array_unique(array_column($userFollowList, 'to_user_id'));
                $from_user_id = array_unique(array_column($userFollowList, 'from_user_id'));
                $userIds = array_merge($to_user_id,$from_user_id);
            }

            $groups = GroupUser::instance()->getGroupInfo($userIds);
            $groups = array_column($groups, null, 'user_id');
            $users = User::instance()->getUsers($userIds);

            $userFollow = UserFollow::query()->where('from_user_id', $this->user->id)->get()->toArray();
            $userFollow = array_column($userFollow, null, 'to_user_id');

            foreach ($userFollowList as $key => $value) {
                $userFollowList[$key]['isFollow']       = false;
                if (isset($userFollow[$value['to_user_id']])) {
                    $userFollowList[$key]['isFollow']  = true;
                }
            }

            $users = array_column($users, null, 'id');
            foreach ($userFollowList as $list) {
                if($type==1){
                    $userId = $list['to_user_id'];
                }elseif ($type==2){
                    $userId = $list['from_user_id'];
                }else{
                    if($actor->id==$list['from_user_id']){
                        $userId = $list['to_user_id'];
                    }elseif ($actor->id==$list['to_user_id']){
                        $userId = $list['from_user_id'];
                    }
                }

                $user = [];
                if (!empty($users[$userId])) {
                    $user = $this->getUserInfo($users[$userId]);
                }
                $group = [];

                if (!empty($groups[$userId])) {
                    $group = $this->getGroupInfo($groups[$userId]);
                }
                $lists['id'] = $list['id'];
                $lists['fromUserId'] = $list['from_user_id'];
                $lists['toUserId'] = $list['to_user_id'];
                $lists['isMutual'] = $list['is_mutual'];
                $lists['createdAt'] = $list['created_at'];
                $lists['updatedAt'] = $list['updated_at'];
                $lists['isFollow'] = $list['isFollow'];
                $lists['fromUser'] = $list['from_user'];
                $result[] = [
                    'user' => $user,
                    'group' => $group,
                    'userFollow' => $lists,
                ];
            }
            $UserFollows['pageData'] = $result;
        }else{
            $UserFollows = [];
        }
       // dump($UserFollows);die;
        $this->outPut(ResponseCode::SUCCESS, '', $UserFollows);
    }


    public function filterUserFollow($filter, $currentPage, $perPage,User $actor,$sort)
    {
        $join_field = '';
        $user = '';
        $query = $this->userFollow->query()->select('user_follow.*')->distinct();

        $type = (int) Arr::get($filter, 'type', 0);
        $username = Arr::get($filter, 'userName');
        if ($user_id = (int)Arr::get($filter, 'user_id')) {
            $user = $this->user->findOrFail($user_id);
        }
        $user_id = $user ? $user->id : $actor->id;

        if($type>0){
            if ($type == 1) {
                //我的关注
                $query->where('from_user_id', $user_id)->with(['toUser'=>function($query){
                    $query->select('id','username');
                }]);
                $join_field = 'to_user_id';
            } elseif ($type == 2) {
                //我的粉丝
                $query->where('to_user_id', $user_id)->with(['fromUser'=>function($query){
                    $query->select('id','username');
                }]);
                $join_field = 'from_user_id';
            }

            if ($username) {
                $query->join('users', 'users.id', '=', 'user_follow.'.$join_field)
                    ->where(function ($query) use ($username) {
                        $query->where('users.username', 'like', "%{$username}%");
                    });
            }

            if(empty($sort)){
                $sortNew = ['createdAt'=>'desc'];
            }else{
                $sortNew = ["$sort"=>'asc'];
            }

            foreach ((array) $sortNew as $field => $order) {
                if ($field == 'users.createdAt') {
                    // 避免重复 join
                    if (! $username) {
                        $query->join('users', 'users.id', '=', 'user_follow.'.$join_field);
                    }
                    $query->addSelect('users.created_at');
                }
                $query->orderBy(Str::snake($field), $order);
            }
        }else{
            $query->where(function ($query) use ($user_id) {
                $query->where('from_user_id', $user_id)
                    ->orWhere('to_user_id', $user_id);
            })->with(['fromUser'=>function($query){
                $query->select('id','username');
            }]);

            if ($username) {
                $query->leftJoin('users', 'users.id', '=', 'user_follow.to_user_id')
                    ->leftJoin('users as b', 'b.id', '=', 'user_follow.from_user_id')
                    ->where(function ($query) use ($username) {
                        $query->where('users.username', 'like', "%{$username}%");
                    })
                    ->orWhere(function ($query) use ($username) {
                        $query->where('b.username', 'like', "%{$username}%");
                    });
            }
            $query->where(function ($query) use ($user_id) {
                $query->where('from_user_id', $user_id)
                    ->orWhere('to_user_id', $user_id);
            });
            if(empty($sort)){
                $sortNew = ['createdAt'=>'desc'];
            }else{
                $sortNew = ["$sort"=>'asc'];
            }
            foreach ((array) $sortNew as $field => $order) {
                if ($field == 'users.createdAt') {
                    // 避免重复 join
                    if (! $username) {
                        $query->leftJoin('users', 'users.id', '=', 'user_follow.to_user_id')
                            ->leftJoin('users as b', 'b.id', '=', 'user_follow.from_user_id');
                    }
                    $query->addSelect('users.created_at');
                }
                $query->orderBy(Str::snake($field), $order);
            }
        }
        $query = $this->pagination($currentPage, $perPage, $query);
        return $query;
    }

    private function getUserInfo($user)
    {
        return [
            'pid' => $user['id'],
            'userName' => $user['username'],
            'avatar' => $user['avatar'],
        ];
    }

    private function getGroupInfo($group)
    {
        return [
            'pid' => $group['group_id'],
            'groupName' => $group['groups']['name'],
            'groupIcon' => $group['groups']['icon']
        ];
    }
}
