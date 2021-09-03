<?php

namespace App\Console\Commands;

use App\Api\Controller\AttachmentV3\AttachmentTrait;
use App\Api\Controller\Crawler\CrawlerTrait;
use App\Api\Serializer\AttachmentSerializer;
use App\Censor\Censor;
use App\Commands\Attachment\AttachmentUploader;
use App\Commands\Users\RegisterCrawlerUser as RegisterUser;
use App\Commands\Users\UploadCrawlerAvatar;
use App\Common\CacheKey;
use App\Crawler\WxMaterial;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadVideo;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Traits\VideoCloudTrait;
use App\User\CrawlerAvatarUploader;
use App\Validators\AvatarValidator;
use App\Validators\UserValidator;
use Carbon\Carbon;
use Discuz\Auth\Guest;
use Discuz\Console\AbstractCommand;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;
use Illuminate\Contracts\Filesystem\Filesystem as NewFilesystem;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Laminas\Diactoros\UploadedFile as RequestUploadedFile;

class CreateCrawlerOfficialAccountDataCommand extends AbstractCommand
{
    use AttachmentTrait;

    use CrawlerTrait;

    use VideoCloudTrait;

    protected $signature = 'crawlerOfficialAccountData:create';

    protected $description = '数据爬取，公众号文章内容导入';

    protected $settings;

    protected $image;

    protected $uploader;

    protected $attachmentSerializer;

    protected $filesystem;

    protected $events;

    protected $censor;

    protected $userValidator;

    protected $connection;

    protected $userRepo;

    protected $crawlerAvatarUploader;

    protected $avatarValidator;

    protected $newFilesystem;

    private $platform;

    private $categoryId;

    private $officialAccountName;

    private $userId;

    private $threadId;

    private $startCrawlerTime;

    private $lockPath;

    private $wxMaterial;

    private $wxSearchOfficialAccountUrl = 'https://mp.weixin.qq.com/cgi-bin/searchbiz?action=search_biz&begin=0&count=10&lang=zh_CN&f=json&ajax=1';

    private $wxArticleSearchOfficialAccountUrl = 'https://mp.weixin.qq.com/cgi-bin/appmsg?action=list_ex&type=9&query=&lang=zh_CN&f=json&ajax=1';

    //微信内容div正则
    private $wxContentDiv = '/<div class="rich_media_content " id="js_content" style="visibility: hidden;">(.*?)<\/div>/s';

    public function __construct(SettingsRepository  $settings, ImageManager $image,
                                AttachmentUploader $uploader, AttachmentSerializer $attachmentSerializer,
                                Filesystem $filesystem, Events $events,
                                Censor $censor, UserValidator $userValidator,
                                ConnectionInterface $connection, UserRepository $userRepo,
                                AvatarValidator $avatarValidator, NewFilesystem $newFilesystem)
    {

        $this->events = $events;
        $this->censor = $censor;
        $this->image  = $image;
        $this->userRepo = $userRepo;
        $this->uploader = $uploader;
        $this->settings = $settings;
        $this->connection = $connection;
        $this->filesystem = $filesystem;
        $this->newFilesystem = $newFilesystem;
        $this->userValidator = $userValidator;
        $this->avatarValidator = $avatarValidator;
        $this->attachmentSerializer = $attachmentSerializer;
        $this->crawlerAvatarUploader = new CrawlerAvatarUploader($this->censor, $this->newFilesystem , $this->settings);
        parent::__construct();
    }

