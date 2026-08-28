<?php
include '../kvmkit.php';

$resp = new Response();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['content']) || empty($_POST['uname']) || empty($_POST['sec'])) {
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

    $db = new SqliteDB('userdata.db');
    $flag = $db->run(
        'UPDATE user_data set ip=:ip, content=:content, user_name=:user_name WHERE secret=:secret AND deleted_at IS NULL',
        [
            [':ip', Kit::getUserIp(), SqliteDB::TEXT],
            [':content', $_POST['content'], SqliteDB::TEXT],
            [':user_name', $_POST['uname'], SqliteDB::TEXT],
            [':secret', $_POST['sec'], SqliteDB::TEXT]
        ]
    );
    $db->close();
    if ($flag === 1) {
        $resp->apply(200, 'Success');
        $resp->text();
    } else {
        $resp->apply(404, 'No changes');
        $resp->text();
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (empty($_GET['sec'])) {
        $resp->apply(400, 'Sec not found');
        $resp->text();
    }
    $db = new SqliteDB('userdata.db');
    $data = $db->query_search(
        'SELECT id, user_name, content, created_at FROM user_data WHERE secret=:secret AND deleted_at IS NULL',
        [
            [':secret', $_GET['sec'], SqliteDB::TEXT]
        ]
    );
    $db->close();
    if (count($data) === 0) {
        $resp->apply(404, 'Content not found');
        $resp->text();
    }
    $resp->apply(200, 'Success');
    $resp->add_data([
        "id" => $data[0]['id'],
        "uname" => $data[0]['user_name'],
        "content" => $data[0]['content'],
        "created_at" => $data[0]['created_at']
    ]);
    $resp->text();
} else {
    $resp->apply(400, 'Method not allowed');
    $resp->text();
}

?>