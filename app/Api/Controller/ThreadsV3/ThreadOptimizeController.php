<?php
namespace App\Api\Controller\ThreadsV3;

use App\Common\ResponseCode;
use App\Models\Thread;
use App\Models\ThreadTom;
use App\Repositories\UserRepository;
use Discuz\Base\DzqCache;
use Discuz\Base\DzqController;
use App\Modules\ThreadTom\TomConfig;
use Illuminate\Support\Facades\DB;
use MongoDB\Driver\Query;

class ThreadOptimizeController extends DzqController
{
    protected function checkRequestPermissions(UserRepository $userRepo)
    {
        return $this->user->isAdmin();
    }

    public function main()
    {
        $isDisplay = $this->inPut('isDisplay');
        if(empty($isDisplay) && $isDisplay !== 0){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }
        if(!in_array($isDisplay,array(Thread::BOOL_YES,Thread::BOOL_NO))){
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }
        try {
            $db = $this->getDB();
            $db->update("update threads set is_display = $isDisplay where id in(select thread_id from thread_tom where tom_type in(104,106,107)) or price > 0 or attachment_price > 0 or is_anonymous = 1");
            DzqCache::clear();
            $this->outPut(ResponseCode::SUCCESS);
        } catch (\Exception $e) {
            $this->info('threadOptimize_error_' . $this->user->id, $e->getMessage());
            $this->outPut(ResponseCode::DB_ERROR, $e->getMessage());
        }
    }
}