    public function handle()
    {
        $crawlerSplQueue = app('cache')->get(CacheKey::CRAWLER_SPLQUEUE_INPUT_DATA);
        if (!$crawlerSplQueue) {
            exit;
        }
        $publicPath = public_path();
        $this->lockPath = $publicPath . DIRECTORY_SEPARATOR . 'crawlerSplQueueLock.conf';
        if (file_exists($this->lockPath)) {
            $lockFileContent = $this->getLockFileContent($this->lockPath);
            if ($lockFileContent['runtime'] < Thread::CREATE_CRAWLER_DATA_LIMIT_MINUTE_TIME && $lockFileContent['status'] == Thread::IMPORT_PROCESSING) {
                $this->info('----The content import process has been occupied,You cannot start a new process.----');
                exit;
            } else if ($lockFileContent['runtime'] > Thread::CREATE_CRAWLER_DATA_LIMIT_MINUTE_TIME) {
                $this->insertLogs('----Execution timed out.The file lock has been deleted.----');
                app('cache')->clear();
                $this->changeLockFileContent($this->lockPath, 0, Thread::PROCESS_OF_START_INSERT_CRAWLER_DATA, Thread::IMPORT_TIMEOUT_ENDING, $lockFileContent['topic']);
                exit;
            }
        }

        $this->startCrawlerTime = Carbon::now();

        if (!$crawlerSplQueue->isEmpty()) {
            $inputData = $crawlerSplQueue->dequeue();
            $this->categoryId = $inputData['categoryId'];
            $this->platform = $inputData['platform'];
            $this->officialAccountName = $inputData['officialAccountName'];
            $officialAccountCookie = $inputData['officialAccountCookie'];
            $officialAccountToken = $inputData['officialAccountToken'];
            if ($this->platform != Thread::CRAWLER_DATA_PLATFORM_OF_WECHAT) {
                exit;
            }

            $this->wxSearchOfficialAccountUrl = $this->wxSearchOfficialAccountUrl . '&query=' . $this->officialAccountName . '&token=' . $officialAccountToken;
            $this->wxMaterial = new WxMaterial();
            $searchResult = $this->wxMaterial->curl($this->wxSearchOfficialAccountUrl, [], $officialAccountCookie);
            $searchResult = json_decode($searchResult, true);
            if (empty($searchResult['list'])) {
                $this->insertLogs('----No official account is obtained. Process ends.----');
                app('cache')->clear();
                $this->changeLockFileContent($this->lockPath, 0, Thread::PROCESS_OF_START_INSERT_CRAWLER_DATA, Thread::IMPORT_NOTHING_ENDING, $this->officialAccountName);
                exit;
            } else {
                $this->officialAccountName = $searchResult['list'][0]['nickname'];
                $fakeId = $searchResult['list'][0]['fakeid'];
                $avatarUrl = $searchResult['list'][0]['round_head_img'];
                $pageArticleNumber = 5;
                $newWxArticleSearchOfficialAccountUrl = $this->wxArticleSearchOfficialAccountUrl . '&fakeid=' . $fakeId . '&token=' . $officialAccountToken . '&begin=0&count=';
                $articleSearchResult = $this->wxMaterial->curl($newWxArticleSearchOfficialAccountUrl . $pageArticleNumber, [], $officialAccountCookie);
                $articleSearchResult = json_decode($articleSearchResult, true);
                if (!isset($articleSearchResult['app_msg_cnt']) || $articleSearchResult['app_msg_cnt'] == 0) {
                    $this->insertLogs('----No official account article is obtained. Process ends.----');
                    app('cache')->clear();
                    $this->changeLockFileContent($this->lockPath, 0, Thread::PROCESS_OF_START_INSERT_CRAWLER_DATA, Thread::IMPORT_NOTHING_ENDING, $this->officialAccountName);
                    exit;
                }
                $this->insertLogs("----The official account's total data number is  [" . $articleSearchResult['app_msg_cnt'] . "].----");

                $totalNumber = count($articleSearchResult['app_msg_list']);
                $articleList = $articleSearchResult['app_msg_list'];
                if ($articleSearchResult['app_msg_cnt'] > 25) {
                    while ($totalNumber < $articleSearchResult['app_msg_cnt']) {
                        $begin = $totalNumber;
                        $url = $this->wxArticleSearchOfficialAccountUrl . '&fakeid=' . $fakeId . '&token=' . $officialAccountToken . '&begin=' . $begin . '&count=' . $pageArticleNumber;
                        $moreArticleSearchResult = $this->wxMaterial->curl($url, [], $officialAccountCookie);
                        $moreArticleSearchResult = json_decode($moreArticleSearchResult, true);
                        if (isset($moreArticleSearchResult['app_msg_list']) && count($moreArticleSearchResult['app_msg_list']) > 0) {
                            $articleList = array_merge($articleList, $moreArticleSearchResult['app_msg_list']);
                        } else {
                            break;
                        }
                        $totalNumber = count($articleList);
                        sleep(5);
                    }
                }
            }
            $this->insertLogs('----The total number of crawler data: ' . $totalNumber . '.----');

            $this->insertLogs('----Start importing crawler data.----');
            $this->userId = $this->insertUser($this->officialAccountName, $avatarUrl);
            $startProcessPercent = 5;
            $this->changeLockFileContent($this->lockPath, $this->startCrawlerTime, $startProcessPercent, Thread::IMPORT_PROCESSING, $this->officialAccountName);
            $dataNumber = 0;
            $averageProcessPercent = 90 / $totalNumber;
            $importThreadProcessPercent = $startProcessPercent;

            foreach ($articleList as $key => $value) {
                try {
                    $this->connection->beginTransaction();
                    $value['link'] = str_replace('#rd', '', $value['link']);
                    $articleBasicInfo = [
                        'createdAt' => $value['create_time'],
                        'title' => $value['title'],
                        'wechatName' => $this->officialAccountName,
                        'contentUrl' => $value['link']
                    ];
                    $this->insertLogs("----The article url is [" . $value['link'] . "].----");
                    $this->insertLogs("----Insert thread's data start.----");
                    $newThread = $this->insertThread($articleBasicInfo);
                    $this->insertLogs("----Insert thread's data end,The thread id is " . $newThread->id . ".----");

                    $this->insertLogs("----Insert post's data start.----");
                    $urlContents = $this->getFileContents($value['link']);
                    $content = $this->getArticleContent($value['link'], $urlContents, $newThread->id);

                    $newPost = $this->insertPost($newThread, $content);
                    $this->insertLogs("----Insert post's data end,The post id is " . $newPost->id . ".----");
                    $this->connection->commit();
                    $newThread->is_draft = Thread::BOOL_NO;
                    $newThread->save();
                    $dataNumber++;
                    $importThreadProcessPercent = $importThreadProcessPercent + $averageProcessPercent;
                    $this->checkExecutionTime($importThreadProcessPercent, $dataNumber);
                } catch (\Exception $e) {
                    $this->connection->rollback();
                    $this->updateCountCache();
                    $this->insertLogs('----Importing crawler data fail,errorMsg: '. $e->getMessage() . '----');
                    app('cache')->clear();
                    $this->changeLockFileContent($this->lockPath, 0, Thread::PROCESS_OF_START_INSERT_CRAWLER_DATA, Thread::IMPORT_ABNORMAL_ENDING, $this->officialAccountName, $dataNumber);
                    exit;
                }
            }

            $this->updateCountCache();
            $this->changeLockFileContent($this->lockPath, 0, Thread::PROCESS_OF_END_INSERT_CRAWLER_DATA, Thread::IMPORT_NORMAL_ENDING, $this->officialAccountName, $dataNumber);
            $this->insertLogs("----Importing crawler data success.The importing'data total number is " . $dataNumber . ".----");
            app('cache')->clear();
            exit;
        }
    }

