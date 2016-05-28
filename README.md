ImagedBot
============================

A command line Bot based on Yii2 framework, which downloads images from the single page

INSTALLATION
------------

### Install via Composer
~~~
composer global require "fxp/composer-asset-plugin:~1.1.1" #Yii2 required  component
composer install
~~~

CONFIGURATION
-------------
Edit the file `url.php` with url's list, for example:

```php
return [
    'urlList' => [
        'https://twitter.com/',
        'http://flickr.com'
    ]
];
```

USAGE
-----

You can schedule list of images to be downloaded from url's list pages.
Another way to schedule images' list is set certain files in root folder with url per line as params

```
./bot shedule [file1] [file2] [fileN]
```

You can download scheduled images and save to local `/storage` folder.
Optional you can choose certain queues. If you avoid it, all active queues would be used

```
./bot parse/download [download] [failed] [randomTube]
```