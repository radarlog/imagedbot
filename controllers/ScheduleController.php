<?php

namespace controllers;

use components\BeanstalkException;
use components\ParserException;
use Yii;
use yii\console\Controller;

/**
 * Schedule list of images to be downloaded
 */
class ScheduleController extends Controller
{
    CONST DOWNLOAD_TUBE = 'download';

    /**
     * @inheritdoc
     */
    public $defaultAction = 'worker';

    /**
     * Worker
     */
    public function actionWorker()
    {
        $files = Yii::$app->request->getParams();
        array_shift($files); //shift controller/action

        if (!empty($files)) {
            $images = $this->getImagesFromFiles($files);
        } else {
            $urls = Yii::$app->params['urlList'];
            $images = $this->getImagesFromUrlList($urls);
        }

        $images = array_unique($images);
        $this->schedule($images);

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * @param array $urls
     * @return array of parsed images
     */
    protected function getImagesFromUrlList(array $urls)
    {
        $images = [];

        try {
            /* @var $parser \components\Parser */
            $parser = Yii::$app->parser;
            foreach ($urls as $url) {
                $this->stdout("Querying images from url $url\n");
                $images = array_merge($images, $parser->run($url));
            }
        } catch (ParserException $e) {
            $this->stderr('Parser error: ' . $e->getMessage() . "\n");
        }

        return $images;
    }

    /**
     * @param array $files list
     * @return array of parsed images
     */
    protected function getImagesFromFiles(array $files)
    {
        $images = [];
        foreach ($files as $file) {
            $filePath = Yii::getAlias("@app/$file");
            if (file_exists($filePath)) {
                $this->stdout("Querying images from file $filePath\n");
                $images = array_merge($images, $this->getImagesFromFile($file));
            } else {
                $this->stderr("file $filePath does not exists\n");
            }
        }

        return $images;
    }

    /**
     * @param string $file
     * @return array of parsed images
     */
    protected function getImagesFromFile($file)
    {
        $images = explode("\n", file_get_contents($file));
        $images = array_filter($images, function ($url) {
            //Only http and https protocols are supported
            if (preg_match('#^https?://#', $url)) {
                return $url;
            }

            return false;
        });

        return $images;
    }

    /**
     * @param array $images
     * @return int
     */
    protected function schedule(array $images)
    {
        if (empty($images)) {
            $this->stderr("Nothing to schedule\n");
            return self::EXIT_CODE_ERROR;
        }

        try {
            /* @var $beanstalk \components\Beanstalk */
            $beanstalk = Yii::$app->beanstalk;
            foreach ($images as $url) {
                $beanstalk->put($url, self::DOWNLOAD_TUBE);
                $this->stdout("Put $url to " . self::DOWNLOAD_TUBE . " queue\n");
            }
        } catch (BeanstalkException $e) {
            //should never get here
            $this->stderr('Beanstalk error: ' . $e->getMessage() . "\n");
        }
    }
}