    private function insertUser($articleUsername, $avatarUrl)
    {
        $userData = User::query()->select('id', 'username', 'nickname')->get()->toArray();
        $usernameData = array_column($userData, null, 'username');
        $nicknameData = array_column($userData, 'nickname');
        $nickname = $articleUsername;
        $articleUsername = 'robotdzq_' . $articleUsername;

        if (isset($usernameData[$articleUsername])) {
            return $usernameData[$articleUsername]['id'];
        }
        if (in_array($nickname, $nicknameData)) {
            $nickname = User::addStringToNickname($articleUsername);
        }

        $randomNumber = mt_rand(111111, 999999);
        $password = $nickname . $randomNumber;
        $data = [
            'username' => $articleUsername,
            'nickname' => $nickname,
            'password' => $password,
            'passwordConfirmation' => $password,
            'dataType' => 'crawler'
        ];
        $newGuest = new Guest();
        $register = new RegisterUser($newGuest, $data);
        $registerUserResult = $register->handle($this->events, $this->censor, $this->settings, $this->userValidator);
        $this->uploadCrawlerUserAvatar($avatarUrl, $registerUserResult);
        if ($registerUserResult) {
            $registerUserResult->status = User::STATUS_NORMAL;
            $registerUserResult->save();
        }

        return $registerUserResult->id;
    }

