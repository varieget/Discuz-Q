<?php

namespace App\Import;

use App\Api\Controller\AttachmentV3\AttachmentTrait;
use App\Censor\Censor;
use App\Commands\Attachment\AttachmentUploader;
use App\Commands\Users\RegisterCrawlerUser as RegisterUser;
use App\Commands\Users\UploadCrawlerAvatar;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadTag;
use App\Models\ThreadTom;
use App\Models\ThreadTopic;
use App\Models\ThreadVideo;
use App\Models\Topic;
use App\Models\User;
use App\Repositories\UserRepository;
use App\User\CrawlerAvatarUploader;
use App\Validators\AttachmentValidator;
use App\Validators\AvatarValidator;
use App\Validators\UserValidator;
use App\Traits\VideoCloudTrait;
use Carbon\Carbon;
use Discuz\Auth\Guest;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Filesystem\Factory as FactoryFilesystem;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Laminas\Diactoros\UploadedFile as RequestUploadedFile;

trait ImportDataTrait
{
    use ImportLockFileTrait;
    use VideoCloudTrait;
    use AttachmentTrait;

    protected $userRepo;
    protected $bus;
    protected $settings;
    protected $censor;
    protected $userValidator;
    protected $avatarValidator;
    protected $crawlerAvatarUploader;
    protected $attachmentValidator;
    protected $uploader;
    protected $image;
    protected $db;
    protected $filesystem;
    protected $events;
    private $categoryId;
    private $topic;
    private $startCrawlerTime;
    private $lockPath;
    private $crawlerPlatform;
    private $cookie;
    private $userAgent;

    public function __construct(
        UserRepository $userRepo,
        Dispatcher $bus,
        SettingsRepository $settings,
        Events $events,
        Censor $censor,
        UserValidator $userValidator,
        AvatarValidator $avatarValidator,
        AttachmentValidator $attachmentValidator,
        ImageManager $image,
        ConnectionInterface $db,
        Filesystem $filesystem)
    {
        $this->userRepo = $userRepo;
        $this->bus = $bus;
        $this->settings = $settings;
        $this->events = $events;
        $this->censor = $censor;
        $this->userValidator = $userValidator;
        $this->avatarValidator = $avatarValidator;
        $this->attachmentValidator = $attachmentValidator;
        $this->image = $image;
        $this->db = $db;
        $this->filesystem = $filesystem;
        $this->uploader = new AttachmentUploader($this->filesystem, $this->settings);
        $this->crawlerAvatarUploader = new CrawlerAvatarUploader($this->censor, $this->filesystem, $this->settings);
        $publicPath = public_path();
        $this->lockPath = $publicPath . DIRECTORY_SEPARATOR . 'importDataLock.conf';
        parent::__construct();
    }

    public function insertCrawlerData($topic, $categoryId, $data)
    {

        if (empty($data)) {
            throw new \Exception('未接收到相关数据！');
        }

        $this->topic = $topic;
        $this->categoryId = $categoryId;

        $oldUserData = User::query()->select('id', 'username', 'nickname')->get()->toArray();
        $oldUsernameData = array_column($oldUserData, null, 'username');
        $oldNicknameData = array_column($oldUserData, 'nickname');
        $oldTopics = Topic::query()->select('id', 'user_id', 'content', 'thread_count', 'view_count')->get()->toArray();
        $oldTopics = array_column($oldTopics, null, 'content');
        try {
            $this->db->beginTransaction();

            if (!isset($data['user']) || !isset($data['forum'])) {
                throw new \Exception('数据格式有误');
            }

            [$oldUsernameData, $oldNicknameData, $userData] = $this->insertUser($oldUsernameData, $oldNicknameData, $data['user']);
            $threadUserId = $userData->id;

            $newThread = $this->insertThread($data['forum'], $threadUserId);
            $threadId = $newThread->id;
            $threadContent = $this->changeThreadContent($oldTopics, $data['forum'], $threadUserId, $threadId);
            $this->insertContent($threadId, $threadUserId, $threadContent, Post::FIRST_YES, $newThread->created_at);

            if (isset($value['comment']) && !empty($value['comment'])) {
                $postNumber = $this->insertPosts($value['comment'], $oldUsernameData, $oldNicknameData, $threadId);
            }

            $this->db->commit();

            $newThread->is_draft = Thread::BOOL_NO;
            if (isset($postNumber)) {
                $newThread->post_count = $newThread->post_count + $postNumber;
                $newThread->view_count = $newThread->view_count + $postNumber;
            }
            $newThread->save();
            $userData->thread_count = $userData->thread_count + 1;
            $userData->save();
            return $newThread->id;
        } catch (\Exception $e) {
            $this->db->rollback();
            Category::refreshThreadCountV3($this->categoryId);
            app('cache')->clear();
            $this->changeLockFileContent($this->lockPath, 0, Thread::PROCESS_OF_START_INSERT_CRAWLER_DATA, Thread::IMPORT_ABNORMAL_ENDING, $this->topic, 0);
            throw new \Exception("数据导入失败：" . $e->getMessage());
        }
    }

