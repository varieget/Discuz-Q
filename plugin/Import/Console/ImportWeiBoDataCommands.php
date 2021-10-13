<?php


namespace Plugin\Import\Console;

use App\Import\ImportDataTrait;
use App\Models\Category;
use App\Models\Thread;
use Discuz\Base\DzqCommand;
use Plugin\Import\Platform\Weibo;

class ImportWeiBoDataCommands extends DzqCommand
{
    use ImportDataTrait;
    protected $signature = 'importData:insertWeiBoData {--topic=} {--number=} {--auto=} {--type=} {--interval=} {--month=} {--week=} {--day=} {--hour=} {--minute=}';
    protected $description = '执行一个脚本命令,控制台执行[php disco importData:insertWeiBoData]';
    private $importDataLockFilePath;
    private $autoImportDataLockFilePath;

    protected function main()
    {
        $topic = $this->option('topic');
        $number = (int) $this->option('number');
        if ($number < 0 || $number > 1000 || floor($number) != $number) {
            throw new \Exception('number参数错误');
        }
        if (!empty($number) && empty($topic)) {
            throw new \Exception('缺少关键词！');
        }

        $category = Category::query()->select('id')->orderBy('id', 'asc')->first()->toArray();
        if (empty($category)) {
            throw new \Exception('缺少分类，请您先创建内容分类！');
        }
        $categoryId = $category['id'];
        $publicPath = public_path();
        $this->importDataLockFilePath = $publicPath . DIRECTORY_SEPARATOR . 'importDataLock.conf';
        $this->autoImportDataLockFilePath = $publicPath . DIRECTORY_SEPARATOR . 'autoImportDataLock.conf';

        $auto = $this->option('auto');
        if ($auto) {
            // 写入自动导入任务
            $autoImportParameters = [
                'topic' => $topic,
                'number' => $number,
                'type' => $this->option('type') ?? 0,
                'interval' => $this->option('interval') ?? 0,
                'month' => $this->option('month') ?? 0,
                'week' => $this->option('week') ?? 0,
                'day' => $this->option('day') ?? 0,
                'hour' => $this->option('hour') ?? 0,
                'minute' => $this->option('minute') ?? 0
            ];
            $checkResult = $this->checkAutoImportParameters($this->autoImportDataLockFilePath, $autoImportParameters, 'WeiBo');
            if ($checkResult == 1) {
                $this->insertLogs('----The automatic import task is written successfully.----');
            } elseif ($checkResult == 2) {
                $this->insertLogs('----The automatic import task is written successfully,and overwrites the previous task.----');
            }
            exit;
        }

        if (empty($topic) && empty($number)) {
            if (!file_exists($this->autoImportDataLockFilePath)) {
                exit;
            }

            $autoImportDataLockFileContent = $this->getLockFileContent($this->autoImportDataLockFilePath);
            if ($autoImportDataLockFileContent['platform'] != 'WeiBo') {
                exit;
            }

            $fileData = $this->getAutoImportData($this->autoImportDataLockFilePath, $autoImportDataLockFileContent);
            if ($fileData && !empty($fileData['topic']) && !empty($fileData['number'])) {
                $this->insertPlatformData($fileData['topic'], $fileData['number'], $categoryId, true);
            }
            exit;
        } else {
            if (file_exists($this->importDataLockFilePath)) {
                $lockFileContent = $this->getLockFileContent($this->importDataLockFilePath);
                if ($lockFileContent['runtime'] < Thread::CREATE_CRAWLER_DATA_LIMIT_MINUTE_TIME && $lockFileContent['status'] == Thread::IMPORT_PROCESSING) {
                    $this->insertLogs('----The content import process has been occupied,You cannot start a new process.----');
                    exit;
                } else if ($lockFileContent['runtime'] > Thread::CREATE_CRAWLER_DATA_LIMIT_MINUTE_TIME) {
                    $this->insertLogs('----Execution timed out.The file lock has been deleted.----');
                    app('cache')->clear();
                    $this->changeLockFileContent($this->importDataLockFilePath, 0, Thread::PROCESS_OF_START_INSERT_CRAWLER_DATA, Thread::IMPORT_TIMEOUT_ENDING, $lockFileContent['topic']);
                    exit;
                }
            }

            $this->insertPlatformData($topic, $number, $categoryId, false);
            exit;
        }
    }

    private function insertPlatformData($topic, $number, $categoryId, $auto)
    {
        $startCrawlerTime = time();
        $this->changeLockFileContent($this->importDataLockFilePath, $startCrawlerTime, Thread::PROCESS_OF_START_INSERT_CRAWLER_DATA, Thread::IMPORT_PROCESSING, $topic);
        if ($auto) {
            $this->insertLogs('----Start automatic import.----');
            $this->changeLastImportFileContent($this->autoImportDataLockFilePath, $startCrawlerTime, Thread::AUTO_IMPORT_HAVE_FINISHED);
        } else {
            $this->insertLogs('----Start import.----');
        }
        $platform = new Weibo();
        $data = $platform->main($topic, $number);
        if (empty($data)) {
            $this->insertLogs('----No data is obtained. Process ends.----');
            $this->changeLockFileContent($this->importDataLockFilePath, 0, Thread::PROCESS_OF_START_INSERT_CRAWLER_DATA, Thread::IMPORT_NOTHING_ENDING, $topic);
            exit;
        }

        $processPercent = 0;
        $averageProcessPercent = 95 / count($data);
        $totalImportDataNumber = 0;
        foreach ($data as $value) {
            $theradId = $this->insertCrawlerData($topic, $categoryId, $value);
            $totalImportDataNumber++;
            $processPercent = $processPercent + $averageProcessPercent;
            $this->changeLockFileContent($this->importDataLockFilePath, $startCrawlerTime, $processPercent, Thread::IMPORT_PROCESSING, $topic, $totalImportDataNumber);
            $this->insertLogs('----Insert a new thread success.The thread id is ' . $theradId . '.The progress is ' . floor((string)$processPercent) . '%.----');
        }
        Category::refreshThreadCountV3($this->categoryId);
        $this->changeLockFileContent($this->lockPath, 0, Thread::PROCESS_OF_END_INSERT_CRAWLER_DATA, Thread::IMPORT_NORMAL_ENDING, $topic, $totalImportDataNumber);
        app('cache')->clear();
        $this->insertLogs("----Importing crawler data success.The progress is 100%.The importing' data total number is " . $totalImportDataNumber . ".----");
        return true;
    }

    private function insertLogs($logString)
    {
        $this->info($logString);
        app('log')->info($logString);
        return true;
    }
}