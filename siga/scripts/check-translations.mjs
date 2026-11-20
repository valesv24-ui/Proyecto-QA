/**
 * Lista las claves __('...') usadas en el código que faltan en lang/es.json.
 *
 * Existe porque una clave sin traducir NO rompe nada: Laravel devuelve la
 * clave tal cual, así que el texto simplemente aparece en inglés en medio de
 * una pantalla en español. Revisarlo a ojo sobre cientos de cadenas
 * repartidas en decenas de archivos no es realista; esto lo vuelve una
 * comprobación de un comando con resultado binario.
 *
 * Está en Node y no en PHP a propósito: Node ya es requisito del proyecto
 * (Vite), y así el chequeo corre aunque el entorno de PHP esté a medio
 * instalar.
 *
 * Uso:
 *   node scripts/check-translations.mjs
 *
 * Sale con código 1 si falta alguna clave, para poder encadenarlo en CI.
 */
import { readFileSync, readdirSync, statSync } from "node:fs";
import { join, extname } from "node:path";

const SCAN_DIRS = ["src", "app", "resources/views", "database"];
const LANG_FILE = "lang/es.json";

/** Recorre un directorio y devuelve todos los .php (incluidos los .blade.php). */
function phpFilesIn(dir) {
  let found = [];

  for (const entry of readdirSync(dir)) {
    const path = join(dir, entry);

    if (statSync(path).isDirectory()) {
      found = found.concat(phpFilesIn(path));
    } else if (extname(path) === ".php") {
      found.push(path);
    }
  }

  return found;
}

/**
 * Extrae las claves de __('...') y __("...").
 *
 * Se hace en JavaScript y no con grep porque las claves pueden contener
 * comillas escapadas (por ejemplo "Don't have an account?"), y una tubería
 * de grep + sed las parte por la mitad — de hecho eso fue lo que pasó en el
 * primer intento, que reportaba una clave inexistente llamada "Don\".
 */
function keysIn(source) {
  const keys = [];
  const patterns = [/__\(\s*'((?:[^'\\]|\\.)*)'/g, /__\(\s*"((?:[^"\\]|\\.)*)"/g];

  for (const pattern of patterns) {
    for (const match of source.matchAll(pattern)) {
      // Se desescapan solo las comillas: es el único escape que el
      // delimitador obliga a poner, y dejarlo convertiría la clave en una
      // que no existe en el JSON.
      keys.push(match[1].replace(/\\'/g, "'").replace(/\\"/g, '"'));
    }
  }

  return keys;
}

const used = new Set();
for (const dir of SCAN_DIRS) {
  for (const file of phpFilesIn(dir)) {
    for (const key of keysIn(readFileSync(file, "utf8"))) {
      used.add(key);
    }
  }
}

const translated = JSON.parse(readFileSync(LANG_FILE, "utf8"));
const missing = [...used].filter((key) => !(key in translated)).sort();

console.log(`${used.size} claves usadas · ${missing.length} sin traducir`);
for (const key of missing) {
  console.log(`  - ${key}`);
}

process.exit(missing.length === 0 ? 0 : 1);