    private function insertUser($oldUsernameData, $oldNicknameData, $user)
    {
        if (!isset($user['nickname'])) {
            throw new \Exception('数据格式有误');
        }

        $username = 'robotdzq_' . $user['nickname'];
        if (isset($oldUsernameData[$username])) {
            $userData = User::query()->where('id', $oldUsernameData[$username]['id'])->first();
            return [$oldUsernameData, $oldNicknameData, $userData];
        }

        if (in_array($user['nickname'], $oldNicknameData)) {
            $user['nickname'] = User::addStringToNickname($user['nickname']);
        }

        $randomNumber = mt_rand(111111, 999999);
        $password = $user['nickname'] . $randomNumber;
        $data = [
            'username' => $username,
            'nickname' => $user['nickname'],
            'password' => $password,
            'passwordConfirmation' => $password,
            'dataType' => 'crawler'
        ];
        $newGuest = new Guest();
        $register = new RegisterUser($newGuest, $data);
        $registerUserResult = $register->handle($this->events, $this->censor, $this->settings, $this->userValidator);
        if (isset($user['avatar']) && !empty($user['avatar'])) {
            $this->uploadCrawlerUserAvatar($user['avatar'], $registerUserResult);
        }

        if ($registerUserResult) {
            $registerUserResult->status = User::STATUS_NORMAL;
            $registerUserResult->save();
        }

        $oldUsernameData = array_merge($oldUsernameData, [$registerUserResult->username => [
            'id' => $registerUserResult->id,
            'username' => $registerUserResult->username,
            'nickname' => $registerUserResult->nickname
        ]]);
        $oldNicknameData = array_merge($oldNicknameData, [$registerUserResult->nickname => [
            'id' => $registerUserResult->id,
            'username' => $registerUserResult->username,
            'nickname' => $registerUserResult->nickname
        ]]);

        return [$oldUsernameData, $oldNicknameData, $registerUserResult];

    }

    private function uploadCrawlerUserAvatar($avatar, $registerUserData)
    {
        $mimeType = $this->getAttachmentMimeType($avatar);
        $fileExt = substr($mimeType, strpos($mimeType, "/") + strlen("/"));
        if (!in_array($fileExt, ['gif', 'png', 'jpg', 'jpeg', 'jpe', 'heic'])) {
            return false;
        }

        $fileName = Str::random(40) . '.' . $fileExt;
        set_time_limit(0);
        $file = $this->getFileContents($avatar);
        if (!$file) {
            return false;
        }

        $tmpFile = tempnam(storage_path('/tmp'), 'avatar');
        $fileExt = $fileExt ? ".$fileExt" : '';
        $tmpFileWithExt = $tmpFile . $fileExt;
        $avatarSize = @file_put_contents($tmpFileWithExt, $file);
        $avatarFile = new RequestUploadedFile(
            $tmpFile,
            $avatarSize,
            0,
            $fileName,
            $mimeType
        );

        $avatar = new UploadCrawlerAvatar($registerUserData->id, $avatarFile, $registerUserData, $tmpFile);
        $uploadAvatarResult = $avatar->handle($this->userRepo, $this->crawlerAvatarUploader, $this->avatarValidator);
        return $uploadAvatarResult;
    }

    private function insertThread($forumData, $threadUserId)
    {
        $createdAt = strtotime($forumData['createdAt']) ? $forumData['createdAt'] : Carbon::now();
        $newThread = new Thread();
        $newThread->user_id = $threadUserId;
        $newThread->category_id = $this->categoryId;
        $newThread->title = $forumData['text']['title'] ?? '';
        $newThread->type = Thread::TYPE_OF_ALL;
        $newThread->post_count = 1;
        $newThread->share_count = mt_rand(0, 100);
        $newThread->view_count = mt_rand(0, 100);
        $newThread->address = $newThread->location = $forumData['text']['position'] ?? '';
        $newThread->is_draft = Thread::BOOL_YES;
        $newThread->is_approved = Thread::BOOL_YES;
        $newThread->is_anonymous = Thread::BOOL_NO;
        $newThread->created_at = $newThread->updated_at = $createdAt;
        $newThread->source = Thread::DATA_PLATFORM_OF_IMPORT;
        $newThread->save();
        return $newThread;
    }

