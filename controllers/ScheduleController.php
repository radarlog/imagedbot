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

    public $defaultAction = 'worker';

    private $_imagesList = [];

    /**
     * Worker
     */

    public function actionWorker()
    {
        $url = Yii::$app->params['initUrl'];

        try {
            /* @var $parser \components\Parser */
            $parser = Yii::$app->parser;
            $this->_imagesList = $parser->run($url);
        } catch (ParserException $e) {
            $this->stderr('Parser error: ' . $e->getMessage() . "\n");
        }

        try {
            /* @var $beanstalk \components\Beanstalk */
            $beanstalk = Yii::$app->beanstalk;
            foreach ($this->_imagesList as $url) {
                $beanstalk->put($url, self::DOWNLOAD_TUBE);
                $this->stdout("Put $url to " . self::DOWNLOAD_TUBE . " queue\n");
            }
        } catch (BeanstalkException $e) {
            //should never get here
            $this->stderr('Beanstalk error: ' . $e->getMessage() . "\n");
        }
    }
}