/**
 * DFBnet-Spielplan-Import fuer die Platzbelegung VfL Ramsdorf.
 *
 * =====================================================================
 * WICHTIGER HINWEIS: Dieses Skript wurde OHNE echten Zugriff auf die
 * DFBnet-Oberflaeche gebaut (Netzwerkzugriff war beim Bau blockiert).
 * Login-Feld-Bezeichnungen und der Export-Button-Text sind bestmoegliche
 * Vermutungen anhand oeffentlicher Dokumentation, NICHT live verifiziert.
 * Vor dem produktiven Einsatz: einmal mit echten Zugangsdaten in einer
 * GitHub-Action-Ausfuehrung testen und bei Bedarf die mit "ANPASSEN:"
 * markierten Stellen unten an die tatsaechliche Oberflaeche anpassen.
 * Bei jedem Schritt wird ein Screenshot gespeichert (siehe SCREENSHOT_DIR),
 * damit man im Fehlerfall genau sieht, wo es hakt, ohne DFBnet selbst
 * erneut oeffnen zu muessen.
 * =====================================================================
 *
 * Ablauf:
 *   1. DFBnet-Zugangsdaten von der App abrufen (GET
 *      /import/dfbnet-credentials, Token-Auth) - der Admin traegt sie
 *      unter /admin/dfbnet in der Oberflaeche ein, das Passwort liegt
 *      dort verschluesselt in der Datenbank. Es muessen also keine
 *      DFBnet-Zugangsdaten mehr als GitHub-Secret hinterlegt werden.
 *   1b. Login bei portal.dfbnet.org mit diesen Zugangsdaten.
 *   2. Fuer jede Mannschaft aus team-mapping.json: die hinterlegte
 *      Spielplanbearbeitung-URL der Staffel oeffnen und den CSV-Export
 *      auslösen (Direktlink statt Menue-Navigation, siehe README - das
 *      ist die robusteste Variante, da wir die genaue Menuefuehrung nicht
 *      kennen).
 *   3. Die heruntergeladene spielplan.csv einlesen, auf VfL-Ramsdorf-Spiele
 *      filtern und in das von der App erwartete JSON-Format bringen.
 *   4. Alle gesammelten Spiele in einem POST-Request an /import/games der
 *      PHP-App senden (Token-Auth, siehe README).
 */

const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const IMPORT_API_URL = requireEnv('IMPORT_API_URL'); // z.B. https://platzbelegung.vfl-ramsdorf.de/import/games
const IMPORT_API_TOKEN = requireEnv('IMPORT_API_TOKEN');
const IMPORT_CREDENTIALS_URL = deriveCredentialsUrl(IMPORT_API_URL);
const TEAM_MAPPING_PATH = process.env.TEAM_MAPPING_PATH || path.join(__dirname, 'team-mapping.json');
const SCREENSHOT_DIR = process.env.SCREENSHOT_DIR || path.join(__dirname, 'debug-screenshots');
const CLUB_NAME_PATTERN = /ramsdorf/i; // ANPASSEN falls der Vereinsname im CSV anders geschrieben wird

// ANPASSEN: echte Login-URL pruefen, falls diese nicht mehr stimmt.
const LOGIN_URL = 'https://portal.dfbnet.org/de/startseite.html';

function requireEnv(name) {
    const value = process.env[name];
    if (!value) {
        console.error(`Fehlende Umgebungsvariable: ${name}`);
        process.exit(1);
    }
    return value;
}

/** /import/games -> /import/dfbnet-credentials, sonst Fallback auf den Origin der URL. */
function deriveCredentialsUrl(importApiUrl) {
    if (importApiUrl.endsWith('/import/games')) {
        return importApiUrl.slice(0, -'/import/games'.length) + '/import/dfbnet-credentials';
    }
    return new URL('/import/dfbnet-credentials', importApiUrl).toString();
}

/** Holt die vom Admin unter /admin/dfbnet hinterlegten DFBnet-Zugangsdaten von der App. */
async function fetchDfbnetCredentials() {
    const response = await fetch(IMPORT_CREDENTIALS_URL, {
        headers: { Authorization: `Bearer ${IMPORT_API_TOKEN}` },
    });
    const body = await response.json().catch(() => ({}));

    if (response.status === 404) {
        throw new Error(
            'Keine DFBnet-Zugangsdaten in der App hinterlegt. Bitte im Adminbereich ' +
            'unter /admin/dfbnet einmalig Benutzername und Passwort eintragen.'
        );
    }
    if (!response.ok) {
        throw new Error(`Abruf der DFBnet-Zugangsdaten fehlgeschlagen (${response.status}): ${JSON.stringify(body)}`);
    }
    if (!body.username || !body.password) {
        throw new Error('Antwort der App enthielt keine vollstaendigen DFBnet-Zugangsdaten.');
    }
    return { username: body.username, password: body.password };
}

