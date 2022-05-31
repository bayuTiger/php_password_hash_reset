<?php
session_start();

// pdoオブジェクトを取得
require_once './database.php';
$pdo = getPdo();

// クエリからtokenを取得
$passwordResetToken = filter_input(INPUT_GET, 'token');

// tokenに合致するユーザーを取得
$sql = 'SELECT * FROM `password_resets` WHERE `token` = :token';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':token', $passwordResetToken, \PDO::PARAM_STR);
$stmt->execute();
$passwordResetuser = $stmt->fetch(\PDO::FETCH_OBJ);

// 合致するユーザーがいなければ無効なトークンなので、処理を中断
if (!$passwordResetuser) exit('無効なURLです');

// 今回はtokenの有効期間を24時間とする
$tokenValidPeriod = (new \DateTime())->modify("-24 hour")->format('Y-m-d H:i:s');

// パスワードの変更リクエストが24時間以上前の場合、有効期限切れとする
if ($passwordResetuser->token_sent_at < $tokenValidPeriod) {
    exit('有効期限切れです');
}

// formに埋め込むcsrf tokenの生成
if (empty($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

// パスワードリセットフォームを読み込む
require_once './views/reset_form.php';