    private function changeThreadContent($oldTopics, $data, $userId, $threadId)
    {
        if (!isset($data['text']['text']) || empty($data['text']['text'])) {
            throw new \Exception('数据格式有误');
        }

        $content = $data['text']['text'];
        $topicIds = [];
        $imageIds = [];
        $attachmentIds = [];
        $videoId = 0;
        $audioId = 0;

        $content = $this->changeContentImg($content, $userId, Attachment::TYPE_OF_IMAGE);
        if (isset($data['text']['topicList']) && !empty($data['text']['topicList'])) {
            [$content, $topicIds] = $this->insertTopics($oldTopics, $content, $userId, $data['text']['topicList']);
        }

        if (isset($data['images']) && !empty($data['images'])) {
            $imageIds = $this->insertImages($userId, $data['images'], Attachment::TYPE_OF_IMAGE);
            $imageIds = array_column($imageIds, 'id');
        }

        if (isset($data['attachments']) && !empty($data['attachments'])) {
            $attachmentIds = $this->insertImages($userId, $data['attachments'], Attachment::TYPE_OF_FILE);
            $attachmentIds = array_column($attachmentIds, 'id');
        }

        if (isset($data['media'])) {
            if (isset($data['media']['video']) && !empty($data['media']['video'])) {
                $newVideoData = $this->insertMedia($data['media']['video'], ThreadVideo::TYPE_OF_VIDEO, $userId, $threadId);
                $videoId = $newVideoData['videoId'] ?? 0;
            }
            if (isset($data['media']['audio']) && !empty($data['media']['audio'])) {
                $newAudioData = $this->insertMedia($data['media']['audio'], ThreadVideo::TYPE_OF_AUDIO, $userId, $threadId);
                $audioId = $newAudioData['videoId'] ?? 0;
            }
        }

        if (isset($data['contentMedia']['videos']) && !empty($data['contentMedia']['videos'])) {
            $content = $this->changeContentMedia($content, $userId, $threadId, ThreadVideo::TYPE_OF_VIDEO, $data['contentMedia']['videos']);
        }
        if (isset($data['contentMedia']['audio']) && !empty($data['contentMedia']['audio'])) {
            $content = $this->changeContentMedia($content, $userId, $threadId, ThreadVideo::TYPE_OF_AUDIO, $data['contentMedia']['audio']);
        }


        // 写入对应关联关系
        if (!empty($topicIds)) {
            $this->insertThreadTopics($threadId, $topicIds);
        }
        $this->insertTom($threadId, $imageIds, $attachmentIds, $videoId, $audioId);

        return $content;
    }

    private function changeContentImg($content, $userId, $type)
    {
        $postPicturesSrc = $this->getImagesSrc($content);
        $insertImagesResult = $this->insertImages($userId, $postPicturesSrc, $type);
        if (!empty($insertImagesResult)) {
            foreach ($insertImagesResult as $value) {
                if (in_array($value['oldImageSrc'], $postPicturesSrc)) {
                    $content = str_replace($value['oldImageSrc'] . '"', $value['newImageSrc'] . '" alt="attachmentId-' . $value['id'] . '" ', $content);
                }
            }
        }

        return $content;
    }

    private function changeContentMedia($content, $userId, $threadId, $type, $mediaUrl)
    {
        foreach ($mediaUrl as $value) {
            $data = $this->insertMedia($value, $type, $userId, $threadId);
            $videoId = $data['videoId'];
            if ($videoId) {
                $content = str_replace($value . '"', '" alt="videoId-' . $videoId . '" ', $content);
            }
        }
        return $content;
    }

    private function getImagesSrc($content)
    {
        $imgSrcArr = [];
        //首先将富文本字符串中的 img 标签进行匹配
        $pattern_imgTag = '/<img\b.*?(?:\>|\/>)/i';
        preg_match_all($pattern_imgTag, $content, $matchIMG);
        if (isset($matchIMG[0])) {
            foreach ($matchIMG[0] as $key => $imgTag) {
                //进一步提取 img标签中的 src属性信息
                $pattern_src = '/\bsrc\b\s*=\s*[\'\"]?([^\'\"]*)[\'\"]?/i';
                preg_match_all($pattern_src, $imgTag, $matchSrc);
                if (isset($matchSrc[1])) {
                    foreach ($matchSrc[1] as $src) {
                        //将匹配到的src信息压入数组
                        $imgSrcArr[] = $src;
                    }
                }
            }
        }
        return $imgSrcArr;
    }