function loadTeamMapping() {
    if (!fs.existsSync(TEAM_MAPPING_PATH)) {
        console.error(
            `team-mapping.json nicht gefunden unter ${TEAM_MAPPING_PATH}. ` +
            'Bitte team-mapping.example.json kopieren und mit den echten Staffel-URLs fuellen.'
        );
        process.exit(1);
    }
    return JSON.parse(fs.readFileSync(TEAM_MAPPING_PATH, 'utf8'));
}

async function screenshot(page, name) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
    const file = path.join(SCREENSHOT_DIR, `${Date.now()}-${name}.png`);
    try {
        await page.screenshot({ path: file, fullPage: true });
        console.log(`Screenshot gespeichert: ${file}`);
    } catch (e) {
        console.warn(`Screenshot fehlgeschlagen (${name}):`, e.message);
    }
}

/** Sehr einfacher CSV-Parser (Semikolon-getrennt, erste Zeile = Header). Bewusst ohne Zusatz-Paket. */
function parseCsv(text) {
    const lines = text.replace(/\r\n/g, '\n').split('\n').filter((l) => l.trim() !== '');
    if (lines.length === 0) {
        return [];
    }
    const headers = splitCsvLine(lines[0]);
    return lines.slice(1).map((line) => {
        const cells = splitCsvLine(line);
        const row = {};
        headers.forEach((h, i) => {
            row[h.trim()] = (cells[i] ?? '').trim();
        });
        return row;
    });
}

function splitCsvLine(line) {
    // Minimaler Parser: unterstuetzt einfache Anfuehrungszeichen um Felder,
    // reicht fuer die typische DFBnet-CSV-Struktur ohne Zusatz-Library.
    const cells = [];
    let current = '';
    let inQuotes = false;
    for (let i = 0; i < line.length; i++) {
        const ch = line[i];
        if (ch === '"') {
            inQuotes = !inQuotes;
        } else if (ch === ';' && !inQuotes) {
            cells.push(current);
            current = '';
        } else {
            current += ch;
        }
    }
    cells.push(current);
    return cells;
}

/** Findet den ersten Spaltennamen, der auf eines der Suchmuster passt (case-insensitive, teilweise). */
function findColumn(headers, patterns) {
    for (const header of headers) {
        for (const pattern of patterns) {
            if (pattern.test(header)) {
                return header;
            }
        }
    }
    return null;
}

// ANPASSEN: falls die echten DFBnet-Spaltennamen abweichen, hier die Muster erweitern.
const COLUMN_PATTERNS = {
    date: [/^datum$/i, /spieldatum/i],
    time: [/^uhrzeit$/i, /anstoss/i, /anstoß/i],
    home: [/heim.?verein/i, /heimmannschaft/i, /^heim$/i],
    guest: [/gast.?verein/i, /gastmannschaft/i, /^gast$/i],
    venue: [/spielort/i, /austragungsort/i, /platz/i],
    matchNumber: [/spiel.?nr/i, /spielnummer/i, /spiel.?id/i],
    competition: [/staffel/i, /wettbewerb/i, /liga/i],
};

function mapRowsToGames(rows, teamConfig) {
    if (rows.length === 0) {
        return [];
    }
    const headers = Object.keys(rows[0]);
    const col = {};
    for (const [key, patterns] of Object.entries(COLUMN_PATTERNS)) {
        col[key] = findColumn(headers, patterns);
    }

    if (!col.date || !col.time || !col.home || !col.guest) {
        console.error(
            `[${teamConfig.key}] Konnte Pflichtspalten nicht in der CSV finden. ` +
            `Gefundene Spalten: ${headers.join(', ')}. Bitte COLUMN_PATTERNS in import.js anpassen.`
        );
        return [];
    }

    const games = [];
    for (const row of rows) {
        const home = row[col.home] || '';
        const guest = row[col.guest] || '';
        const isHome = CLUB_NAME_PATTERN.test(home);
        const isGuest = CLUB_NAME_PATTERN.test(guest);
        if (!isHome && !isGuest) {
            continue; // Spiel betrifft nicht VfL Ramsdorf, ignorieren
        }

        const kickoff = combineDateTime(row[col.date], row[col.time]);
        if (!kickoff) {
            console.warn(`[${teamConfig.key}] Ueberspringe Zeile mit unlesbarem Datum/Uhrzeit:`, row);
            continue;
        }

        const matchNumber = col.matchNumber ? row[col.matchNumber] : null;
        const externalRef = matchNumber && matchNumber !== ''
            ? `dfbnet-${matchNumber}`
            : `dfbnet-${teamConfig.key}-${row[col.date]}-${home}-${guest}`.replace(/\s+/g, '-');

        games.push({
            team_external_ref: teamConfig.ourTeamExternalRef,
            external_ref: externalRef,
            opponent: isHome ? guest : home,
            is_home: isHome,
            kickoff_datetime: kickoff,
            competition: teamConfig.competition || 'pflichtspiel',
            venue_text: col.venue ? row[col.venue] : null,
        });
    }
    return games;
}

