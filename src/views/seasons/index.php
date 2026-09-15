<?php
/** @var array $seasons */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<h1>Saisons &amp; Sondermodi</h1>

<section class="card">
    <h2>Neue Saison anlegen</h2>
    <form method="post" action="<?= View::e(Url::to('/admin/seasons')) ?>" class="grid-form">
        <?= Csrf::field() ?>
        <label>Name <input type="text" name="name" required></label>
        <label>Typ
            <select name="type">
                <option value="summer">Sommersaison</option>
                <option value="winter">Wintersaison</option>
                <option value="hall">Hallensaison</option>
                <option value="holiday">Ferien</option>
                <option value="tournament">Turnierwoche</option>
                <option value="closed">Platz gesperrt</option>
            </select>
        </label>
        <label>Start <input type="date" name="start_date" required></label>
        <label>Ende <input type="date" name="end_date" required></label>
        <button type="submit" class="btn btn-primary">Anlegen</button>
    </form>
</section>

<section class="card">
    <h2>Bestehende Saisons</h2>
    <table class="table">
        <thead><tr><th>Name</th><th>Typ</th><th>Start</th><th>Ende</th><th>Aktiv</th><th>Aktionen</th></tr></thead>
        <tbody>
        <?php foreach ($seasons as $season): ?>
            <tr>
                <td><?= View::e($season['name']) ?></td>
                <td><?= View::e($season['type']) ?></td>
                <td><?= View::e($season['start_date']) ?></td>
                <td><?= View::e($season['end_date']) ?></td>
                <td><?= $season['is_active'] ? '&#10003;' : '' ?></td>
                <td>
                    <?php if (in_array($season['type'], ['summer', 'winter', 'hall'], true) && !$season['is_active']): ?>
                        <form method="post" action="<?= View::e(Url::to('/admin/seasons/' . $season['id'] . '/activate')) ?>" class="inline-form">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn-link">Als aktive Basis-Saison setzen</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
