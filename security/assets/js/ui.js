// Small DOM helpers shared by the demo pages. No network, no storage.

export function el(tag, attrs = {}, children = []) {
  const node = document.createElement(tag);
  for (const [key, value] of Object.entries(attrs)) {
    if (key === 'text') node.textContent = value;
    else if (key === 'class') node.className = value;
    else node.setAttribute(key, value);
  }
  for (const child of children) if (child) node.append(child);
  return node;
}

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const wait = (ms) => new Promise((r) => setTimeout(r, reduceMotion() ? 0 : ms));

/** Plays the demo "scan" console. Lines are fixed text, not real work. */
export async function runConsole(consoleEl, lines) {
  const body = consoleEl.querySelector('.console-body');
  body.textContent = '';
  consoleEl.hidden = false;
  consoleEl.classList.add('running');
  for (const line of lines) {
    const p = el('p', { text: '> ' + line });
    body.append(p);
    await wait(30);
    p.classList.add('on');
    await wait(380);
  }
  const done = el('p', { class: 'done on', text: '> 完了（デモ・架空データを表示します）' });
  body.append(done);
  consoleEl.classList.remove('running');
  await wait(250);
}

export function focusHeading(container) {
  const h = container.querySelector('h2, h3');
  if (!h) return;
  h.setAttribute('tabindex', '-1');
  h.focus({ preventScroll: true });
  container.scrollIntoView({ behavior: reduceMotion() ? 'auto' : 'smooth', block: 'start' });
}
