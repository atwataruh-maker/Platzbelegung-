<?php
/** @var array $entries */
/** @var array $teams */
/** @var array $pitches */
/** @var array $seasons */
/** @var array $weekdays */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<h1>Grundbelegung (wiederkehrendes Training)</h1>

<section class="card">
    <h2>Neue Trainingszeit anlegen</h2>
    <form method="post" action="<?= View::e(Url::to('/admin/base-schedule')) ?>" class="grid-form">
        <?= Csrf::field() ?>
        <label>Mannschaft
            <select name="team_id" required>
                <?php foreach ($teams as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"><?= View::e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Platz
            <select name="pitch_id" required>
                <?php foreach ($pitches as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= View::e($p['name']) ?> (<?= View::e($p['season_type']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Saison
            <select name="season_id" required>
                <?php foreach ($seasons as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= View::e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Wochentag
            <select name="weekday" required>
                <?php foreach ($weekdays as $num => $label): ?>
                    <option value="<?= $num ?>"><?= View::e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Von <input type="time" name="start_time" step="900" required></label>
        <label>Bis <input type="time" name="end_time" step="900" required></label>
        <label>Notiz <input type="text" name="note"></label>
        <button type="submit" class="btn btn-primary">Anlegen</button>
    </form>
</section>

<section class="card">
    <h2>Bestehende Trainingszeiten</h2>
    <table class="table">
        <thead><tr><th>Wochentag</th><th>Zeit</th><th>Mannschaft</th><th>Platz</th><th>Aktionen</th></tr></thead>
        <tbody>
        <?php foreach ($entries as $e): ?>
            <tr>
                <td><?= View::e($weekdays[(int) $e['weekday']] ?? $e['weekday']) ?></td>
                <td><?= View::e(substr($e['start_time'], 0, 5)) ?>&ndash;<?= View::e(substr($e['end_time'], 0, 5)) ?></td>
                <td><?= View::e($e['team_name']) ?></td>
                <td><?= View::e($e['pitch_name']) ?></td>
                <td>
                    <form method="post" action="<?= View::e(Url::to('/admin/base-schedule/' . $e['id'] . '/delete')) ?>" class="inline-form" onsubmit="return confirm('Trainingszeit wirklich entfernen?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-link btn-danger">Entfernen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
