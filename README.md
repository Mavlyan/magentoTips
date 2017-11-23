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

###  3. Errors handling

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
### 4. API script

It is not multypurpose script. Works only with Magento's SOAP V2 (WS-I Compliance Mode).
See: http://devdocs.magento.com/guides/m1x/api/soap/introduction.html

How to use:
1. Change your Api user, Api password, WSDL Url and BAA login/pass if necessary. 
2. Change productId, shipping method, payment method, customer Id which exist in your magento store.
Or request for these data from store via blablablaList() api methods (eg catalogProductList())
3. Run it from your local env
