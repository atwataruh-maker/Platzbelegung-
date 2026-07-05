<?php
/** @var string $content */
use App\Core\Url;
use App\Core\View;
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Platzbelegung VfL Ramsdorf &ndash; oeffentliche Ansicht</title>
<link rel="stylesheet" href="<?= View::e(Url::to('/assets/css/app.css')) ?>">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <span class="brand">VfL Ramsdorf &middot; Platzbelegung</span>
    </div>
</header>
<main class="content">
    <?= $content ?>
</main>
</body>
</html>