/** Erwartet deutsches Datumsformat TT.MM.JJJJ und Uhrzeit HH:MM. ANPASSEN falls DFBnet ein anderes Format liefert. */
function combineDateTime(dateStr, timeStr) {
    if (!dateStr) {
        return null;
    }
    const dateMatch = /(\d{1,2})\.(\d{1,2})\.(\d{4})/.exec(dateStr);
    if (!dateMatch) {
        return null;
    }
    const [, day, month, year] = dateMatch;
    const timeMatch = /(\d{1,2}):(\d{2})/.exec(timeStr || '');
    const hour = timeMatch ? timeMatch[1].padStart(2, '0') : '00';
    const minute = timeMatch ? timeMatch[2] : '00';
    return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')} ${hour}:${minute}:00`;
}

async function loginToDfbnet(page, username, password) {
    await page.goto(LOGIN_URL, { waitUntil: 'networkidle' });
    await screenshot(page, '01-login-page');

    // ANPASSEN: Feld-Labels/Platzhalter pruefen, falls dies fehlschlaegt.
    const userField = page.getByLabel(/benutzername|username|e-?mail/i).first();
    const passField = page.getByLabel(/passwort|password/i).first();
    await userField.fill(username);
    await passField.fill(password);

    const loginButton = page.getByRole('button', { name: /anmelden|login|einloggen/i }).first();
    await Promise.all([
        page.waitForLoadState('networkidle'),
        loginButton.click(),
    ]);
    await screenshot(page, '02-after-login');

    const stillOnLogin = await page.getByLabel(/passwort|password/i).first().isVisible().catch(() => false);
    if (stillOnLogin) {
        throw new Error(
            'Login scheint fehlgeschlagen zu sein (Passwortfeld weiterhin sichtbar). ' +
            'Zugangsdaten pruefen oder Login-Selektoren in import.js anpassen.'
        );
    }
}

async function exportSpielplanCsv(page, teamConfig) {
    await page.goto(teamConfig.spielplanUrl, { waitUntil: 'networkidle' });
    await screenshot(page, `03-spielplan-${teamConfig.key}`);

    // ANPASSEN: Text/Rolle des Export-Buttons pruefen (laut Doku existiert
    // ein "Export"-Button, der eine spielplan.csv erzeugt).
    const exportControl = page.getByRole('button', { name: /export/i })
        .or(page.getByRole('link', { name: /export/i }))
        .first();

    const downloadPromise = page.waitForEvent('download', { timeout: 30000 });
    await exportControl.click();
    const download = await downloadPromise;

    const tmpDir = fs.mkdtempSync(path.join(require('os').tmpdir(), 'dfbnet-'));
    const csvPath = path.join(tmpDir, `${teamConfig.key}.csv`);
    await download.saveAs(csvPath);
    return fs.readFileSync(csvPath, 'utf8');
}

async function postGamesToApp(games) {
    if (games.length === 0) {
        console.log('Keine Spiele zum Uebertragen gefunden.');
        return;
    }

    const response = await fetch(IMPORT_API_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${IMPORT_API_TOKEN}`,
        },
        body: JSON.stringify({ source: 'dfbnet', games }),
    });

    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(`Import-API antwortete mit ${response.status}: ${JSON.stringify(body)}`);
    }
    console.log('Import-API Antwort:', body);
}

async function main() {
    const teamMappings = loadTeamMapping();
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();

    const allGames = [];
    let hadError = false;

    try {
        const { username, password } = await fetchDfbnetCredentials();
        await loginToDfbnet(page, username, password);

        for (const teamConfig of teamMappings) {
            try {
                const csvText = await exportSpielplanCsv(page, teamConfig);
                const rows = parseCsv(csvText);
                const games = mapRowsToGames(rows, teamConfig);
                console.log(`[${teamConfig.key}] ${games.length} VfL-Ramsdorf-Spiele gefunden.`);
                allGames.push(...games);
            } catch (err) {
                hadError = true;
                console.error(`[${teamConfig.key}] Fehler beim Export:`, err.message);
                await screenshot(page, `error-${teamConfig.key}`);
            }
        }

        await postGamesToApp(allGames);
    } catch (err) {
        hadError = true;
        console.error('Abbruch:', err.message);
        await screenshot(page, 'fatal-error');
    } finally {
        await browser.close();
    }

    process.exit(hadError ? 1 : 0);
}

main();
