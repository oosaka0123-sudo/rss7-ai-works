// Builds a diagnosis report from provider facts only (ported from v0.1 PHP).
// Risk level and actions are deterministic rules. The summary text is a
// template; assertGrounded() rejects any number or "（架空）" service name that
// is not in the facts, so a future AI narrator cannot invent breaches.

const ADVICE = {
  'パスワード': { priority: 1, text: '該当サービスと、同じパスワードを使っている全サービスのパスワードを変更する' },
  '電話番号': { priority: 2, text: 'SMSによる偽の宅配・料金通知（スミッシング）に注意する' },
  '住所': { priority: 2, text: '住所を知っていることを装った詐欺や郵送物に注意する' },
  '生年月日': { priority: 2, text: '生年月日を使った暗証番号や秘密の質問を見直す' },
  'メールアドレス': { priority: 3, text: '「パスワード再設定」「料金未払い」などの不審なメールのリンクを開かない' },
  '氏名': { priority: 3, text: '氏名入りのメールでも、公式サイトやアプリから直接確認する' },
  'ユーザー名': { priority: 3, text: '同じユーザー名を使っている他サービスのログイン履歴を確認する' },
  'IPアドレス': { priority: 4, text: '見覚えのないログイン通知が届いたら、すぐにパスワードを変更する' },
};

const GENERAL = [
  '重要なサービスで二段階認証またはパスキーを有効にする',
  'パスワード管理アプリを使い、サービスごとに異なるパスワードにする',
];

export function riskLevel(classes, count) {
  if (count === 0) return 'none';
  if (classes.includes('パスワード')) return 'high';
  if (classes.some((c) => ['電話番号', '住所', '生年月日'].includes(c)) || count >= 3) return 'medium';
  return 'low';
}

export function summarize(facts) {
  if (facts.breach_count === 0) {
    return '確認したデータの範囲では、このメールアドレスが含まれる漏洩は見つかりませんでした。'
      + 'すべての漏洩が公表・収録されているわけではないため、「安全である」ことの証明にはなりません。';
  }
  let text = `このメールアドレスは${facts.breach_count}件の漏洩に含まれていました（${facts.services.join('、')}）。`
    + `流出した可能性がある情報の種類は「${facts.data_classes.join('、')}」です。`;
  if (facts.latest_date) text += `最も新しい漏洩の発生日は${facts.latest_date}です。`;
  text += {
    high: 'パスワードが含まれているため、同じパスワードを使い回しているサービスから優先して変更してください。',
    medium: 'パスワードは含まれていませんが、なりすましメールや詐欺の連絡に注意してください。',
    low: '影響は限定的と考えられますが、不審な連絡には注意してください。',
  }[facts.risk_level];
  return text;
}

export function assertGrounded(summary, facts) {
  const allowed = new Set([String(facts.breach_count)]);
  if (facts.latest_date) allowed.add(facts.latest_date);
  for (const token of summary.match(/\d+(?:-\d+)*/g) || []) {
    if (!allowed.has(token)) throw new Error(`ungrounded number: ${token}`);
  }
  for (const service of summary.match(/[^、（）「」。\s]+（架空）/g) || []) {
    if (!facts.services.includes(service)) throw new Error(`unknown service: ${service}`);
  }
}

export function buildReport(breaches, isDemo, narrator = summarize) {
  const classes = [];
  let latest = null;
  for (const b of breaches) {
    for (const c of b.data_classes) if (!classes.includes(c)) classes.push(c);
    if (!latest || b.breach_date > latest) latest = b.breach_date;
  }
  const facts = {
    breach_count: breaches.length,
    risk_level: riskLevel(classes, breaches.length),
    services: breaches.map((b) => b.title),
    data_classes: classes,
    latest_date: latest,
  };
  const summary = narrator(facts);
  assertGrounded(summary, facts);
  const specific = classes
    .filter((c) => ADVICE[c])
    .map((c) => ADVICE[c])
    .sort((a, b) => a.priority - b.priority)
    .map((a) => a.text);
  return {
    is_demo: isDemo,
    facts,
    summary,
    actions: [...new Set([...specific, ...GENERAL])],
    disclaimer: '漏洩データベースに収録された情報のみに基づく結果です。未収録・未公表の漏洩は判定できません。',
  };
}

export function domainSummary(aliases, catalog) {
  const perBreach = {};
  let withPassword = 0;
  for (const names of Object.values(aliases)) {
    if (names.some((n) => catalog[n].data_classes.includes('パスワード'))) withPassword++;
    for (const n of names) perBreach[n] = (perBreach[n] || 0) + 1;
  }
  return {
    affected_addresses: Object.keys(aliases).length,
    addresses_with_password_exposure: withPassword,
    breaches: Object.entries(perBreach)
      .sort((a, b) => b[1] - a[1] || catalog[b[0]].breach_date.localeCompare(catalog[a[0]].breach_date))
      .map(([n, count]) => ({ title: catalog[n].title, date: catalog[n].breach_date, addresses: count, password: catalog[n].data_classes.includes('パスワード') })),
  };
}
