<?php

/**
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Api\Serializer;

use App\Models\NotificationTpl;
use Discuz\Api\Serializer\AbstractSerializer;

class NotificationTplSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'notification_tpls';

    /**
     * @var string[] 禁止修改的通知，只能设置开启关闭
     */
    protected $disabledTypeName = [
        '内容回复通知',
        '内容点赞通知',
        '内容支付通知',
        '内容@通知',
        '提现通知',
        '提现失败通知',
        '分成收入通知',
        '问答提问通知',
        '问答回答通知',
        '过期通知',
    ];

    /**
     * @param NotificationTpl $model
     * @return array
     */
    protected function getDefaultAttributes($model)
    {
        return [
            'id'                => $model->id,
            'status'            => $model->status,
            'type'              => $model->type,
            'type_name'         => $model->type_name,
            'title'             => $model->title,
            'content'           => $model->content,
            'vars'              => unserialize($model->vars),
            'template_id'       => $model->template_id,
            'first_data'        => $model->first_data,
            'keywords_data'     => $model->keywords_data ? explode(',', $model->keywords_data) : [],
            'remark_data'       => $model->remark_data,
            'color'             => $model->color ?: [],
            'redirect_type'     => (int) $model->redirect_type,
            'redirect_url'      => (string) $model->redirect_url,
            'page_path'         => (string) $model->page_path,
            'disabled'          => $model->type === NotificationTpl::SYSTEM_NOTICE && in_array($model->type_name, $this->disabledTypeName),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getId($model)
    {
        return $model->type;
    }
}
