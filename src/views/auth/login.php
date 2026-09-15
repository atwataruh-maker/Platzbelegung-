<?php
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Anmelden &ndash; Platzbelegung VfL Ramsdorf</title>
<link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/app.css')) ?>">
</head>
<body class="login-body">
<div class="login-box">
    <h1>VfL Ramsdorf</h1>
    <p class="subtitle">Platzbelegung &ndash; Anmeldung</p>
    <?php foreach (Flash::pull() as $flash): ?>
        <div class="flash flash-<?= View::e($flash['type']) ?>"><?= View::e($flash['message']) ?></div>
    <?php endforeach; ?>
    <form method="post" action="<?= View::e(Url::to('/login')) ?>" class="stacked-form">
        <?= Csrf::field() ?>
        <label>E-Mail
            <input type="email" name="email" required autofocus>
        </label>
        <label>Passwort
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary">Anmelden</button>
    </form>
</div>
</body>
</html>