    private function uploadCrawlerUserAvatar($avatarUrl, $registerUser)
    {
        set_time_limit(0);
        $file = $this->getFileContents($avatarUrl);
        if (!$file) {
            return false;
        }
        $tmpFile = tempnam(storage_path('/tmp'), 'avatar');
        $ext = substr($avatarUrl,strpos($avatarUrl,"wx_fmt=") + strlen("wx_fmt="));

        if (!in_array($ext, ['gif', 'png', 'jpg', 'jpeg', 'jpe', 'heic'])) {
            return false;
        }
        $ext = $ext ? ".$ext" : '';
        $tmpFileWithExt = $tmpFile . $ext;
        $avatarSize = @file_put_contents($tmpFileWithExt, $file);
        $mimeType = $this->getAttachmentMimeType($avatarUrl);
        $fileName = Str::random(40) . $ext;
        $avatarFile = new RequestUploadedFile(
            $tmpFile,
            $avatarSize,
            0,
            $fileName,
            $mimeType
        );
        $avatar = new UploadCrawlerAvatar($registerUser->id, $avatarFile, $registerUser, $tmpFile);
        $uploadAvatarResult = $avatar->handle($this->userRepo, $this->crawlerAvatarUploader, $this->avatarValidator);
        return $uploadAvatarResult;
    }

    private function insertThread($articleBasicInfo)
    {
        $createdAt = date('Y-m-d H:i:s', $articleBasicInfo['createdAt']);
        $newThread = new Thread();
        $newThread->user_id = $this->userId;
        $newThread->category_id = $this->categoryId;
        $newThread->title = $articleBasicInfo['title'];
        $newThread->type = Thread::TYPE_OF_ALL;
        $newThread->post_count = 1;
        $newThread->share_count = mt_rand(0, 100);
        $newThread->view_count = mt_rand(0, 100);
        $newThread->address = $newThread->location = '';
        $newThread->is_draft = Thread::BOOL_YES;
        $newThread->is_approved = Thread::BOOL_YES;
        $newThread->is_anonymous = Thread::BOOL_NO;
        $newThread->created_at = $newThread->updated_at = $createdAt ? $createdAt : Carbon::now();
        $newThread->source = $this->platform;
        $newThread->save();
        return $newThread;
    }

    private function getArticleContent($url, $urlContents, $threadId)
    {
        $content_html_pattern = $this->wxContentDiv;
        preg_match_all($content_html_pattern, $urlContents, $html_matchs);
        if(empty(array_filter($html_matchs))) {
            return '未获取到相关内容';
        }
        $content = $html_matchs[0][0];
        //去除掉hidden隐藏
        $content = str_replace('style="visibility: hidden;"','', $content);
        $content = preg_replace("/<(\/?mpprofile.*?)>/si",'', $content);
        $content = preg_replace("/<(\/?svg.*?)>/si",'', $content);
        $content = preg_replace("/<(\/?mpvideosnap.*?)>/si",'', $content); //  过滤视频号
        $content = preg_replace("/<(\/?mp-miniprogram.*?)>/si",'', $content); //  过滤小程序
        $content = $this->changeArticleImg($content);
        $content = $this->changeArticleVideo($url, $content, $threadId);
        //添加微信样式
        $content = '<div style="max-width: 677px;margin-left: auto;margin-right: auto;">' . $content . '</div>';

        return $content;
    }

    private function changeArticleImg($content)
    {
        preg_match_all('/<img.*?data-src=[\"|\']?(.*?)[\"|\']?\s.*?>/i', $content, $imagesSrc);
        if (!empty($imagesSrc[1])) {
            foreach ($imagesSrc[1] as $key => $value) {
                $this->insertLogs("----Upload the thread' image attachment start,the image url is [" . $value . "].----");
                [$attachmentId, $attachmentUrl] = $this->importArticleImg($value);
                $this->insertLogs("----Upload the thread' image attachment end,the attachment_id is " . $attachmentId . ".----");

                if($attachmentId) {
                    $newImageUrl = '<img src="' . $attachmentUrl . '" alt="attachmentId-' . $attachmentId . '" />';
                    $content = str_replace($imagesSrc[0][$key], $newImageUrl, $content);
                }
            }
        }

        return $content;
    }

