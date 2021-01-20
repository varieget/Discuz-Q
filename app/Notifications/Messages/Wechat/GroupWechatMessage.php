<?php

namespace App\Notifications\Messages\Wechat;

use Discuz\Notifications\Messages\SimpleMessage;
use Illuminate\Contracts\Routing\UrlGenerator;

/**
 * 用户角色调整通知 - 微信
 */
class GroupWechatMessage extends SimpleMessage
{
    public $tplId = 24;

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
        [$firstData, $user, $data] = $parameters;
        // set parent tpl data
        $this->firstData = $firstData;
        $this->user = $user;
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
        /**
         * 设置父类 模板数据
         *
         * @parem $user_name 被更改人的用户名
         * @parem $group_original 原用户组名
         * @parem $group_change 新用户组名
         */
        $this->setTemplateData([
            '{$user_name}' => $this->user->username,
            '{$group_original}' => $data['old']->pluck('name')->join('、'),
            '{$group_change}' => $data['new']->pluck('name')->join('、'),
        ]);

        // build data
        $build = $this->compiledArray();

        // redirect_url
        $redirectUrl = '';
        if (!empty($this->firstData->redirect_url)) {
            $redirectUrl = $this->firstData->redirect_url;
        }
        $build['redirect_url'] = $this->url->to($redirectUrl);

        return $build;
    }

}