    private function insertImages($userId, $imagesSrc, $type)
    {
        $imageIds = [];
        $actor = User::query()->where('id', $userId)->first();
        $ipAddress = '';
        foreach ($imagesSrc as $key => $value) {
            set_time_limit(0);
            $mimeType = $this->getAttachmentMimeType($value);
            $fileExt = substr($mimeType, strpos($mimeType, "/") + strlen("/"));
            $fileName = Str::random(40) . '.' . $fileExt;
            $file = $this->getFileContents($value);
            $imageSize = strlen($file);
            $maxSize = $this->settings->get('support_max_size', 'default', 0) * 1024 * 1024;
            if ($file && $imageSize > 0 && $imageSize < $maxSize) {
                ini_set('memory_limit', -1);
                $tmpFile = tempnam(storage_path('/tmp'), 'attachment');
                $ext = $fileExt ? ".$fileExt" : '';
                $tmpFileWithExt = $tmpFile . $ext;
                $putResult = @file_put_contents($tmpFileWithExt, $file);
                if (!$putResult) {
                    return false;
                }

                $imageFile = new UploadedFile(
                    $tmpFileWithExt,
                    $fileName,
                    $mimeType,
                    0,
                    true
                );

                if (strtolower($ext) != 'gif') {
                    if ((int)$type === Attachment::TYPE_OF_IMAGE && extension_loaded('exif')) {
                        $this->image->make($tmpFileWithExt)->orientate()->save();
                    }
                }

                // 上传
                $this->uploader->uploadCrawlerData($imageFile, $type);
                list($width, $height) = getimagesize($tmpFileWithExt);
                $attachment = Attachment::build(
                    $actor->id,
                    $type,
                    $this->uploader->fileName,
                    $this->uploader->getPath(),
                    $imageFile->getClientOriginalName(),
                    $imageFile->getSize(),
                    $imageFile->getClientMimeType(),
                    $this->settings->get('qcloud_cos', 'qcloud') ? 1 : 0,
                    Attachment::APPROVED,
                    $ipAddress,
                    0,
                    $width ?: 0,
                    $height ?: 0
                );

                $attachment->save();
                @unlink($tmpFile);
                @unlink($tmpFileWithExt);

                if ($attachment->is_remote) {
                    $url = $this->settings->get('qcloud_cos_sign_url', 'qcloud', false)
                        ? app()->make(FactoryFilesystem::class)->disk('attachment_cos')->temporaryUrl($attachment->full_path, Carbon::now()->addDay())
                        : app()->make(FactoryFilesystem::class)->disk('attachment_cos')->url($attachment->full_path);
                } else {
                    $url = app()->make(FactoryFilesystem::class)->disk('attachment')->url($attachment->full_path);
                }

                $imageIds[] = [
                    'id' => $attachment->id,
                    'oldImageSrc' => $value,
                    'newImageSrc' => $url
                ];

            }
        }

        return $imageIds;
    }

    private function insertTopics($oldTopics, $content, $userId, $topicList)
    {
        $topicIds = [];
        foreach ($topicList as $key => $value) {
            if (isset($oldTopics[$value])) {
                $topicIds[] = $oldTopics[$value]['id'];
                $html = sprintf('<span id="topic" value="%s">#%s#</span>', $oldTopics[$value]['id'], $value);
                $topicContent = $oldTopics[$value]['content'];
            } else {
                $newTopic = new Topic();
                $newTopic->user_id = $userId;
                $newTopic->content = $value;
                $newTopic->created_at = $newTopic->updated_at = Carbon::now();
                $newTopic->save();
                $topicIds[] = $newTopic->id;
                $html = sprintf('<span id="topic" value="%s">#%s#</span>', $newTopic->id, $newTopic->content);
                $topicContent = $newTopic->content;
                $oldTopics = array_merge($oldTopics, [
                    $newTopic->content => [
                        'id' => $newTopic->id,
                        'user_id' => $newTopic->user_id,
                        'content' => $newTopic->content
                    ]
                ]);
            }

            if (!strpos($content, $html)) {
                $searchTopicContent = '#' . $topicContent . '#';
                $content = str_replace($searchTopicContent, $html, $content);
            }
        }

        return [$content, $topicIds];
    }

