<?php
/** @var array $games */
/** @var array $teams */
/** @var array $pitches */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<h1>Spiele</h1>

<section class="card">
    <h2>Spiel manuell anlegen</h2>
    <p class="muted">Bei Heimspielen mit ausgewaehltem Platz wird die Sperrzeit (Vorlauf/Spieldauer/Nachlauf)
        automatisch berechnet und betroffene Trainings werden erkannt.</p>
    <form method="post" action="<?= View::e(Url::to('/admin/games')) ?>" class="grid-form">
        <?= Csrf::field() ?>
        <label>Mannschaft
            <select name="team_id" required>
                <?php foreach ($teams as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"><?= View::e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Gegner <input type="text" name="opponent" required></label>
        <label>Heimspiel
            <select name="is_home">
                <option value="1">Ja</option>
                <option value="0">Nein (Auswaerts)</option>
            </select>
        </label>
        <label>Anstoss <input type="datetime-local" name="kickoff_datetime" required></label>
        <label>Platz (bei Heimspiel)
            <select name="pitch_id">
                <option value="">-- kein Platz / auswaerts --</option>
                <?php foreach ($pitches as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= View::e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Wettbewerb
            <select name="competition">
                <option value="pflichtspiel">Pflichtspiel</option>
                <option value="pokal">Pokalspiel</option>
                <option value="nachholspiel">Nachholspiel</option>
                <option value="jugendspiel">Jugendspiel</option>
                <option value="freundschaft">Freundschaftsspiel</option>
            </select>
        </label>
        <label>Spieldauer (Min., leer = Standard) <input type="number" name="duration_minutes"></label>
        <label>Vorlauf (Min., leer = Standard) <input type="number" name="lead_minutes"></label>
        <label>Nachlauf (Min., leer = Standard) <input type="number" name="lag_minutes"></label>
        <label>Notiz <input type="text" name="notes"></label>
        <button type="submit" class="btn btn-primary">Anlegen</button>
    </form>
</section>

<section class="card">
    <h2>Alle Spiele</h2>
    <table class="table">
        <thead><tr><th>Anstoss</th><th>Mannschaft</th><th>Gegner</th><th>Heim</th><th>Platz</th><th>Status</th><th>Aktionen</th></tr></thead>
        <tbody>
        <?php foreach ($games as $g): ?>
            <tr>
                <td><?= View::e((new DateTime($g['kickoff_datetime']))->format('d.m.Y H:i')) ?></td>
                <td><?= View::e($g['team_name']) ?></td>
                <td><?= View::e($g['opponent']) ?></td>
                <td><?= $g['is_home'] ? 'Ja' : 'Nein' ?></td>
                <td><?= View::e($g['pitch_name'] ?? '-') ?></td>
                <td><?= View::e($g['status']) ?></td>
                <td>
                    <?php if ($g['status'] !== 'cancelled'): ?>
                    <form method="post" action="<?= View::e(Url::to('/admin/games/' . $g['id'] . '/cancel')) ?>" class="inline-form" onsubmit="return confirm('Spiel absagen? Die Platzsperre wird aufgehoben.');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-link">Absagen</button>
                    </form>
                    <?php endif; ?>
                    <form method="post" action="<?= View::e(Url::to('/admin/games/' . $g['id'] . '/delete')) ?>" class="inline-form" onsubmit="return confirm('Spiel wirklich loeschen?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-link btn-danger">Loeschen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
