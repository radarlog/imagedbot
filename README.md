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
Edit the file `url.php` with init url, for example:

```php
return [
    'initUrl' => 'https://twitter.com/'
];
```

USAGE
-----

You can schedule list of images to be downloaded from init url page:

```
./bot shedule
```

You can download scheduled images and save to local `/storage` folder.
Optional you can choose queues. If you avoid it, all queues would be used

```
./bot parse/download [download] [failed]
```