    private function importArticleImg($url){
        $refer = "https://mmbiz.qpic.cn/";
        $opt = [
            'https'=>[
                'header'=>"Referer: " . $refer
            ],
            'ssl' => ['verify_peer'=>false, 'verify_peer_name'=>false]
        ];
        $context = stream_context_create($opt);
        //接受数据流
        $fileContent = file_get_contents($url, false, $context);
        $fileSize = strlen($fileContent);
        $maxSize = $this->settings->get('support_max_size', 'default', 0) * 1024 * 1024;
        if (empty($fileContent) || $fileSize < 0 ||  $fileSize > $maxSize) {
            return '';
        }

        $fileExt = substr($url,strpos($url,"wx_fmt=") + strlen("wx_fmt="));
        $allowExt = $this->settings->get('support_img_ext', 'default');
        if (!in_array($fileExt, explode(',', $allowExt))) {
            return '';
        }
        $mimeType = $this->getAttachmentMimeType($url);
        $fileName = Str::random(40) . '.' . $fileExt;
        $filePath = 'public/attachments/' . date('Y/m/d/');
        $tmpFile = tempnam(storage_path('/tmp'), 'attachment');
        $tmpFileWithExt = $tmpFile . '.' . $fileExt;
        @file_put_contents($tmpFileWithExt, $fileContent);
        $imageFile = new UploadedFile(
            $tmpFileWithExt,
            $fileName,
            $mimeType,
            0,
            true
        );
        // 帖子图片自适应旋转
        if(strtolower($fileExt) != 'gif' && extension_loaded('exif')) {
            $this->image->make($tmpFileWithExt)->orientate()->save();
        }

        $this->uploader->put(Attachment::TYPE_OF_IMAGE, $imageFile, $fileName, $filePath);
        list($width, $height) = getimagesize($tmpFileWithExt);
        @unlink($tmpFile);
        @unlink($tmpFileWithExt);

        $attachment = new Attachment();
        $attachment->uuid = Str::uuid();
        $attachment->user_id = $this->userId;
        $attachment->type = Attachment::TYPE_OF_IMAGE;
        $attachment->is_approved = Attachment::APPROVED;
        $attachment->attachment = $fileName;
        $attachment->file_path = $filePath;
        $attachment->file_name = $fileName;
        $attachment->file_size = $fileSize;
        $attachment->file_width = $width;
        $attachment->file_height = $height;
        $attachment->file_type = $mimeType;
        $attachment->is_remote = $this->uploader->isRemote();
        $attachment->ip = "";
        $attachment->save();
        $attachmentUrl = $this->getAttachmentUrl($attachment);
        return [$attachment->id, $attachmentUrl];
    }

    private function getAttachmentUrl($attachment)
    {
        if ($attachment->is_remote) {
            $attachmentUrl = $this->settings->get('qcloud_cos_sign_url', 'qcloud', true)
                ? $this->filesystem->disk('attachment_cos')->temporaryUrl($attachment->full_path, Carbon::now()->addDay())
                : $this->filesystem->disk('attachment_cos')->url($attachment->full_path);
        } else {
            $attachmentUrl = $this->filesystem->disk('attachment')->url($attachment->full_path);
        }
        return $attachmentUrl;
    }

    private function changeArticleVideo($url, $content, $threadId)
    {
        preg_match_all('/<iframe(.*?)<\/iframe>/', $content, $videoIframeSrc);
        preg_match_all('/<mpvoice(.*?)<\/mpvoice>/', $content, $voiceIframeSrc);
        if (empty($videoIframeSrc[0]) && empty($voiceIframeSrc[0])) {
            return $content;
        }

        $wxMaterial = new WxMaterial();
        $resources = $wxMaterial->getWxArticleMaterial($url);
        if (empty($resources['video']) && empty($resources['voice'])) {
            return $content;
        }

        $videoData = $this->importVideo($resources, $threadId);
        if (!empty($videoIframeSrc[0])) {
            $content = $this->changeVideoIframe($videoIframeSrc[0], $videoData, $content, ThreadVideo::TYPE_OF_VIDEO);
        }

        if (!empty($voiceIframeSrc[0])) {
            $content = $this->changeVideoIframe($voiceIframeSrc[0], $videoData, $content, ThreadVideo::TYPE_OF_AUDIO);
        }

        return $content;
    }

