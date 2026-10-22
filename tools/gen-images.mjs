// สร้างรูปตัวอย่างสินค้า (placeholder) จนกว่าจะได้รูปจริงจากร้าน
// ใช้: node tools/gen-images.mjs <out-dir>   แล้วตามด้วย render ด้วย Chrome (ดู tools/build-images.sh)
import fs from "node:fs";
import path from "node:path";

const root = path.dirname(path.dirname(new URL(import.meta.url).pathname));
const out = process.argv[2];
fs.mkdirSync(out, { recursive: true });

const hash = (s) => [...s].reduce((h, c) => (h * 31 + c.charCodeAt(0)) | 0, 7) >>> 0;
const GREEN = "#7d9473";

function bloom(x, y, r, color, rot, petals = 6) {
  let s = "";
  for (let k = 0; k < petals; k++)
    s += `<ellipse cx="${x}" cy="${y - r * 0.55}" rx="${r * 0.46}" ry="${r * 0.62}" fill="${color}" stroke="rgba(70,40,45,.14)" stroke-width=".8" transform="rotate(${(k * 360) / petals + rot} ${x} ${y})"/>`;
  return s + `<circle cx="${x}" cy="${y}" r="${r * 0.3}" fill="#f1cf7a" stroke="rgba(70,40,45,.15)" stroke-width=".8"/>`;
}
const leaf = (x, y, a, s = 1) => `<ellipse cx="${x}" cy="${y}" rx="${6 * s}" ry="${17 * s}" fill="${GREEN}" transform="rotate(${a} ${x} ${y})"/>`;
const fruit = (x, y, r, c) => `<circle cx="${x}" cy="${y}" r="${r}" fill="${c}"/><circle cx="${x - r * 0.35}" cy="${y - r * 0.35}" r="${r * 0.25}" fill="#fff" opacity=".35"/>`;

function cluster(cx, cy, colors, seed, spread = 1, size = 17) {
  const spots = [[0, -22], [-26, -6], [26, -6], [-12, 12], [12, 12], [-38, 16], [38, 16]];
  let s = leaf(cx - 42, cy + 30, -40) + leaf(cx + 42, cy + 30, 40) + leaf(cx - 30, cy - 18, -60) + leaf(cx + 30, cy - 18, 60);
  spots.forEach(([dx, dy], i) => {
    const j = ((seed >> i) % 7) - 3;
    s += bloom(cx + dx * spread + j, cy + dy * spread + j, size + (i === 0 ? 3 : 0), colors[i % colors.length], (seed % 40) + i * 13);
  });
  return s;
}

