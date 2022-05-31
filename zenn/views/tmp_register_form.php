<form action="tmp_register.php" method="POST">
    <p>仮会員登録</p>
    <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token']; ?>">
    <label>
        メールアドレスを入力してください
        <input type="email" name="email" value="">
    </label>
    <button type="submit">登録</button>
</form>