    private function importVideo($videoData, $threadId)
    {
        $newVideoData = [];
        foreach($videoData as $key => $value) {
            if ($key == 'video' && !empty($value)) {
                foreach($value as $articleVideo) {
                    if (!empty($articleVideo['vid'])) {
                        $articleVideo['videoType'] = ThreadVideo::TYPE_OF_VIDEO;
                        $newVideoData[] = $articleVideo;
                    }
                }
            }
            if ($key == 'voice' && !empty($value)) {
                foreach($value as $articleVoice) {
                    if (!empty($articleVoice['vid'])) {
                        $articleVoice['videoType'] = ThreadVideo::TYPE_OF_AUDIO;
                        $newVideoData[] = $articleVoice;
                    }
                }
            }
        }

        foreach ($newVideoData as $key => $value) {
            if (!empty($value['url'])) {
                $this->insertLogs("----Upload the thread video start,the video url is [" . $value['url'] . "].----");
                if ($value['videoType'] == ThreadVideo::TYPE_OF_AUDIO) {
                    $mimeType = $this->getAttachmentMimeType($value['url']);
                    $ext = substr($mimeType,strrpos($mimeType,'/') + 1);
                    $videoId = $this->videoUpload($this->userId, $threadId, $value['url'], $this->settings, $ext);
                } else {
                    $videoId = $this->videoUpload($this->userId, $threadId, $value['url'], $this->settings);
                }
                $this->insertLogs("----Upload the thread video end,the video_id is " . $videoId . ".----");
                if ($videoId) {
                    $video = ThreadVideo::query()->where('id', $videoId)->first();
                    $video->type = $value['videoType'];
                    $video->save();
                    $newVideoData[$key]['videoId'] = $videoId;
                }
            }
        }
        return $newVideoData;
    }

    private function changeVideoIframe($iframeSrc, $videoData, $content, $type)
    {
        foreach ($iframeSrc as $iframeSrcValue) {
            foreach ($videoData as $videoValue) {
                if ($videoValue['videoType'] == $type && strpos($iframeSrcValue, $videoValue['vid'])) {
                    $newIframeSrcValue = '<iframe src="" alt="videoId-'.$videoValue['videoId'].'" ></iframe>';
                    $content = str_replace($iframeSrcValue, $newIframeSrcValue, $content);
                }
            }
        }
        return $content;
    }

    private function insertPost($newThread, $content)
    {
        $threadPost = new Post();
        $threadPost->user_id = $this->userId;
        $threadPost->thread_id = $newThread->id;
        $threadPost->content = $content;
        $threadPost->is_first = Post::FIRST_YES;
        $threadPost->is_approved = Post::APPROVED_YES;
        $threadPost->ip = '';
        $threadPost->port = 0;
        $threadPost->created_at = $threadPost->updated_at = $newThread->created_at;
        $threadPost->save();
        return $threadPost;
    }

    private function transformHtml($string)
    {
        $string = str_replace('&quot;','"',$string);
        $string = str_replace('&amp;','&',$string);
        $string = str_replace('amp;','',$string);
        $string = str_replace('&lt;','<',$string);
        $string = str_replace('&gt;','>',$string);
        $string = str_replace('&nbsp;',' ',$string);
        $string = str_replace("\\", '',$string);
        return $string;
    }

    private function insertLogs($logString)
    {
        $this->info($logString);
        app('log')->info($logString);
        return true;
    }

    private function checkExecutionTime($importThreadProcessPercent, $dataNumber)
    {
        $runTime = floor((time() - strtotime($this->startCrawlerTime))%86400/60);
        if ($runTime > Thread::CREATE_CRAWLER_DATA_LIMIT_MINUTE_TIME) {
            $this->insertLogs('----Execution timed out.The file lock has been deleted.----');
            app('cache')->clear();
            $this->changeLockFileContent($this->lockPath, 0, Thread::PROCESS_OF_START_INSERT_CRAWLER_DATA, Thread::IMPORT_TIMEOUT_ENDING, $this->officialAccountName, $dataNumber);
            exit;
        } else {
            $this->changeLockFileContent($this->lockPath, $this->startCrawlerTime, $importThreadProcessPercent, Thread::IMPORT_PROCESSING, $this->officialAccountName, $dataNumber);
            return true;
        }
    }

    private function updateCountCache()
    {
        $user = User::query()->where('id', $this->userId)->first();
        $user->thread_count = Thread::query()
            ->where('user_id', $this->userId)
            ->whereNull('deleted_at')
            ->where('is_draft',Thread::IS_NOT_DRAFT)
            ->where('is_display', Thread::BOOL_YES)
            ->where('is_approved', Thread::APPROVED)
            ->count();
        $user->save();
        Category::refreshThreadCountV3($this->categoryId);
        return true;
    }
}