function art(kind, colors, seed) {
  const c = (i) => colors[i % colors.length];
  switch (kind) {
    case "bouquet":
      return [[100, 62], [74, 78], [126, 78], [88, 96], [112, 96]].map(([x, y]) => `<line x1="${x}" y1="${y}" x2="100" y2="172" stroke="${GREEN}" stroke-width="3"/>`).join("") +
        cluster(100, 84, colors, seed) +
        `<path d="M46 110 L154 110 L112 190 L88 190 Z" fill="#efe1cc" stroke="#cdb594" stroke-width="1.2"/>
         <path d="M46 110 L100 130 L154 110" fill="none" stroke="#cdb594" stroke-width="1.2"/>
         <path d="M84 150 q16 12 32 0 M100 155 l-11 20 M100 155 l11 20" stroke="#b76e79" stroke-width="3" fill="none" stroke-linecap="round"/>`;
    case "box":
      return cluster(100, 92, colors, seed, 1.05) +
        `<rect x="48" y="118" width="104" height="66" rx="5" fill="#3a2e2e"/><rect x="48" y="118" width="104" height="9" fill="#4a3c3c"/>
         <rect x="94" y="118" width="12" height="66" fill="#c8a97e"/><text x="100" y="160" text-anchor="middle" font-family="Georgia" font-size="7" fill="#c8a97e" letter-spacing="1.5">MY MEMORIES</text>`;
    case "vase":
      return `<path d="M100 120 C 92 80 70 60 64 40 M100 120 C 108 78 128 58 138 42" stroke="${GREEN}" stroke-width="3" fill="none"/>` +
        [[64, 42], [78, 56], [92, 74], [138, 44], [124, 60], [110, 80], [100, 34]].map(([x, y], i) => bloom(x, y, 11, c(i), i * 20, 5)).join("") +
        `<path d="M74 120 L126 120 Q134 152 120 188 L80 188 Q66 152 74 120 Z" fill="#f4f0e8" stroke="#cdc3b2" stroke-width="1.2"/><path d="M78 140 Q100 147 122 140" stroke="#c8a97e" fill="none"/>`;
    case "basket":
    case "fruit": {
      let s = `<path d="M52 112 Q100 30 148 112" stroke="#b08a5a" stroke-width="6" fill="none"/>`;
      if (kind === "fruit") {
        s += leaf(60, 112, -50) + leaf(140, 112, 50);
        [[70, 112, 15], [96, 104, 17], [124, 110, 15], [84, 92, 13], [112, 90, 12]].forEach(([x, y, r], i) => (s += fruit(x, y, r, c(i))));
        s += bloom(100, 82, 10, "#f2b8c6", 10);
      } else s += cluster(100, 98, colors, seed, 1.15, 16);
      for (let i = 0; i < 4; i++) s += `<path d="M46 ${118 + i * 16} L154 ${118 + i * 16}" stroke="#a37d4f" stroke-width="2"/>`;
      return s + `<path d="M44 114 L156 114 L144 184 L56 184 Z" fill="#c9a06b" opacity=".85"/>` +
        [0, 1, 2, 3].map((i) => `<path d="M48 ${124 + i * 15} L152 ${124 + i * 15}" stroke="#a37d4f" stroke-width="1.5"/>`).join("");
    }
    case "fruit-bouquet":
      return `<line x1="100" y1="80" x2="100" y2="170" stroke="${GREEN}" stroke-width="3"/>` + leaf(62, 96, -50) + leaf(138, 96, 50) +
        [[76, 82, 14], [100, 70, 16], [124, 82, 14], [88, 100, 13], [112, 100, 13]].map(([x, y, r], i) => fruit(x, y, r, c(i))).join("") +
        bloom(100, 92, 10, c(2), 0) +
        `<path d="M46 110 L154 110 L112 190 L88 190 Z" fill="#efe1cc" stroke="#cdb594"/><path d="M84 150 q16 12 32 0" stroke="#b76e79" stroke-width="3" fill="none"/>`;
    case "gift":
      return `<rect x="42" y="96" width="116" height="88" rx="6" fill="${c(1)}"/><rect x="42" y="96" width="116" height="14" fill="${c(0)}"/>
        <rect x="58" y="70" width="20" height="40" rx="4" fill="#fff8ec" stroke="#cdc3b2"/><rect x="84" y="60" width="18" height="50" rx="3" fill="#8a6a4a"/>
        <circle cx="128" cy="92" r="16" fill="#f28c28"/><circle cx="114" cy="98" r="10" fill="#d7263d"/>` + bloom(146, 84, 12, "#ffffff", 0) +
        `<rect x="94" y="96" width="12" height="88" fill="${c(2)}"/><path d="M100 96 q-18 -18 -24 -4 q6 10 24 4 q18 6 24 -4 q-6 -14 -24 4" fill="${c(2)}"/>`;
    case "wreath": {
      let s = `<circle cx="100" cy="92" r="56" fill="none" stroke="${GREEN}" stroke-width="16"/>`;
      for (let i = 0; i < 14; i++) {
        const a = (i / 14) * Math.PI * 2;
        s += bloom(100 + Math.cos(a) * 56, 92 + Math.sin(a) * 56, 12, c(i), i * 17);
      }
      return s + `<line x1="100" y1="148" x2="100" y2="158" stroke="#c8a97e" stroke-width="2"/><rect x="76" y="156" width="48" height="30" rx="3" fill="#fffaf0" stroke="#c8a97e"/>`;
    }
    case "garland": {
      let s = `<path d="M60 40 Q100 60 140 40" stroke="#c8a97e" stroke-width="2" fill="none"/>`;
      for (let i = 0; i < 9; i++) s += `<ellipse cx="${60 + i * 0.1}" cy="${48 + i * 11}" rx="9" ry="7" fill="${c(i % 2)}" stroke="#d8d0bd" transform="translate(${i * 0}, 0)"/>`;
      for (let i = 0; i < 9; i++) s += `<ellipse cx="140" cy="${48 + i * 11}" rx="9" ry="7" fill="${c(i % 2)}" stroke="#d8d0bd"/>`;
      s += `<path d="M60 146 Q100 196 140 146" stroke="#c8a97e" stroke-width="2" fill="none"/>`;
      s += bloom(100, 168, 13, "#e8577a", 0) + bloom(84, 162, 9, c(2), 10) + bloom(116, 162, 9, c(2), 10);
      return s + `<path d="M94 180 L90 196 M100 182 L100 198 M106 180 L110 196" stroke="#e9b44c" stroke-width="2"/>`;
    }
  }
}

