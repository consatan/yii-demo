<?php
$db = require __DIR__ . '/db.php';
// test database! Important not to run tests on production or development databases
$db['dsn'] = 'sqlite:' . sys_get_temp_dir() . '/yii2_test.sq3';

return $db;
