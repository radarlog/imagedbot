<?php

namespace controllers;

use components\ConsoleController;
use components\ParserException;
use finfo;
use Yii;

/**
 * Download queued urls
 *
 * @property string $fullPath
 */
class DownloadController extends ConsoleController
{
    CONST FAILED_TUBE = 'failed';

    /**
     * @inheritdoc
     */
    public $defaultAction = 'worker';

    /**
     * @var string
     */
    protected $url = '';

    /**
     * Worker. Can pass optional queue as params: [download] [failed]
     *
     * @see ConsoleController
     */
    public function actionWorker($job)
    {
        $this->url = $job->data;

        $this->stdout("Processing $this->url\n");

        try {
            /* @var $parser \components\Parser */
            $parser = Yii::$app->parser;
            $fileContent = $parser::$curl->get($this->url);
        } catch (ParserException $e) {
            $this->stderr("Error fetching $this->url: " . $e->getMessage() . "\n");

            return self::FAILED; //move to failed queue
        }

        if ($this->isImage($fileContent)) {
            $this->save($fileContent);
        } else {
            //why is get here?
            //TODO: improve scheduler
            $this->stderr("URL $this->url: is not an image\n");
        }

        return self::DELETE; //remove from queue
    }

    /**
     * @param $fileContent
     * @return bool
     */
    protected function isImage($fileContent)
    {
        $info = new finfo(FILEINFO_MIME_TYPE); // return mime type
        $mimeType = $info->buffer($fileContent);
        return strpos($mimeType, 'image/') === 0;
    }

    /**
     * @param $fileContent
     */
    protected function save($fileContent)
    {
        $saved = file_put_contents($this->fullPath, $fileContent, LOCK_EX);

        if ($saved !== false) {
            $this->stdout("Saved $this->url\n");
        } else {
            $this->stderr("Error save $this->url\n");
        }
    }

    /**
     * @return string
     */
    protected function getFullPath()
    {
        $fileName = basename($this->url);
        $fullPath = Yii::getAlias("@app/storage/$fileName");

        return $fullPath;
    }
}