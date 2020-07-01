<?php

/**
 * Discuz & Tencent Cloud
 * This is NOT a freeware, use is subject to license terms
 */

namespace App\Api\Controller\Wechat;

use App\Api\Serializer\OffIAccountAssetSerializer;
use App\Validators\OffIAccountAssetUploadValidator;
use Discuz\Api\Controller\AbstractResourceController;
use Discuz\Auth\AssertPermissionTrait;
use Discuz\Auth\Exception\PermissionDeniedException;
use Discuz\Wechat\EasyWechatTrait;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\Kernel\Support\Collection;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use EasyWeChat\Kernel\Messages\Article;

class OffIAccountAssetUploadController extends AbstractResourceController
{
    use AssertPermissionTrait;
    use EasyWechatTrait;

    /**
     * @var string
     */
    public $serializer = OffIAccountAssetSerializer::class;

    /**
     * @var OffIAccountAssetUploadValidator
     */
    protected $validator;

    /**
     * @var $easyWechat
     */
    protected $easyWechat;

    /**
     * 允许上传的类型
     *
     * @var array
     */
    protected $allowTypes = [];

    /**
     * @param OffIAccountAssetUploadValidator $validator
     */
    public function __construct(OffIAccountAssetUploadValidator $validator)
    {
        $this->validator = $validator;

        $this->easyWechat = $this->offiaccount();
    }

    /**
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return array|Collection|mixed|object|ResponseInterface|string
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws PermissionDeniedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        // 图片（image）、视频（video）、语音（voice）、图文（news）
        $type = Arr::get($request->getQueryParams(), 'type', '');
        $body = $request->getParsedBody();

        $path = '';
        if ($isNews = $type != 'news') {
            $file = Arr::get($request->getUploadedFiles(), 'file');

            // path name
            $path = storage_path('tmp/') . $file->getClientFilename();

            $file->moveTo($path);
        }

        $result = [];
        switch ($type) {
            case 'image': // 图片（image）
                $result = $this->easyWechat->material->uploadImage($path);
                break;
            case 'video': // 视频（video）
                $videoTitle = Arr::get($body, 'video_title', '');
                $videoInfo = Arr::get($body, 'video_info', '');
                $result = $this->easyWechat->material->uploadVideo($path, $videoTitle, $videoInfo);
                break;
            case 'voice': // 语音（voice）
                $result = $this->easyWechat->material->uploadVoice($path);
                break;
            case 'news':  // 图文（news）
                $news = json_decode(Arr::get($body, 'news', []), true);
                /**
                 * @see OffIAccountAssetUploadValidator
                 */
                $this->validator->valid($news);

                // 上传单篇图文
                $article = new Article($news);
                $result = $this->easyWechat->material->uploadArticle($article);
                break;
            case 'lot_of_news':
                // TODO 或者多篇图文
                // $result = $this->easyWechat->material->uploadArticle([$article, $article2, ...]);
                break;
            case 'thumbnail':   // 缩略图
                $result = $this->easyWechat->material->uploadThumb($path);
                break;
        }

        if ($isNews) {
            // remove
            unlink($path);
        }

        return $result;
    }
}
