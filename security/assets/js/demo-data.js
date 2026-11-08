// FIXED FICTIONAL DATA for the demo. Nothing here describes a real person,
// company, service or breach. Every service name ends with "（架空）" and every
// domain uses the reserved ".example" TLD (RFC 2606).

export const DEMO_NOTICE = 'デモ・架空データ：実在のサービスや実際の漏洩情報ではありません。';

export const CATALOG = {
  DemoShopA: {
    title: 'デモ通販サイトA（架空）',
    domain: 'demo-shop-a.example',
    breach_date: '2023-04-12',
    data_classes: ['メールアドレス', 'パスワード', '氏名'],
  },
  DemoForumB: {
    title: 'デモ掲示板B（架空）',
    domain: 'demo-forum-b.example',
    breach_date: '2021-11-03',
    data_classes: ['メールアドレス', 'ユーザー名', 'IPアドレス'],
  },
  DemoFitnessC: {
    title: 'デモ健康アプリC（架空）',
    domain: 'demo-fitness-c.example',
    breach_date: '2024-08-20',
    data_classes: ['メールアドレス', '生年月日', '電話番号'],
  },
  DemoTravelD: {
    title: 'デモ旅行予約D（架空）',
    domain: 'demo-travel-d.example',
    breach_date: '2022-06-30',
    data_classes: ['メールアドレス', 'パスワード', '住所'],
  },
  DemoNewsE: {
    title: 'デモニュース会員E（架空）',
    domain: 'demo-news-e.example',
    breach_date: '2020-02-14',
    data_classes: ['メールアドレス'],
  },
};

// Sample people the visitor chooses from. No free-text input exists.
export const PERSONAS = [
  {
    id: 'high',
    label: '漏洩が多い例',
    email: 'yamada.demo@example.com',
    note: 'パスワードを含む漏洩が複数ある架空の利用者',
    breaches: ['DemoShopA', 'DemoTravelD', 'DemoForumB'],
  },
  {
    id: 'medium',
    label: '一部の情報が漏洩した例',
    email: 'sato.demo@example.jp',
    note: '電話番号と生年月日が流出した架空の利用者',
    breaches: ['DemoFitnessC', 'DemoNewsE'],
  },
  {
    id: 'none',
    label: '見つからなかった例',
    email: 'suzuki.demo@example.net',
    note: '収録データに含まれていなかった架空の利用者',
    breaches: [],
  },
];

// Sample passwords. Counts are FICTIONAL. The SHA-1 is computed in the browser
// only to illustrate which 5 characters a real service would send.
export const PASSWORD_SAMPLES = [
  { id: 'weak1', value: 'password123', count: 2480000 },
  { id: 'weak2', value: 'sakura1990', count: 3150 },
  { id: 'strong', value: 'umi-sora-hikari-2026!', count: 0 },
];

export const DEMO_COMPANY = {
  name: 'デモ商事株式会社（架空）',
  domain: 'demo-shoji.example',
  employees: '従業員 約40名（架空）',
  txt_host: '_rss7-security.demo-shoji.example',
  txt_value: 'rss7-security-verify=demo-0000-not-a-real-token',
  aliases: {
    'info': ['DemoNewsE'],
    'sales': ['DemoShopA', 'DemoForumB'],
    'support': ['DemoFitnessC'],
    'tanaka.demo': ['DemoShopA', 'DemoTravelD'],
    'kato.demo': ['DemoForumB'],
    'recruit': ['DemoTravelD'],
  },
};
