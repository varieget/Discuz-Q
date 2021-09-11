<?php
namespace App\Api\Controller\ThreadsV3;

use App\Common\ResponseCode;
use App\Models\Setting;
use App\Models\Thread;
use App\Repositories\UserRepository;
use Discuz\Base\DzqAdminController;
use Discuz\Base\DzqCache;
use Discuz\Contracts\Setting\SettingsRepository;

class ThreadOptimizeController extends DzqAdminController
{
    protected $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
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
            $prefix = $db->getTablePrefix();
            $thread = 'threads';
            $threadTom = 'thread_tom';
            if(!empty($prefix)){
                $thread = $prefix."threads";
                $threadTom = $prefix."thread_tom";
            }
            $db->update("update {$thread} set is_display = {$isDisplay} where id in(select thread_id from {$threadTom} where tom_type in(104,106,107)) or price > 0 or attachment_price > 0 or is_anonymous = 1");
            $threadOptimize = Setting::query()->where('key','thread_optimize')->first();
            if($threadOptimize){
                $this->settings->set('thread_optimize', $isDisplay, 'default');
            }else{
                $th = new Setting();
                $th->key = 'thread_optimize';
                $th->value = $isDisplay;
                $th->tag = 'default';
                $th->save();
            }
            DzqCache::clear();
            $this->outPut(ResponseCode::SUCCESS);
        } catch (\Exception $e) {
            $this->info('threadOptimize_error_' . $this->user->id, $e->getMessage());
            $this->outPut(ResponseCode::DB_ERROR, $e->getMessage());
        }
    }
}
