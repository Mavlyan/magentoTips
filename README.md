# Different tips and trics for Magento developer

### 1. test.php

Script allows to load Magento core and work like from ordinary Model. Place the script into root of Magento installation and run:
```sh
http://example.com/test.php
```
### 2. Show Full config.xml

```php
file_put_contents(dirname(__FILE__) .DS.'configxml.xml', Mage::app()->getConfig()->getXmlString());
```

###	3. Errors handling
add it to index.php:
```php
ini_set('error_reporting', E_ERROR);
register_shutdown_function("fatal_handler");
function fatal_handler() {
	$error = error_get_last();
	echo("<pre>");
	print_r($error);
}
```
