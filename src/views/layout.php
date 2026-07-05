<?php
/** @var string $content */
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;

$user = Auth::user();
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Platzbelegung VfL Ramsdorf</title>
<link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/app.css')) ?>">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="<?= View::e(Url::to('/')) ?>">VfL Ramsdorf &middot; Platzbelegung</a>
        <?php if ($user): ?>
        <nav class="nav">
            <a href="<?= View::e(Url::to('/week')) ?>">Woche</a>
            <a href="<?= View::e(Url::to('/day')) ?>">Tag</a>
            <?php if (in_array($user['role'], ['trainer', 'betreuer'], true)): ?>
                <a href="<?= View::e(Url::to('/my-team')) ?>">Meine Mannschaft</a>
            <?php endif; ?>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= View::e(Url::to('/admin/teams')) ?>">Mannschaften</a>
                <a href="<?= View::e(Url::to('/admin/pitches')) ?>">Plaetze</a>
                <a href="<?= View::e(Url::to('/admin/seasons')) ?>">Saisons</a>
                <a href="<?= View::e(Url::to('/admin/base-schedule')) ?>">Grundbelegung</a>
                <a href="<?= View::e(Url::to('/admin/games')) ?>">Spiele</a>
                <a href="<?= View::e(Url::to('/admin/conflicts')) ?>">Konflikte</a>
            <?php endif; ?>
        </nav>
        <form method="post" action="<?= View::e(Url::to('/logout')) ?>" class="logout-form">
            <?= Csrf::field() ?>
            <span class="user-name"><?= View::e($user['name']) ?></span>
            <button type="submit" class="btn-link">Abmelden</button>
        </form>
        <?php else: ?>
        <a class="btn-link" href="<?= View::e(Url::to('/login')) ?>">Anmelden</a>
        <?php endif; ?>
    </div>
</header>
<main class="content">
    <?php foreach (Flash::pull() as $flash): ?>
        <div class="flash flash-<?= View::e($flash['type']) ?>"><?= View::e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
<footer class="footer">
    <a href="<?= View::e(Url::to('/public/week')) ?>">Oeffentliche Ansicht</a>
</footer>
</body>
</html>
