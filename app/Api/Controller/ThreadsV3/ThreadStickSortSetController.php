<?php
namespace App\Api\Controller\ThreadsV3;

use App\Common\ResponseCode;
use App\Models\Thread;
use App\Models\ThreadStickSort;
use Discuz\Base\DzqAdminController;

class ThreadStickSortSetController extends DzqAdminController
{
    public function main()
    {
        $data = $this->inPut('data');
        if(empty($data)) {
            $this->outPut(ResponseCode::INVALID_PARAMETER);
        }

        $db = $this->getDB();
        $db->beginTransaction();
        try {
            $resultData = [];
            foreach ($data as $value) {
                $this->dzqValidate($value, [
                    'id' => 'required|integer',
                    'sort' => 'required|integer'
                ]);
                $threads = $this->getStickThreadsBuild();
                $threadStick = $threads->where('id', $value['id'])->first();
                if (!empty($threadStick)) {
                    $stickSort = ThreadStickSort::query()->where('thread_id', $value['id'])->first();
                    if (!empty($stickSort)) {
                        if (isset($value['sort'])) {
                            $stickSort->sort = $value['sort'];
                            $stickSort->save();
                        }
                    } else {
                        $stickSort = new ThreadStickSort();
                        if (isset($value['sort'])) {
                            $stickSort->thread_id = $value['id'];
                            $stickSort->sort = $value['sort'];
                            $stickSort->save();
                        }
                    }
                    $resultData[] = $stickSort;
                }
            }
            //检查置顶数据完整性
            $threadIds = $this->getStickThreadsBuild()->get()->pluck('id')->toArray();
            $threadSortIds = ThreadStickSort::query()->get()->pluck('thread_id')->toArray();

            $diffIds=array_diff($threadIds,$threadSortIds);
            if(!empty($diffIds)){
                $insertData = [];
                foreach ($diffIds as $v){
                    $add['thread_id'] =  $v;
                    $add['sort'] =  0;
                    $insertData[] = $add;
                }
                ThreadStickSort::query()->insert($insertData);
            }
            $db->commit();
        }catch (\Exception $e) {
            $db->rollBack();
            $this->outPut(ResponseCode::DB_ERROR, '置顶排序设置失败');
            $this->info('置顶排序设置失败：' . $e->getMessage());
        }
        $data = $this->camelData($resultData);
        $this->outPut(ResponseCode::SUCCESS, '', $data);
    }

    protected function getStickThreadsBuild(){
       return Thread::query()
            ->where('is_sticky', Thread::BOOL_YES)
            ->whereNull('deleted_at')
            ->whereNotNull("user_id")
            ->where('is_draft', Thread::BOOL_NO)
            ->where('is_display', Thread::BOOL_YES)
            ->where('is_approved', Thread::BOOL_YES);
    }
}
