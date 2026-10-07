# BrutalTestRunner

Minimalist PHP test runner.

Is a BRUTAL DEV DESIGNED APP -> idea from [@4uto3!o6r4mm](http://autobiogramm.tuxfamily.org/brutalisme.html)

## Install

 
```sh
composer require --dev osd84/brutaltestrunner
```


## Simple - only 3 methods :

```php
$btr = new BrutalTestRunner();
$btr->header(<test_name>) // print header in out
$btr->assertEqual(<expected_val>, <tested_val>, <info_msg>, <strict_mode_test_bool>) // Assert Equals
$btr->footer() // print result in out and correct exit() code
$tester->footer(exit: false); // OR if you don't want exit() script at the end
```

## How To use ?

Exemple of tests :

```php 
<?php

use osd84\BrutalTestRunner\BrutalTestRunner;

require dirname(__DIR__) . '/vendor/autoload.php';

$btr = new BrutalTestRunner(); // init the runner

$btr->header(__FILE__); // if you want pretty header in terminal

$btr->assertEqual(true, true, 'true == true'); // Only assertEqual test, it's minimalist
$btr->assertEqual(true, is_file(__FILE__), 'script is file');
$btr->assertEqual(true, false, 'true == false', true);
$btr->assertEqual(1, '1', "1 === '1'", false); // assertEqual no strict mode (default)
$btr->assertEqual(1, '1', "1 === '1' strict", true); // assertEqual with strict mode
$btr->assertEqual(true, 1, "true === 1 strict", true); // assertEqual with strict mode

$btr->footer(); // if you want pretty footer n terminal AND good exit code success/fail
```

Result :
```shell
-----------
Brutal test Runner for [test.php]
test 1 :: OK ✔ :: script is file
test 2 :: OK ✔ :: true == true
test 3 :: OK ✔ :: 1 === '1'
test 4 :: FAIL ✖ :: 1 === '1' must, Fail because 'strict' enabled
test 5 :: FAIL ✖ :: true === 1 must, Fail because 'strict' enabled
test 6 :: FAIL ✖ :: true == false must, Fail because 'strict' enabled

-----------
✖ [FAILED] 3 fails, 3 success, 6 total 
```

See code example in test.php :

```shell
php7.4  test.php
```

## Debug Mode

For more debug info you can "on" debug mode

```php
<?php

$btr = new BrutalTestRunner(true);
```

will stop in first failed test :

```shell
-----------
Brutal test Runner for [test.php]
test 1 :: OK ✔ :: script is file
test 2 :: OK ✔ :: true == true
test 3 :: OK ✔ :: 1 === '1'
test 4 :: FAIL ✖ :: 1 === '1'  must, Fail because 'strict' enabled
    1 != 1 
---------------
EXPECT :
1
FOUND :
'1'
---------------
Tests FAILED
```
## Testing

```sh
composer install
php7.4  tests/test.php
```
