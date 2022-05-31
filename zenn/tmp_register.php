<?php
session_start();

// csrf tokenを取得
$csrfToken = filter_input(INPUT_POST, '_csrf_token');

// csrf tokenを検証
if (
    empty($csrfToken)
    || empty($_SESSION['_csrf_token'])
    || $csrfToken !== $_SESSION['_csrf_token']
) {
    exit('不正なリクエストです');
}

// 本来はここでemailのバリデーションもかける
$email = filter_input(INPUT_POST, 'email');

// pdoオブジェクトを取得
require_once './database.php';
$pdo = getPdo();

// emailがusersテーブルに登録済みか確認
$sql = 'SELECT * FROM users WHERE `email` = :email';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':email', $email, \PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(\PDO::FETCH_OBJ);

// ユーザーがいる場合、本登録済みユーザーなら新規登録処理はせずにメール送信完了画面を表示
// 「登録済みです」と表示するのは、そのメールアドレスを知っている別人がこのメールアドレスを入力した場合に、
// 「このメールアドレスは登録済みである」という情報を与えてしまうので避けたい
if ($user && $user->status !== 'tentative') {
    require_once './views/email_sent.php';
    exit();
}

if (!$user) {
    // ユーザーがいなければ、仮登録としてテーブルにインサート
    $sql = 'INSERT INTO users(email, register_token, register_token_sent_at) VALUES(:email, :register_token, :register_token_sent_at)';
} else {
    // 既に仮登録済みのユーザーがいる場合、register_tokenの再発行と有効期限のリセットを行う
    // 有効期限切れで再度仮登録する場合はこちらの処理になる
    $sql = 'UPDATE users SET register_token = :register_token, register_token_sent_at = :register_token_sent_at WHERE email = :email';
}

// register token生成
$registerToken = bin2hex(random_bytes(32));

// 仮登録とメール送信は原子性を保ちたいため、トランザクションを設置する
// メール送信に失敗した場合は、仮登録も失敗させる
try {
    $pdo->beginTransaction();

    // ユーザーを仮登録
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
    $stmt->bindValue(':register_token', $registerToken, \PDO::PARAM_STR);
    $stmt->bindValue(':register_token_sent_at', (new \DateTime())->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
        // 日本語が文字化けしないよう、設定。php.iniで設定してあれば不要
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");

    // URLはご自身の環境に合わせてください
    $url = "http://localhost:1111/zenn/show_register_form.php?token={$registerToken}";

    $subject =  '仮登録が完了しました';

    $body = <<<EOD
        会員登録ありがとうございます！

        24時間以内に下記URLへアクセスし、本登録を完了してください。
        {$url}
        EOD;

    // Fromはご自身の環境に合わせてください
    $headers = "From : hoge@hoge.com\n";
    // text/htmlを指定し、html形式で送ることも可能
    $headers .= "Content-Type : text/plain";

    // mb_send_mailは成功したらtrue、失敗したらfalseを返す
    $isSent = mb_send_mail($email, $subject, $body, $headers);

    if (!$isSent) throw new \Exception('メール送信に失敗しました。');

    // メール送信まで成功したら、仮登録を確定
    $pdo->commit();

    echo "入力されたメールアドレスにメールを送信しました！";
    echo "<br>";
    echo "メールに記載のURLからパスワードを登録してください!";

} catch (\Exception $e) {
    $pdo->rollBack();

    exit($e->getMessage());
}


// 送信済み画面を表示
require_once './views/email_sent.php';