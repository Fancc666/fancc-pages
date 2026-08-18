<?php
include './kvmkit.php';

$resp = new Response();

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (empty($_GET['p'])) {
        $resp->apply(400, 'Path not found');
        $resp->text();
    }

    $db = new SqliteDB('userdata.db');
    $data = $db->query_search(
        'SELECT id, user_name, content, created_at FROM user_data WHERE path=:path AND deleted_at IS NULL',
        [
            [':path', $_GET['p'], SqliteDB::TEXT]
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