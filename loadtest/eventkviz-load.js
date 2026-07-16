/*
 * EventKviz load test (k6) — opakovateľný, config-driven.
 * Simuluje reálnych hráčov: načítanie vstupu + stránok kvízov + ŤAŽKÝCH assetov
 * (audio hudobného kvízu, video filmového, geojson mapového). Per-typ metriky cez tagy.
 *
 * Spúšťa sa cez run.sh (ten posiela -e premenné). Scenáre: smoke | realistic | burst | ramp-to-break.
 *
 * POZN.: GET stránok kvízu vytvorí v DB „question_set" pre lt-userov daného eventu
 * → testuj LEN proti dedikovanému test eventu (AKCIA=loadtest) a po teste uprac.
 */
import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Counter } from 'k6/metrics';

const BASE  = __ENV.BASE_URL || 'https://eventkviz.sk';
const AKCIA = __ENV.AKCIA || 'loadtest';
const MQ    = __ENV.MQ || '';
const TYPES = (__ENV.TYPES || 'hub,music,movies,knowledge,sudoku,mapa').split(',').map(s => s.trim()).filter(Boolean);
const FETCH_ASSETS = (__ENV.FETCH_ASSETS || 'true') === 'true';
const MAP_GEOJSON  = __ENV.MAP_GEOJSON || '';
const SCENARIO = __ENV.SCENARIO || 'realistic';
const VUS = parseInt(__ENV.VUS || '50', 10);

const SLUG = { music: 'aqljk', movies: 'merdfghh', knowledge: 'kwersdfzx', sudoku: 'sweertydfd' };
const errors = new Counter('ek_errors');

function thresholds() {
  return {
    http_req_failed:   ['rate<0.02'],            // < 2 % chybných requestov
    http_req_duration: ['p(95)<2000', 'p(99)<5000'],
  };
}

export const options = (() => {
  const base = { thresholds: thresholds() };
  switch (SCENARIO) {
    case 'smoke':          // 1 VU, validácia skriptu — zanedbateľná záťaž
      return { ...base, scenarios: { smoke: { executor: 'shared-iterations', vus: 1, iterations: 3, maxDuration: '1m' } } };
    case 'burst':          // všetci naraz (najhorší prípad — koniec časovaného kola)
      return { ...base, scenarios: { burst: { executor: 'per-vu-iterations', vus: VUS, iterations: 1, maxDuration: '2m' } } };
    case 'ramp-to-break':  // dvíhaj kým nezačne padať → zisti skutočný strop
      return { ...base, scenarios: { ramp: { executor: 'ramping-vus', startVUs: 0, stages: [
        { duration: '1m', target: 25 }, { duration: '1m', target: 50 },
        { duration: '1m', target: 100 }, { duration: '1m', target: 200 }, { duration: '1m', target: 0 } ] } } };
    default:               // realistic — ramp na VUS, drž, dobehni
      return { ...base, scenarios: { realistic: { executor: 'ramping-vus', startVUs: 0, stages: [
        { duration: '2m', target: VUS }, { duration: '5m', target: VUS }, { duration: '1m', target: 0 } ] } } };
  }
})();

// realistický „think time" hráča; pri burst žiadny (všetci naraz)
function think() { if (SCENARIO === 'burst' || SCENARIO === 'smoke') return; sleep(Math.random() * 25 + 8); }

function hit(url, name) {
  const r = http.get(url, { tags: { name } });
  const ok = check(r, { [`${name} ok`]: x => x.status === 200 || x.status === 302 });
  if (!ok) errors.add(1, { name });
  return r;
}

// vytiahne prvý <source src="..."> (audio/video) a stiahne ho — test prenosu
function fetchAsset(html, type) {
  if (!FETCH_ASSETS || !html) return;
  const m = html.match(/<source[^>]+src=["']([^"']+)["']/i);
  if (m && m[1]) hit(m[1].replace(/&amp;/g, '&'), `${type}_asset`);
}

export default function () {
  const user = `lt${__VU}_${__ITER}`;

  if (TYPES.includes('hub')) {
    group('hub', () => hit(`${BASE}/eventkviz-vstup/?akcia=${AKCIA}`, 'hub'));
    think();
  }

  ['music', 'movies', 'knowledge', 'sudoku'].forEach(t => {
    if (!TYPES.includes(t)) return;
    group(t, () => {
      const r = hit(`${BASE}/${SLUG[t]}/?akcia=${AKCIA}&user=${user}`, `${t}_page`);
      if (t === 'music' || t === 'movies') fetchAsset(r.body, t);
    });
    think();
  });

  if (TYPES.includes('mapa') && MQ) {
    group('mapa', () => {
      hit(`${BASE}/mapa-quiz/?akcia=${AKCIA}&mq=${MQ}&user=${user}`, 'mapa_page');
      if (FETCH_ASSETS && MAP_GEOJSON) hit(MAP_GEOJSON, 'mapa_geojson');
    });
    think();
  }
}
