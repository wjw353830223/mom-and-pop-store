<?php

// autoload_psr4.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'think\\helper\\' => array($vendorDir . '/topthink/think-helper/src'),
    'think\\composer\\' => array($vendorDir . '/topthink/think-installer/src'),
    'think\\' => array($baseDir . '/thinkphp/library/think', $vendorDir . '/topthink/think-image/src', $vendorDir . '/topthink/think-queue/src'),
    'Workerman\\' => array($vendorDir . '/workerman/workerman-for-win'),
    'GatewayWorker\\' => array($vendorDir . '/workerman/gateway-worker-for-win/src'),
);
