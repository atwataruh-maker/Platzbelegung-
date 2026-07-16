<?php
/** @var array $teams */
/** @var array $trainers */
/** @var array $pitches */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<h1>Mannschaften</h1>

<section class="card">
    <h2>Neue Mannschaft anlegen</h2>
    <form method="post" action="<?= View::e(Url::to('/admin/teams')) ?>" class="grid-form">
        <?= Csrf::field() ?>
        <label>Name <input type="text" name="name" required></label>
        <label>Altersklasse <input type="text" name="age_group"></label>
        <label>Geschlecht
            <select name="gender">
                <option value="gemischt">gemischt</option>
                <option value="m">maennlich</option>
                <option value="w">weiblich</option>
            </select>
        </label>
        <label>Trainer
            <select name="trainer_user_id">
                <option value="">-- keiner --</option>
                <?php foreach ($trainers as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"><?= View::e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Co-Trainer
            <select name="co_trainer_user_id">
                <option value="">-- keiner --</option>
                <?php foreach ($trainers as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"><?= View::e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Bevorzugte Trainingsdauer (Min.) <input type="number" name="preferred_duration_minutes" value="60"></label>
        <label>Bevorzugter Platztyp
            <select name="preferred_pitch_kind">
                <option value="egal">egal</option>
                <option value="kunstrasen">Kunstrasen</option>
                <option value="rasen">Rasen</option>
                <option value="halle">Halle</option>
            </select>
        </label>
        <label>Bevorzugte Groesse
            <select name="preferred_size">
                <option value="egal">egal</option>
                <option value="gross">Grossfeld</option>
                <option value="drittel">Drittel</option>
            </select>
        </label>
        <label>Prioritaet (1=hoch) <input type="number" name="priority" value="5"></label>
        <label>Fussball.de/DFBnet-Kennung <input type="text" name="external_ref" placeholder="z.B. DFBnet-Mannschafts-ID"></label>
        <label>Standard-Heimplatz (fuer automatischen Spielplan-Import)
            <select name="default_pitch_id">
                <option value="">-- keiner / manuell zuweisen --</option>
                <?php foreach ($pitches as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= View::e($p['name']) ?> (<?= View::e($p['season_type']) ?>)<?= $p['is_combo'] ? ' - Kombi/Grossfeld' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary">Anlegen</button>
    </form>
</section>

<section class="card">
    <h2>Bestehende Mannschaften</h2>
    <table class="table">
        <thead><tr><th>Name</th><th>Altersklasse</th><th>Trainer</th><th>Prioritaet</th><th>Aktionen</th></tr></thead>
        <tbody>
        <?php foreach ($teams as $team): ?>
            <tr>
                <td><?= View::e($team['name']) ?></td>
                <td><?= View::e($team['age_group'] ?? '') ?></td>
                <td><?= View::e($team['trainer_name'] ?? '-') ?></td>
                <td><?= (int) $team['priority'] ?></td>
                <td>
                    <details>
                        <summary>Bearbeiten</summary>
                        <form method="post" action="<?= View::e(Url::to('/admin/teams/' . $team['id'] . '/update')) ?>" class="grid-form">
                            <?= Csrf::field() ?>
                            <label>Name <input type="text" name="name" value="<?= View::e($team['name']) ?>" required></label>
                            <label>Altersklasse <input type="text" name="age_group" value="<?= View::e($team['age_group'] ?? '') ?>"></label>
                            <label>Trainer
                                <select name="trainer_user_id">
                                    <option value="">-- keiner --</option>
                                    <?php foreach ($trainers as $t): ?>
                                        <option value="<?= (int) $t['id'] ?>" <?= (int) $team['trainer_user_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= View::e($t['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Prioritaet <input type="number" name="priority" value="<?= (int) $team['priority'] ?>"></label>
                            <label>Fussball.de/DFBnet-Kennung <input type="text" name="external_ref" value="<?= View::e($team['external_ref'] ?? '') ?>"></label>
                            <label>Standard-Heimplatz
                                <select name="default_pitch_id">
                                    <option value="">-- keiner / manuell zuweisen --</option>
                                    <?php foreach ($pitches as $p): ?>
                                        <option value="<?= (int) $p['id'] ?>" <?= (int) ($team['default_pitch_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= View::e($p['name']) ?> (<?= View::e($p['season_type']) ?>)<?= $p['is_combo'] ? ' - Kombi/Grossfeld' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit" class="btn btn-secondary">Speichern</button>
                        </form>
                    </details>
                    <form method="post" action="<?= View::e(Url::to('/admin/teams/' . $team['id'] . '/delete')) ?>" class="inline-form" onsubmit="return confirm('Mannschaft wirklich deaktivieren?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-link btn-danger">Deaktivieren</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
