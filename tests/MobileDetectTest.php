<?php

require_once '../aurox.php';

use osd84\BrutalTestRunner\BrutalTestRunner;
use OsdAurox\MobileDetect;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Tests pour isMobile() et isTablet()
$tester->header("Test des méthodes isMobile() et isTablet()");

// Fonction helper pour simuler différents User-Agents
function createMobileDetectWithUA($userAgent) {
    if($userAgent === null){
        $userAgent = "";
    }
    $detect = new MobileDetect();
    $detect->setUserAgent($userAgent);
    return $detect;
}

// Test avec iPhone
$detect = createMobileDetectWithUA("Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1");
$tester->assertEqual($detect->isMobile(), true, "iPhone doit être détecté comme mobile");
$tester->assertEqual($detect->isTablet(), false, "iPhone ne doit pas être détecté comme tablet");

// Test avec Samsung Galaxy S21
$detect = createMobileDetectWithUA("Mozilla/5.0 (Linux; Android 12; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.104 Mobile Safari/537.36");
$tester->assertEqual($detect->isMobile(), true, "Samsung Galaxy doit être détecté comme mobile");
$tester->assertEqual($detect->isTablet(), false, "Samsung Galaxy ne doit pas être détecté comme tablet");

// Test avec iPad
$detect = createMobileDetectWithUA("Mozilla/5.0 (iPad; CPU OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1");
$tester->assertEqual($detect->isMobile(), true, "iPad doit être détecté comme mobile");
$tester->assertEqual($detect->isTablet(), true, "iPad doit être détecté comme tablet");

// Test avec Samsung Galaxy Tab
$detect = createMobileDetectWithUA("Mozilla/5.0 (Linux; Android 12; SM-T870) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.104 Safari/537.36");
$tester->assertEqual($detect->isMobile(), true, "Galaxy doit être détecté comme mobile");
$tester->assertEqual($detect->isTablet(), true, "Galaxy Tab doit être détecté comme tablet");

// Test avec Chrome Desktop
$detect = createMobileDetectWithUA("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36");
$tester->assertEqual($detect->isMobile(), false, "Chrome Desktop ne doit pas être détecté comme mobile");
$tester->assertEqual($detect->isTablet(), false, "Chrome Desktop ne doit pas être détecté comme tablet");

// Test avec Firefox Desktop
$detect = createMobileDetectWithUA("Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0");
$tester->assertEqual($detect->isMobile(), false, "Firefox Desktop ne doit pas être détecté comme mobile");
$tester->assertEqual($detect->isTablet(), false, "Firefox Desktop ne doit pas être détecté comme tablet");

// Test avec Googlebot
$detect = createMobileDetectWithUA("Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
$tester->assertEqual($detect->isMobile(), false, "Googlebot ne doit pas être détecté comme mobile");
$tester->assertEqual($detect->isTablet(), false, "Googlebot ne doit pas être détecté comme tablet");

// Test avec WAP Profile
$detect = createMobileDetectWithUA("Not empty");
$detect->setHttpHeaders([
    'HTTP_X_WAP_PROFILE' => 'something'
]);
$tester->assertEqual($detect->isMobile(), true, "WAP Profile doit être détecté comme mobile");
$tester->assertEqual($detect->isTablet(), false, "WAP Profile ne doit pas être détecté comme tablet");

// Test avec User-Agent vide
$detect = createMobileDetectWithUA("");
$tester->assertEqual($detect->isMobile(), false, "User-Agent vide ne doit pas être détecté comme mobile");
$tester->assertEqual($detect->isTablet(), false, "User-Agent vide ne doit pas être détecté comme tablet");

// Test avec User-Agent null
$detect = createMobileDetectWithUA(null);
$tester->assertEqual($detect->isMobile(), false, "User-Agent null ne doit pas être détecté comme mobile");
$tester->assertEqual($detect->isTablet(), false, "User-Agent null ne doit pas être détecté comme tablet");

$tester->footer();