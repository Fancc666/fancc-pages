<?php
include './kvmkit.php';

$resp = new Response();

/**
 * 生成不重复的6位随机串
 * @param SqliteDB $db
 * @return string
 */
function generateUniquePath(SqliteDB $db) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    for ($i = 0; $i < 20; $i++) {
        $path = '';
        for ($j = 0; $j < 6; $j++) {
            $path .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $exists = $db->query_search(
            "SELECT COUNT(*) AS cnt FROM user_data WHERE path = :p",
            [[':p', $path, SqliteDB::TEXT]]
        );
        if ($exists[0]['cnt'] == 0) {
            return $path;
        }
    }
    throw new Exception('hash error');
}

/**
 * 生成6位随机串
 * @return string
 */
function generateUniqueSec() {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    $path = '';
    for ($j = 0; $j < 6; $j++) {
        $path .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $path;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['content']) || empty($_POST['uname'])) {
        $resp->apply(400, 'Form is not complement');
        $resp->text();
    }
    if (mb_strlen($_POST['uname']) > 30) {
        $resp->apply(400, 'Your name is too long(>30)');
        $resp->text();
    }
    if (mb_strlen($_POST['content']) > 8000) {
        $resp->apply(400, 'Content is too long(>8000)');
        $resp->text();
    }

    $db = new SqliteDB('./userdata.db');
    $sec = generateUniqueSec();
    $pth = generateUniquePath($db);
    $flag = $db->insert_data(
        'INSERT INTO user_data (ip, content, user_name, secret, path, created_at) VALUES (:ip, :content, :user_name, :secret, :path, :created_at)',
        [
            [':ip', Kit::getUserIp(), SqliteDB::TEXT],
            [':content', $_POST['content'], SqliteDB::TEXT],
            [':user_name', $_POST['uname'], SqliteDB::TEXT],
            [':secret', $sec, SqliteDB::TEXT],
            [':path', $pth, SqliteDB::TEXT],
            [':created_at', Kit::getStrTime(), SqliteDB::TEXT]
        ]
    );
    $db->close();
    if ($flag) {
        $resp->apply(0, 'Success');
        $resp->add_data([
            'path' => $pth,
            'sec' => $sec
        ]);
        $resp->text();
    } else {
        $resp->apply(500, 'Server Error');
        $resp->text();
    }
} else {
    $resp->apply(400, 'Method not allowed');
    $resp->text();
}

?>