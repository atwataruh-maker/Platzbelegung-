/**
 * Rein lesender Diagnose-Abruf fuer die geplante fussball.de-Anbindung.
 *
 * =====================================================================
 * Dieses Skript veraendert NICHTS: kein Login, keine Zugangsdaten, kein
 * POST an die Platzbelegung-App. Es ruft die unten gelisteten fussball.de-
 * Endpunkte ab und speichert fuer jeden: HTTP-Status, finale URL (nach
 * Redirects), Content-Type und den rohen Response-Body als Datei - damit
 * sich daran ablesen laesst, ob ein einfacher HTTP-Abruf (ohne Headless-
 * Browser) fuer den spaeteren echten Import ausreicht, und damit echte
 * Selektoren/Pagination/Filter gegen echtes HTML entwickelt werden
 * koennen, auch wenn das lokale Sandbox-Netz fussball.de blockiert.
 *
 * Die URLs wurden manuell recherchiert (Linkziele auf fussball.de) und
 * sind interne AJAX-Fragment-Endpunkte - keine dokumentierte, offiziell
 * unterstuetzte API. Weder erreichbar noch dauerhaft stabil ist damit
 * nachgewiesen; dieses Skript dient ausschliesslich dazu, das einmal zu
 * pruefen und das Ergebnis (Erfolg oder Fehler) sichtbar zu machen.
 * =====================================================================
 */

const fs = require('fs');
const path = require('path');

const TARGETS = [
    {
        key: 'vereinsspielplan',
        comment: 'VfL Ramsdorf - Vereinsspielplan (alle Mannschaften)',
        url: 'https://www.fussball.de/ajax.club.matchplan/-/id/00ES8GN8QS00004CVV0AG08LVUPGND5I/mode/PAGE/show-filter/true',
    },
    {
        key: 'e1-mannschaftsspielplan',
        comment: 'E1-Junioren - Mannschaftsspielplan',
        url: 'https://www.fussball.de/ajax.team.matchplan/-/mode/PAGE/team-id/011MIDAF8C000000VTVG0001VTR8C1K7',
    },
    {
        key: 'e2-mannschaftsspielplan',
        comment: 'E2-Junioren - Mannschaftsspielplan',
        url: 'https://www.fussball.de/ajax.team.matchplan/-/mode/PAGE/team-id/011MIEU4S4000000VTVG0001VTR8C1K7',
    },
];

const OUT_DIR = process.env.DIAGNOSE_OUT_DIR || path.join(__dirname, 'diagnose-output');

async function diagnoseOne(target) {
    const result = { key: target.key, comment: target.comment, requestUrl: target.url };
    try {
        const response = await fetch(target.url, {
            redirect: 'follow',
            headers: {
                // ANPASSEN: falls fussball.de ohne "echten" Browser-User-Agent
                // eine andere Antwort liefert (z.B. Bot-Sperre), hier testweise
                // einen realistischen Chrome-UA eintragen.
                'User-Agent': 'Mozilla/5.0 (compatible; VfLRamsdorfPlatzbelegungDiagnose/1.0; +https://github.com/)',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language': 'de-DE,de;q=0.9',
            },
        });

        const body = await response.text();

        // Auszug direkt ins Workflow-Log schreiben (nicht nur in die Artefakt-
        // Datei) - so laesst sich der Inhalt auch pruefen, wenn der Download
        // des Artefakts von woanders aus nicht moeglich ist.
        console.log(`--- Auszug ${target.key} (erste 1500 Zeichen von ${body.length}) ---`);
        console.log(body.slice(0, 1500));
        console.log(`--- Ende Auszug ${target.key} ---`);

        fs.mkdirSync(OUT_DIR, { recursive: true });
        const filePath = path.join(OUT_DIR, `${target.key}.html`);
        fs.writeFileSync(filePath, body, 'utf8');

        result.status = response.status;
        result.ok = response.ok;
        result.finalUrl = response.url;
        result.contentType = response.headers.get('content-type');
        result.byteLength = Buffer.byteLength(body, 'utf8');
        result.savedTo = path.relative(process.cwd(), filePath);
        // Grober Hinweis, ob eine JS-Shell statt gerenderter Daten kam
        // (echte Bestaetigung/Widerlegung erfolgt durch manuelles Ansehen
        // der gespeicherten HTML-Datei, nicht durch diese Heuristik).
        result.looksLikeEmptyAppShell = body.length < 2000 && /<div id=["']app["']|<app-root/i.test(body);
        // Grobe Plausibilitaetspruefung anhand der vom Nutzer genannten
        // Beispieldaten (Gegner aus dem Screenshot, konkrete Spielnummer).
        result.mentionsKnownOpponents = /Raesfeld|Schermbeck|Reken|Gahlen/i.test(body);
        result.mentionsExampleSpielnummer = body.includes('210666011');
    } catch (err) {
        result.ok = false;
        result.error = err.message;
    }
    return result;
}

async function main() {
    const results = [];
    for (const target of TARGETS) {
        console.log(`Abruf: ${target.key} (${target.comment})\n  ${target.url}`);
        const result = await diagnoseOne(target);
        console.log(JSON.stringify(result, null, 2));
        results.push(result);
    }

    fs.mkdirSync(OUT_DIR, { recursive: true });
    fs.writeFileSync(path.join(OUT_DIR, 'summary.json'), JSON.stringify(results, null, 2), 'utf8');
    console.log(`\nZusammenfassung gespeichert unter ${path.join(OUT_DIR, 'summary.json')}`);

    const failed = results.filter((r) => !r.ok);
    if (failed.length > 0) {
        console.error(`\n${failed.length} von ${results.length} Abrufen nicht erfolgreich (siehe oben/summary.json).`);
        process.exit(1);
    }
    console.log('\nAlle Abrufe erfolgreich (HTTP 2xx). Bitte die gespeicherten HTML-Dateien manuell pruefen,');
    console.log('ob sie tatsaechlich die erwarteten Spieltermine enthalten oder nur eine leere JS-Huelle.');
}

main();
