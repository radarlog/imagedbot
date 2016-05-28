<?php

namespace components;

use Curl\Curl;
use simple_html_dom;
use Yii;
use yii\base\Component;

/**
 * Class Parser
 */
class Parser extends Component
{
    /**
     * @var Curl
     */
    public static $curl;

    /**
     * @var simple_html_dom
     */
    public static $htmlDom;

    /**
     * @var string currently parsing absolute url
     */
    protected $baseUrl;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->initCurl();
    }

    /**
     * init Curl
     */
    protected function initCurl()
    {
        $cookiesFile = Yii::getAlias('@runtime/cookies');

        //init
        self::$curl = new Curl();
        self::$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        self::$curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        self::$curl->setCookieFile($cookiesFile);  //on read
        self::$curl->setCookieJar($cookiesFile); //on write
        self::$curl->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/43.0.2357.65 Chrome/43.0.2357.65 Safari/537.36');
        self::$curl->setTimeout(10);
        self::$curl->setOpt(CURLOPT_AUTOREFERER, true);
        self::$curl->setOpt(CURLOPT_ENCODING, 'gzip');
        self::$curl->setOpt(CURLOPT_FOLLOWLOCATION, true);

        self::$curl->setHeader('Expect', '');

        self::$curl->error(function ($curl) {
            throw new ParserException("Curl error: {$curl->errorMessage}");
        });
    }

    /**
     * init simple_html_dom
     */
    protected function initHtmlDom()
    {
        self::$htmlDom = new simple_html_dom();
    }

    /**
     * @param string $url
     * @return array of found images
     */
    public function run($url)
    {
        $this->initHtmlDom();

        $this->baseUrl = $url;

        $response = self::$curl->get($url);
        $html = self::$htmlDom->load($response);

        $images = $this->getImages($html);

        return $images;
    }

    /**
     * @param simple_html_dom $html
     * @return array of found images
     */
    protected function getImages(simple_html_dom $html)
    {
        $images = [];

        //TODO parse CSS

        foreach ($html->find('img') as $img) {
            if ($img->src) {
                $absUrl = $this->rel2abs($img->src, $this->baseUrl);
                if ($absUrl !== false) {
                    $images[] = $absUrl;
                }
            }
        }

        return array_unique($images);
    }

    /**
     * @param $relUrl
     * @param $baseUrl
     * @return string
     */
    protected function rel2abs($relUrl, $baseUrl)
    {
        //absolute url with scheme
        if (strpos($relUrl, 'data:image/') === 0) {
            //TODO: handle
            return false;
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] . '://';
        $host = $base['host'];
        $path = !isset($base['path']) ? '/' : $base['path'];
        $prefix = $scheme . $host . $path;

        //absolute url with scheme
        if (parse_url($relUrl, PHP_URL_SCHEME) != '') {
            return $relUrl;
        }

        //absolute url begins with //
        if (strpos($relUrl, '//') === 0) {
            return substr_replace($relUrl, $scheme, 0, 2);
        }

        //absolute url begins with /
        if (strpos($relUrl, '/') === 0) {
            return substr_replace($relUrl, $prefix, 0, 1);
        }

        //relative url begins with ./
        if (strpos($relUrl, './') === 0) {
            return substr_replace($relUrl, $prefix, 0, 2);
        }

        //relative url begins with ../
        if (strpos($relUrl, '../') === 0) {
            //TODO handle and keep in mind possibility of multiple occurrences!
            return false;
        }

        //simple relative url
        return $prefix . $relUrl;
    }
}