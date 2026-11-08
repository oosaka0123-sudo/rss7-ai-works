import { PERSONAS, PASSWORD_SAMPLES } from './demo-data.js';
import { createProvider } from './providers.js';
import { buildReport } from './report.js';
import { sha1Hex, splitHash } from './pwned-core.js';
import { el, runConsole, focusHeading } from './ui.js';

const provider = createProvider('demo');
const $ = (id) => document.getElementById(id);

const RISK = {
  none: ['見つかりませんでした', '0'],
  low: ['影響は限定的', '低'],
  medium: ['注意が必要', '中'],
  high: ['早めの対応が必要', '高'],
};

// ---- Email demo ---------------------------------------------------------
const personaList = $('personaList');
PERSONAS.forEach((p, i) => {
  const input = el('input', { type: 'radio', name: 'persona', value: p.id });
  if (i === 0) input.checked = true;
  personaList.append(el('label', { class: 'choice' }, [
    input,
    el('div', {}, [el('b', { text: p.label }), el('span', { class: 'mono', text: p.email }), el('span', { text: p.note })]),
  ]));
});

$('emailDemo').addEventListener('submit', async (event) => {
  event.preventDefault();
  const button = event.target.querySelector('button[type=submit]');
  const persona = PERSONAS.find((p) => p.id === new FormData(event.target).get('persona'));
  button.disabled = true;
  $('emailResult').hidden = true;
  await runConsole($('emailConsole'), [
    `対象：${persona.email}（架空）`,
    '本人確認：デモのため省略（実際はメールの確認リンクで本人確認）',
    '漏洩データを照会：デモ用の固定データを使用（外部には送信しません）',
    'レポートを作成：確認済みのデータだけを根拠に作成',
  ]);
  const breaches = await provider.breachesForPersona(persona.id);
  renderEmail(persona, buildReport(breaches, provider.isDemo), breaches);
  button.disabled = false;
});

function renderEmail(persona, report, breaches) {
  const { facts } = report;
  const [label, mark] = RISK[facts.risk_level];
  const head = $('emailHead');
  head.className = 'result-head risk-' + facts.risk_level;
  head.querySelector('b').textContent = String(facts.breach_count);
  head.querySelector('.risk-label').textContent = `リスク：${mark}（${label}）`;
  head.querySelector('h3').textContent = persona.email;
  head.querySelector('p').textContent = report.summary;

  const list = $('breachList');
  list.textContent = '';
  for (const b of breaches) {
    list.append(el('article', { class: 'breach' }, [
      el('h4', { text: b.title }),
      el('p', { class: 'meta', text: `漏洩の発生日：${b.breach_date}（架空）` }),
      el('div', { class: 'tags' }, b.data_classes.map((c) => el('span', { class: 'tag' + (c === 'パスワード' ? ' danger' : ''), text: c }))),
    ]));
  }
  $('breachEmpty').hidden = breaches.length > 0;
  $('breachSection').hidden = breaches.length === 0;

  const actions = $('actionList');
  actions.textContent = '';
  report.actions.forEach((t) => actions.append(el('li', { text: t })));
  $('emailDisclaimer').textContent = report.disclaimer + ' このページの結果はデモ用の架空データです。';

  $('emailResult').hidden = false;
  focusHeading($('emailResult'));
}

// ---- Password demo --------------------------------------------------------
const pwList = $('pwList');
PASSWORD_SAMPLES.forEach((s, i) => {
  const input = el('input', { type: 'radio', name: 'sample', value: s.id });
  if (i === 0) input.checked = true;
  pwList.append(el('label', { class: 'choice' }, [input, el('div', {}, [el('b', { class: 'mono', text: s.value }), el('span', { text: 'サンプルのパスワード（架空）' })])]));
});

$('pwDemo').addEventListener('submit', async (event) => {
  event.preventDefault();
  const sample = PASSWORD_SAMPLES.find((s) => s.id === new FormData(event.target).get('sample'));
  // SHA-1 is computed locally to show the mechanism. Nothing is sent.
  const { prefix, suffix } = splitHash(await sha1Hex(sample.value));
  const count = await provider.passwordCount(sample.id);
  $('pwPrefix').textContent = prefix;
  $('pwRest').textContent = suffix;
  const head = $('pwHead');
  head.className = 'result-head ' + (count > 0 ? 'risk-high' : 'risk-none');
  head.querySelector('b').textContent = count > 0 ? '!' : 'OK';
  head.querySelector('.risk-label').textContent = count > 0 ? '使わないでください' : '収録なし';
  head.querySelector('h3').textContent = count > 0
    ? `漏洩データに${count.toLocaleString('ja-JP')}回含まれていました（架空の件数）`
    : '漏洩データには含まれていませんでした（デモ）';
  head.querySelector('p').textContent = count > 0
    ? '広く出回っているパスワードは、総当たり攻撃で最初に試されます。別の長いパスワードかパスキーに変更してください。'
    : '含まれていないことは「安全」の証明ではありません。単語を3つ以上つなげた長いパスワードがおすすめです。';
  $('pwResult').hidden = false;
  focusHeading($('pwResult'));
});
