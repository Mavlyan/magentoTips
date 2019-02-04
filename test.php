<?php

require dirname(__DIR__) . '/app/bootstrap.php';

$params = $_SERVER;

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
$obMan = $bootstrap->getObjectManager();

/**
    BCЁ 3DECb MOJHO USATb ObjectManager N Bbl3blBATb METHObl. HAIIPNMEP:
*/

$emailCron = $obMan->get(\Moogento\Kkm\Crontab\Report::class);

dump($emailCron->execute());
die();


