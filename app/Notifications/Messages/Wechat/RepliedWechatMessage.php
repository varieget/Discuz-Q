<?php

namespace App\Notifications\Messages\Wechat;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Discuz\Notifications\Messages\SimpleMessage;
use Illuminate\Contracts\Routing\UrlGenerator;

/**
 * 回复通知 - 微信
 * Class RepliedWechatMessage
 *
 * @package App\Notifications\Messages\Wechat
 */
class RepliedWechatMessage extends SimpleMessage
{
    /**
     * @var Post $post
     */
    protected $post;

    /**
     * @var User $user
     */
    protected $user;

    protected $data;

    /**
     * @var UrlGenerator
     */
    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    public function setData(...$parameters)
    {
        [$firstData, $user, $post, $data] = $parameters;
        // set parent tpl data
        $this->firstData = $firstData;

        $this->user = $user;
        $this->post = $post;
        $this->data = $data;

        $this->template();
    }

    public function template()
    {
        return ['content' => $this->getWechatContent($this->data)];
    }

    protected function titleReplaceVars()
    {
        return [];
    }

    public function contentReplaceVars($data)
    {
        $content = $this->post->getSummaryContent(Post::NOTICE_LENGTH, true);
        $postContent = $content['content'];                                                 // 回复内容
        $threadTitle = $this->post->thread->getContentByType(Thread::CONTENT_LENGTH, true); // 主题标题/首贴内容
        $threadPostContent = $content['first_content'];                                     // 首贴内容

        // 根据触发通知类型，变量的获取形式不同
        switch ($this->data['notify_type']) {
            case 'notify_thread':
                // 通知主题作者
                $subject = $threadPostContent;
                break;
            case 'notify_reply_post':
                // 通知被回复的人 （楼中楼）
                $subject = $this->post->replyPost->formatContent(); // 解析 content
                break;
            case 'notify_approved':
                // 审核通过后 发送回复人的主题通知
                $subject = $threadTitle;
                $userName = $this->post->user->username;
                break;
        }

        /**
         * 设置父类 模板数据
         * @parem $user_name 回复人的用户名
         * @parem $post_content 回复内容
         * @parem $reply_post 被回复内容
         * @parem $thread_id 主题ID
         * @parem $thread_title 主题标题/首贴内容 (如果有title是title，没有则是首帖内容)
         * @parem $thread_post_content 首贴内容
         */
        $this->setTemplateData([
            '{$user_name}'           => $userName ?? $this->user->username,
            '{$post_content}'        => $this->strWords($postContent),
            '{$reply_post}'          => $this->strWords($subject ?? ''),
            '{$thread_id}'           => $this->post->thread_id,
            '{$thread_title}'        => $this->strWords($threadTitle),
            '{$thread_post_content}' => $this->strWords($threadPostContent),
        ]);

        // redirect_url TODO 判断 $replyPostId 是否是楼中楼 跳转楼中楼详情页
        $replyPostId = $this->post->reply_post_id;                                          // 楼中楼时不为 0

        // build data
        $expand = [
            'redirect_url' => $this->url->to('/topic/index?id=' . $this->post->thread_id),
        ];

        return $this->compiledArray($expand);
    }

}
