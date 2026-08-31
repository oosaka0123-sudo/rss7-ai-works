import { readFileSync, readdirSync } from 'node:fs';
import { join } from 'node:path';

function htmlFiles(directory = '.') {
  return readdirSync(directory, { withFileTypes: true }).flatMap(entry => {
    if (entry.name === '.git') return [];
    const path = join(directory, entry.name);
    return entry.isDirectory() ? htmlFiles(path) : (entry.name.endsWith('.html') ? [path] : []);
  });
}

let failed = false;
for (const file of htmlFiles()) {
  const html = readFileSync(file, 'utf8');
  const scripts = [...html.matchAll(/<script([^>]*)>([\s\S]*?)<\/script>/gi)]
    .filter(match => !/type=["']application\/ld\+json["']/i.test(match[1]));
  scripts.forEach((match, index) => {
    try {
      new Function(match[2]);
    } catch (error) {
      failed = true;
      console.error(`${file}: inline script ${index + 1}: ${error.message}`);
    }
  });
}
if (failed) process.exit(1);
console.log('OK: inline JavaScript syntax');
