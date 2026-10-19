// Gera as ilustrações SVG decorativas e os ícones a partir dos mockups em .spec/init/design/.
// Uso: node scripts/extract-design-art.mjs
import { readFileSync, writeFileSync } from 'node:fs';

const vitrine = readFileSync('.spec/init/design/Orvalha_Semijoias.html', 'utf8').split('\n');
const admin = readFileSync('.spec/init/design/Orvalha_Admin.html', 'utf8').split('\n');

const slice = (lines, from, to) => lines.slice(from - 1, to).join('\n');

// --- ilustrações (vitrine) ---
const artSource = slice(vitrine, 701, 731);
const metal = `function metal(m){return m==="Prata 925"?"url(#silver)":"url(#gold)";}`;
const linkStart = artSource.indexOf('function linkChain');
const artBody = artSource.slice(linkStart);
const factory = new Function(`${metal}\n${artBody}\nreturn {ART, svg};`);
const { ART, svg } = factory();

const star = (x, y, s, d) => `<path class="spark" style="animation-delay:${d}s" d="M${x} ${y - s} Q${x} ${y} ${x + s} ${y} Q${x} ${y} ${x} ${y + s} Q${x} ${y} ${x - s} ${y} Q${x} ${y} ${x} ${y - s}Z" fill="#FFFFFF"/>`;

const pieces = {
  hero: `<g class="swing">${ART.riviera('Ouro 18k')}<g transform="translate(0 30)">${ART.point('Ouro 18k')}</g></g>${star(40, 150, 9, 0)}${star(168, 140, 7, 1.1)}${star(150, 30, 6, 2)}${star(30, 30, 5, 0.6)}${star(100, 182, 6, 1.6)}`,
  'banner-gold': ART.hoop('Ouro 18k'),
  'banner-silver': ART.tennis('Prata 925'),
  help: `<g class="swing">${ART.heart('Ouro 18k')}</g><g transform="translate(118 112) scale(0.42)">${ART.drop('Ouro 18k')}</g>${star(38, 150, 8, 0)}${star(160, 40, 6, 1)}${star(30, 40, 5, 1.8)}`,
  guide: ART.pearlset('Ouro 18k'),
};
for (const [name, inner] of Object.entries(pieces)) {
  writeFileSync(`resources/views/partials/art/${name}.blade.php`, inner + '\n');
}

// peças ilustradas (usadas como fallback quando a peça/categoria não tem foto)
const arts = {};
for (const key of Object.keys(ART)) {
  arts[key] = { gold: ART[key]('Ouro 18k'), silver: ART[key]('Prata 925') };
}
const php = (v) => JSON.stringify(v).replace(/\\\//g, '/').replace(/\$/g, '\\$');
writeFileSync(
  'resources/views/partials/art/illustrations.blade.php',
  `@php\n$illustrations = json_decode(<<<'JSON'\n${JSON.stringify(arts)}\nJSON, true);\n@endphp\n`,
);

// --- ícones (admin) ---
const iconLine = admin.findIndex((l) => l.includes('var ICON = {')) + 1;
const iconEnd = admin.findIndex((l, i) => i > iconLine && l.trim() === '};') + 1;
const icons = new Function(`${slice(admin, iconLine, iconEnd)}\nreturn ICON;`)();
writeFileSync('resources/views/components/icon.blade.php', `@props(['name'])\n@php\n$icons = json_decode(<<<'JSON'\n${JSON.stringify(icons)}\nJSON, true);\n@endphp\n{!! $icons[$name] ?? '' !!}\n`);
console.log('ok', Object.keys(icons).join(','), Object.keys(ART).join(','));
