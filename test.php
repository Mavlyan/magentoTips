<?php
chdir(dirname(__FILE__));

require 'app/bootstrap.php';
require 'app/Mage.php';

Mage::setIsDeveloperMode(true);

ini_set('display_errors', 1);

umask(0);

$mageRunCode = isset($_SERVER['MAGE_RUN_CODE']) ? $_SERVER['MAGE_RUN_CODE'] : '';
$mageRunType = isset($_SERVER['MAGE_RUN_TYPE']) ? $_SERVER['MAGE_RUN_TYPE'] : 'store';

Mage::app();

/***********************************/
//If I need to run script as Admin:
//Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        try {
            #Place your code here
        } catch (Exception $exc) {
            var_dump($exc->getMessage());
            echo $exc->getTraceAsString();
        }
        