<?php
/** @var array $pitches */
/** @var array $relations */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<h1>Plaetze &amp; Hallen</h1>

<section class="card">
    <h2>Neuen Platz anlegen</h2>
    <form method="post" action="<?= View::e(Url::to('/admin/pitches')) ?>" class="grid-form">
        <?= Csrf::field() ?>
        <label>Name <input type="text" name="name" required></label>
        <label>Saison-Typ
            <select name="season_type">
                <option value="summer">Sommer</option>
                <option value="winter">Winter</option>
                <option value="hall">Halle</option>
            </select>
        </label>
        <label>Art
            <select name="kind">
                <option value="kunstrasen">Kunstrasen</option>
                <option value="rasen">Rasen</option>
                <option value="halle">Halle</option>
            </select>
        </label>
        <label>Groesse
            <select name="size">
                <option value="gross">Grossfeld / ganzer Platz</option>
                <option value="drittel">Drittel / Teilflaeche</option>
                <option value="halle">Halle</option>
            </select>
        </label>
        <label>Sortierung <input type="number" name="sort_order" value="0"></label>
        <button type="submit" class="btn btn-primary">Anlegen</button>
    </form>
</section>

<section class="card">
    <h2>Bestehende Plaetze</h2>
    <p class="muted">Teilflaechen-Zuordnung: Belegt jemand einen als "ganzer Platz" markierten Platz, werden alle
        verknuepften Teilflaechen automatisch mitgesperrt (z.B. wenn ein Grossfeldspiel mehrere Rasendrittel
        gleichzeitig braucht).</p>
    <table class="table">
        <thead><tr><th>Name</th><th>Saison</th><th>Art</th><th>Groesse</th><th>Teilflaechen</th><th>Aktionen</th></tr></thead>
        <tbody>
        <?php foreach ($pitches as $pitch): ?>
            <tr>
                <td><?= View::e($pitch['name']) ?></td>
                <td><?= View::e($pitch['season_type']) ?></td>
                <td><?= View::e($pitch['kind']) ?></td>
                <td><?= View::e($pitch['size']) ?></td>
                <td>
                    <?php foreach ($relations[$pitch['id']] ?? [] as $child): ?>
                        <span class="chip">
                            <?= View::e($child['name']) ?>
                            <form method="post" action="<?= View::e(Url::to('/admin/pitches/' . $pitch['id'] . '/relations/unlink')) ?>" class="inline-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="child_pitch_id" value="<?= (int) $child['id'] ?>">
                                <button type="submit" class="btn-link" title="Entfernen">&times;</button>
                            </form>
                        </span>
                    <?php endforeach; ?>
                    <details>
                        <summary>+ verknuepfen</summary>
                        <form method="post" action="<?= View::e(Url::to('/admin/pitches/' . $pitch['id'] . '/relations/link')) ?>" class="inline-form">
                            <?= Csrf::field() ?>
                            <select name="child_pitch_id">
                                <?php foreach ($pitches as $candidate): if ((int) $candidate['id'] === (int) $pitch['id']) continue; ?>
                                    <option value="<?= (int) $candidate['id'] ?>"><?= View::e($candidate['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-link">Verknuepfen</button>
                        </form>
                    </details>
                </td>
                <td>
                    <form method="post" action="<?= View::e(Url::to('/admin/pitches/' . $pitch['id'] . '/delete')) ?>" class="inline-form" onsubmit="return confirm('Platz wirklich deaktivieren?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-link btn-danger">Deaktivieren</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