    private function insertMedia($mediaUrl, $type, $userId, $threadId)
    {
        $newVideoData = [];
        if (!empty($mediaUrl)) {
            if ($type == ThreadVideo::TYPE_OF_AUDIO) {
                $mimeType = $this->getAttachmentMimeType($mediaUrl);
                $ext = substr($mimeType, strrpos($mimeType, '/') + 1);
                $videoId = $this->videoUpload($userId, $threadId, $mediaUrl, $this->settings, $ext);
            } else {
                $videoId = $this->videoUpload($userId, $threadId, $mediaUrl, $this->settings);
            }
            if ($videoId) {
                $video = ThreadVideo::query()->where('id', $videoId)->first();
                $video->type = $type;
                $video->save();

                $newVideoData['videoId'] = $videoId;
                $newVideoData['videoType'] = $type;
                $newVideoData['oldUrl'] = $mediaUrl;
            }
        }
        return $newVideoData;
    }

    private function insertThreadTopics($threadId, $topicIds)
    {
        $threadTopic = [];
        foreach ($topicIds as $key => $value) {
            $threadTopic[] = [
                'thread_id' => $threadId,
                'topic_id' => $value,
                'created_at' => Carbon::now()
            ];
        }
        $threadTopic = array_column($threadTopic, null, 'topic_id');
        $insertThreadTopicsResult = ThreadTopic::query()->insert($threadTopic);
        return $insertThreadTopicsResult;
    }

    private function insertTom($threadId, $imageIds, $attachmentIds, $videoId, $audioId)
    {
        $threadTomData = [];
        $threadTagData[] = [
            'thread_id' => $threadId,
            'tag' => ThreadTag::TEXT,
        ];
        if (!empty($imageIds)) {
            [$threadTomData, $threadTagData] = $this->getTomData($threadId, ThreadTag::IMAGE, 'imageIds', $imageIds, $threadTomData, $threadTagData);
        }

        if (!empty($attachmentIds)) {
            [$threadTomData, $threadTagData] = $this->getTomData($threadId, ThreadTag::DOC, 'docIds', $attachmentIds, $threadTomData, $threadTagData);
        }

        if (!empty($videoId)) {
            [$threadTomData, $threadTagData] = $this->getTomData($threadId, ThreadTag::VIDEO, 'videoId', $videoId, $threadTomData, $threadTagData);
        }

        if (!empty($audioId)) {
            [$threadTomData, $threadTagData] = $this->getTomData($threadId, ThreadTag::VOICE, 'audio', $audioId, $threadTomData, $threadTagData);
        }

        ThreadTom::query()->insert($threadTomData);
        ThreadTag::query()->insert($threadTagData);

        return true;
    }

    private function getTomData($threadId, $type, $typeStr, $ids, $threadTomData, $threadTagData)
    {
        $threadTomData[] = [
            'thread_id' => $threadId,
            'tom_type' => $type,
            'key' => $type,
            'value' => json_encode([$typeStr => $ids], 256)
        ];

        $threadTagData[] = [
            'thread_id' => $threadId,
            'tag' => $type
        ];

        return [$threadTomData, $threadTagData];
    }

    private function insertContent($threadId, $userId, $content, $isFirst, $createdAt)
    {
        $post = new Post();
        $post->user_id = $userId;
        $post->thread_id = $threadId;
        $post->content = $content;
        $post->is_first = $isFirst;
        $post->is_approved = Post::APPROVED_YES;
        $post->ip = '';
        $post->port = 0;
        $post->created_at = $post->updated_at = $createdAt;
        $post->save();
        return $post;
    }

    private function insertPosts($commentList, $oldUsernameData, $oldNicknameData, $threadId)
    {
        $postNumber = 0;
        foreach ($commentList as $value) {
            if (!isset($value['user']) || empty($value['user']) ||
                !isset($value['comment']['text']['text']) ||
                empty($value['comment']['text']['text'])) {
                throw new \Exception('数据格式有误');
            }

            [$oldUsernameData, $oldNicknameData, $userData] = $this->insertUser($oldUsernameData, $oldNicknameData, $value['user']);
            $userId = $userData->id;
            $imageIds = [];
            $content = $value['comment']['text']['text'];
            if (!empty($value['comment']['images'])) {
                $insertAttachmentResult = $this->insertImages($userId, $value['comment']['images'], Attachment::TYPE_OF_IMAGE);
                $imageIds = array_column($insertAttachmentResult, 'id');
            }

            $createdAt = strtotime($value['comment']['createdAt']) ? $value['comment']['createdAt'] : Carbon::now();
            $newPost = $this->insertContent($threadId, $userId, $content, Post::FIRST_NO, $createdAt);

            if ($newPost) {
                if (!empty($imageIds)) {
                    Attachment::query()->whereIn('id', $imageIds)->update(['type_id' => $newPost->id]);
                }
                $postNumber++;
            }
        }
        return $postNumber;
    }
}