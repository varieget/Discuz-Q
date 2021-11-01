<?php

namespace Plugin\Import\Traits;

trait ImportTrait
{
    public function getHtmlLabel($match, $content)
    {
        preg_match_all($match, $content,$matches);
        return $matches;
    }

    public function deleteImportLockFile()
    {
        $publicPath = public_path();
        $importDataLockFilePath = $publicPath . DIRECTORY_SEPARATOR . 'importDataLock.conf';
        if (file_exists($importDataLockFilePath)) {
            @unlink($importDataLockFilePath);
        }
        return true;
    }
}