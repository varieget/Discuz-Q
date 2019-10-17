<?php

/**
 *      Discuz & Tencent Cloud
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: Post.php xxx 2019-10-08 16:21 LiuDongdong $
 */

namespace App\Models;

use App\Events\Post\Created;
use Carbon\Carbon;
use Discuz\Foundation\EventGeneratorTrait;
use Discuz\Database\ScopeVisibilityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $thread_id
 * @property string $content
 * @property string $ip
 * @property int $comment_count
 * @property int $like_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property bool $is_first
 * @property bool $is_approved
 * @package App\Models
 */
class Post extends Model
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;
    use SoftDeletes;

    /**
     * Create a new instance in reply to a thread.
     *
     * @param $threadId
     * @param string $content
     * @param int $userId
     * @param $ip
     * @param int $isFirst
     * @return static
     */
    public static function reply($threadId, $content, $userId, $ip, $isFirst = 0)
    {
        $post = new static;

        $post->created_at = Carbon::now();
        $post->thread_id = $threadId;
        $post->user_id = $userId;
        $post->ip = $ip;
        $post->is_first = $isFirst;

        // Set content last, as the parsing may rely on other post attributes.
        $post->content = $content;

        $post->raise(new Created($post));

        return $post;
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }
}
