import { DEMO_COMPANY } from './demo-data.js';
import { createProvider } from './providers.js';
import { domainSummary } from './report.js';
import { el, runConsole, focusHeading } from './ui.js';

const provider = createProvider('demo');
const $ = (id) => document.getElementById(id);

$('companyName').textContent = DEMO_COMPANY.name;
$('companyDomain').textContent = DEMO_COMPANY.domain;
$('companySize').textContent = DEMO_COMPANY.employees;
$('txtHost').textContent = DEMO_COMPANY.txt_host;
$('txtValue').textContent = DEMO_COMPANY.txt_value;

$('verifyBtn').addEventListener('click', async () => {
  $('verifyBtn').disabled = true;
  await runConsole($('verifyConsole'), [
    `DNSを確認：${DEMO_COMPANY.txt_host}`,
    'TXTレコードを確認：デモのため「設置済み」として扱います',
    '所有確認：完了（架空の会社）',
  ]);
  $('stepDashboard').hidden = false;
  $('openDashboard').focus();
});

$('openDashboard').addEventListener('click', async () => {
  $('openDashboard').disabled = true;
  await runConsole($('dashConsole'), [
    `社員アドレスを照会：@${DEMO_COMPANY.domain}`,
    '漏洩データ：デモ用の固定データを使用（外部には送信しません）',
  ]);
  const aliases = await provider.breachedAliasesForDomain(DEMO_COMPANY.domain);
  const catalog = provider.catalog();
  const summary = domainSummary(aliases, catalog);

  $('kpiAddresses').textContent = summary.affected_addresses;
  $('kpiPasswords').textContent = summary.addresses_with_password_exposure;
  $('kpiBreaches').textContent = summary.breaches.length;

  const bt = $('breachRows');
  bt.textContent = '';
  for (const b of summary.breaches) {
    const name = el('td', { text: b.title }, b.password ? [el('span', { class: 'badge-pw', text: 'パスワード' })] : []);
    bt.append(el('tr', {}, [name, el('td', { class: 'nowrap', text: b.date }), el('td', { text: String(b.addresses) })]));
  }
  const at = $('addressRows');
  at.textContent = '';
  for (const [alias, names] of Object.entries(aliases)) {
    at.append(el('tr', {}, [
      el('td', { class: 'mono', text: `${alias}@${DEMO_COMPANY.domain}` }),
      el('td', { text: names.map((n) => catalog[n].title).join('、') }),
    ]));
  }
  $('dashboard').hidden = false;
  focusHeading($('dashboard'));
});