function page(svg, w, h, bg) {
  return `<!doctype html><meta charset="utf-8"><style>html,body{margin:0;width:${w}px;height:${h}px;overflow:hidden}
  body{background:${bg}}svg{display:block;width:${w}px;height:${h}px}</style>${svg}`;
}

const jobs = [];
const products = JSON.parse(fs.readFileSync(path.join(root, "data/products.json"), "utf8"));
for (const p of products) {
  const seed = hash(p.slug);
  const svg = `<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
    <defs><radialGradient id="g" cx="50%" cy="45%" r="65%"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="${p.colors[0]}" stop-opacity=".16"/></radialGradient></defs>
    <rect width="200" height="200" fill="#fbf7f2"/><rect width="200" height="200" fill="url(#g)"/>
    <g transform="translate(14 12) scale(.86)">${art(p.art, p.colors, seed)}</g></svg>`;
  jobs.push({ name: p.slug, w: 900, h: 900, html: page(svg, 900, 900, "#fbf7f2") });
}

// ภาพบรรยากาศ: hero, event, party, ลูกค้า
const scene = (name, w, h, groups, bg, label) => {
  const vb = `0 0 ${w / 4} ${h / 4}`;
  const g = groups.map(([kind, x, y, s, colors]) => `<g transform="translate(${x} ${y}) scale(${s})">${art(kind, colors, hash(name + x))}</g>`).join("");
  const svg = `<svg viewBox="${vb}" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="${bg}"/>
    <circle cx="${w / 8}" cy="${h / 7}" r="${h / 4}" fill="#fff" opacity=".35"/>${g}
    ${label ? `<text x="${w / 8}" y="${h / 4 - 10}" text-anchor="middle" font-family="Georgia" font-size="9" letter-spacing="3" fill="#8b6b6f">${label}</text>` : ""}</svg>`;
  jobs.push({ name, w, h, html: page(svg, w, h, bg) });
};
const ROSE = ["#f2b8c6", "#e795aa", "#fff3e6", "#d97a92"];
scene("hero", 1600, 900, [["vase", 150, 58, 0.75, ["#e9c6b8", "#f2b8c6", "#fff3e6"]], ["bouquet", 212, 12, 0.98, ROSE], ["box", 290, 96, 0.58, ["#ffffff", "#f2b8c6", "#e795aa"]]], "#f3e4e1");
scene("event", 1600, 1000, [["wreath", 100, 12, 1.0, ["#ffffff", "#f2b8c6", "#e9c6b8"]], ["vase", 10, 80, 0.8, ROSE], ["vase", 230, 80, 0.8, ROSE]], "#efe6dc");
scene("party", 1600, 1000, [["basket", 10, 70, 0.8, ["#f4a259", "#f7d488", "#e8778d"]], ["box", 140, 75, 0.8, ["#fff3e6", "#e795aa", "#c63d5d"]], ["bouquet", 260, 60, 0.85, ["#d7c3ec", "#f6d5df", "#fff1c7"]]], "#f1e7ef");
for (const [name, bg, kind, colors] of [
  ["customer-client", "#f3e4e1", "bouquet", ROSE],
  ["customer-celebrity", "#ece3f1", "box", ["#c63d5d", "#e07c93", "#fff3e6"]],
  ["customer-organization", "#e6ece4", "vase", ["#a066b8", "#c9a3d8", "#ffffff"]],
  ["customer-brand", "#f2ebdd", "basket", ["#f4a259", "#f7d488", "#e8778d"]],
]) scene(name, 1200, 900, [[kind, 55, 15, 0.95, colors]], bg);

for (const j of jobs) fs.writeFileSync(path.join(out, `${j.name}.html`), j.html);
fs.writeFileSync(path.join(out, "jobs.txt"), jobs.map((j) => `${j.name} ${j.w} ${j.h}`).join("\n") + "\n");
console.log(`${jobs.length} pages written`);
