<?php
include './kvmkit.php';

$resp = new Response();

if (empty($_GET['sec'])) {
    $resp->apply(400, 'Secret not found');
    $resp->text();
}

$db = new SqliteDB('userdata.db');
$cnt = $db->run(
    'UPDATE user_data SET deleted_at=:deleted_at WHERE secret=:secret AND deleted_at IS NULL',
    [
        [':deleted_at', Kit::getStrTime(), SqliteDB::TEXT],
        [':secret', $_GET['sec'], SqliteDB::TEXT]
    ]
);
$db->close();

$resp->apply(200, 'Success');
$resp->add_data([
    "affected" => $cnt
]);
$resp->text();

?>