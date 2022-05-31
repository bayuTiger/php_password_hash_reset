<p>パスワードリセット</p>
<form action="reset.php" method="POST">
    <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token']; ?>">
    <input type="hidden" name="password_reset_token" value="<?= $passwordResetToken ?>">

    <label>
        新しいパスワード
        <input type="password" name="password">
    </label>
    <br>
    <label>
        パスワード（確認用）
        <input type="password" name="password_confirmation">
    </label>
    <br>
    
    <button type="submit">送信する</button>
</form>