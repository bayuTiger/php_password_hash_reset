<form action="request.php" method="POST">
    <p>パスワードリセット</p>
    <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token']; ?>">
    <label>
        メールアドレスを入力してください。リセット用URLをお送りします。
        <input type="email" name="email" value="">
    </label>
    <button type="submit">登録</button>
</form>