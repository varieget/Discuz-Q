<?php


namespace App\Api\Controller\Plugin;

use App\Common\ResponseCode;
use Discuz\Base\DzqAdminController;
use Discuz\Contracts\Setting\SettingsRepository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Stream;

class PluginDeleteImageController extends DzqAdminController
{
    /**
     * @param Factory $filesystem
     * @param SettingsRepository $settings
     */
    public function __construct(Factory $filesystem, SettingsRepository $settings)
    {
        $this->filesystem = $filesystem;
        $this->settings = $settings;
    }

    public function main()
    {
        $urlPath = $this->inPut('url');
        $this->outPut(0,'');
    }

    /**
     * @param string $file
     */
    private function remove($file)
    {
        $filesystem = $this->filesystem->disk('public');

        if ($filesystem->has($file)) {
            $filesystem->delete($file);
        }
    }
}
