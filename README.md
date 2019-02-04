# Different tips and trics for Magento developer

### 1. test.php

a. Script allows to load Magento core and work like from ordinary Model. Place the script into root/pub of Magento installation

b. Open nginx config (conf/nginx/magento.conf in workplace) and replace:

```sh
    # PHP entry point for main application
    #location ~ (index|get|static|report|404|503)\.php$ {
```

with:

```sh
    #Fix to run any php file in ./pub
	location ~ \.php$ {
```

Then run:

```sh
http://example.com/test.php
```
