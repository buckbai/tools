<?php
/**
 * Created by PhpStorm.
 * User: buck
 * Date: 2018/1/10
 * Time: 13:09
 *
 * 将csv文件导入数据库，csv默认在当前的file目录内
 * 表名为文件名，字段名为csv首行标题，默认varchar(255)，可在$specialFields中更改
 * 每次执行会删除旧表，生成新表
 */

$dbname = 'test';
$username = 'root';
$passwd = '';
$options = null;

$specialFields = ['CommissionExt' => 'TEXT', 'CommissionValue' => 'TEXT'];
$unique = 'Domain';

// only get filename
$files = array_diff(scandir('file'), ['.', '..']);

$pdo = new PDO('mysql:host=localhost;dbname='.$dbname, $username, $passwd, $options);

foreach ($files as $file) {
    $fp = fopen(__DIR__.'/file/'.$file, 'r');
    $title = fgetcsv($fp);
    list($table, $fields) = extractTableInfo($file, $title, $specialFields);
    checkTable($table, $fields);
    while (false !== ($row = fgetcsv($fp))) {
        insert($table, $row);
    }
    fclose($fp);
}


/**
 * @param $file
 * @param $title
 * @param $specialFields
 * @return array
 */
function extractTableInfo($file, $title, $specialFields)
{
    $table = pathinfo($file, PATHINFO_FILENAME);
    $fields = array_combine($title, array_fill(0, count($title), 'VARCHAR(255)'));
    foreach ($specialFields as $specialField => $type) {
        if (key_exists($specialField, $fields)) {
            $fields[$specialField] = $type;
        }
    }
    return array($table, $fields);
}

/**
 * @param $table
 * @param $fields
 * @return int
 */
function checkTable($table, $fields)
{
    global $pdo;

    // clear old table
    $pdo->exec("drop table if exists $table");
    $columns = '';
    foreach ($fields as $field => $type) {
        if ($columns) {
            $columns .= ",\n  ";
        } else {
            $columns .= '  ';
        }
        $columns .= "`$field` $type DEFAULT NULL";
    }
    $sql = "create table `$table` (\n$columns\n) ENGINE=MyISAM DEFAULT CHARSET=utf8";
//    print_r($sql);
    $pdo->exec($sql);
}

function insert($table, $row)
{
    if (empty($row)) {
        return;
    }
    global $pdo;

    $placeholder = implode(', ', array_fill(0, count($row), '?'));
    $sql = "insert into $table values ($placeholder)";
    $sth = $pdo->prepare($sql);
    foreach ($row as $i => $r) {
        $sth->bindValue($i+1, $r);
    }
    $sth->execute();
    try {
        checkPdoError($sth);
    } catch (PDOException $e) {
        print_r($sth->errorInfo());
        print_r($row);
        exit();
    }
}

function checkPdoError(PDOStatement $sth)
{
    if ($sth->errorCode() !== '00000') {
        throw new PDOException('insert error!');
    }
}