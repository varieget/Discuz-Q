<?php

namespace App\Notifications\Messages\Wechat;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Discuz\Notifications\Messages\SimpleMessage;
use Illuminate\Contracts\Routing\UrlGenerator;

class RelatedWechatMessage extends SimpleMessage
{
    /**
     * @var Post $post
     */
    protected $post;

    /**
     * @var User $actor
     */
    protected $actor;

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
        [$firstData, $actor, $post] = $parameters;
        // set parent tpl data
        $this->firstData = $firstData;

        $this->actor = $actor;
        $this->post = $post;

        $this->template();
    }

    public function template()
    {
        return ['content' => $this->getWechatContent()];
    }

    protected function titleReplaceVars()
    {
        return [];
    }

    public function contentReplaceVars($data)
    {
        $content = $this->post->getSummaryContent(Post::NOTICE_LENGTH, true);
        $postContent = $content['content']; // 去除@样式的 html 标签
        $threadTitle = $this->post->thread->getContentByType(Thread::CONTENT_LENGTH, true);
        $threadPostContent = $content['first_content'];

        /**
         * 设置父类 模板数据
         * @parem $user_name 发送人姓名
         * @parem $post_content @源帖子内容
         * @parem $thread_title 主题标题/首贴内容 (如果有title是title，没有则是首帖内容)
         * @parem $thread_post_content 首贴内容
         */
        $this->setTemplateData([
            '{$user_name}' => $this->actor->username,
            '{$post_content}' => $this->strWords($postContent),
            '{$thread_title}' => $this->strWords($threadTitle),
            '{$thread_post_content}' => $this->strWords($threadPostContent),
        ]);

        // build data
        $build = $this->compiledArray();

        // redirect_url TODO 判断 $replyPostId 是否是楼中楼 跳转楼中楼详情页
        $replyPostId = $this->post->reply_post_id;  // 楼中楼时不为 0
        $redirectUrl = '/topic/index?id=' . $this->post->thread_id;
        if (! empty($this->firstData->redirect_url)) {
            $redirectUrl = $this->firstData->redirect_url;
        }
        $build['redirect_url'] = $this->url->to($redirectUrl);

        return $build;
    }

}
