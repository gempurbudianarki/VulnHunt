import puppeteer from 'puppeteer-core';
import lighthouse from 'file:///C:/Users/gmprbdarki/AppData/Local/npm-cache/_npx/0f94ee7615faf582/node_modules/lighthouse/core/index.js';
import fs from 'fs';
import path from 'path';

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const outDir = path.resolve('docs/lighthouse');
fs.mkdirSync(outDir, { recursive: true });

async function auditPage(url, name, port) {
  console.log(`\n========================================`);
  console.log(`Running Lighthouse Audit: ${name} (${url})`);
  console.log(`========================================`);

  const res = await lighthouse(url, {
    port,
    output: 'json',
    logLevel: 'error',
    formFactor: 'mobile',
    screenEmulation: {
      mobile: true,
      width: 375,
      height: 812,
      deviceScaleFactor: 2,
      disabled: false
    },
    throttlingMethod: 'simulate',
  });

  const lhr = res.lhr;
  const categories = {};
  for (const [k, v] of Object.entries(lhr.categories)) {
    categories[k] = Math.round(v.score * 100);
  }

  const result = {
    name,
    url,
    fetchTime: lhr.fetchTime,
    scores: categories,
    audits: {
      fcp: lhr.audits['first-contentful-paint']?.displayValue,
      lcp: lhr.audits['largest-contentful-paint']?.displayValue,
      cls: lhr.audits['cumulative-layout-shift']?.displayValue,
      tbt: lhr.audits['total-blocking-time']?.displayValue,
      speedIndex: lhr.audits['speed-index']?.displayValue,
    }
  };

  fs.writeFileSync(path.join(outDir, `${name}.json`), JSON.stringify(lhr, null, 2), 'utf-8');
  console.log(`Audit ${name} complete:`, categories);
  return result;
}

async function main() {
  const browser = await puppeteer.launch({
    executablePath: chromePath,
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu', '--remote-debugging-port=9222']
  });

  const results = [];

  try {
    // 1. Landing Page
    const landingRes = await auditPage('http://127.0.0.1:8000/', 'landing-mobile', 9222);
    results.push(landingRes);

    // 2. Login Page
    const loginRes = await auditPage('http://127.0.0.1:8000/login', 'login-mobile', 9222);
    results.push(loginRes);

    // Perform Login via Puppeteer
    console.log('\n[Auth] Logging in as peserta@ubbg.ac.id...');
    const page = await browser.newPage();
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle2' });
    await page.type('input[name="email"]', 'peserta@ubbg.ac.id');
    await page.type('input[name="password"]', 'Password123!');
    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForNavigation({ waitUntil: 'networkidle2' })
    ]);
    console.log('[Auth] Logged in successfully. Current URL:', page.url());
    await page.close();

    // 3. Dasbor Page (Authenticated)
    const dasborRes = await auditPage('http://127.0.0.1:8000/app', 'dasbor-mobile', 9222);
    results.push(dasborRes);

    // 4. Form Lapor Page (Authenticated)
    const laporRes = await auditPage('http://127.0.0.1:8000/app/laporan/baru', 'lapor-form-mobile', 9222);
    results.push(laporRes);

  } finally {
    await browser.close();
  }

  // Generate markdown summary
  let md = '# Laporan Hasil Audit Lighthouse (Mobile Mode)\n\n';
  md += 'Pengujian dilakukan menggunakan Google Chrome Headless dengan profil emulasi perangkat mobile (375x812, DPR 2.0).\n\n';
  md += '| Halaman yang Diuji | URL | Performance | Accessibility | Best Practices | SEO |\n';
  md += '|---|---|---|---|---|---|\n';
  for (const r of results) {
    md += `| **${r.name}** | \`${r.url}\` | **${r.scores.performance ?? '-'}** | **${r.scores.accessibility ?? '-'}** | **${r.scores['best-practices'] ?? '-'}** | **${r.scores.seo ?? '-'}** |\n`;
  }

  md += '\n## Ringkasan Metrik Core Web Vitals\n\n';
  md += '| Halaman | First Contentful Paint (FCP) | Largest Contentful Paint (LCP) | Cumulative Layout Shift (CLS) | Total Blocking Time (TBT) |\n';
  md += '|---|---|---|---|---|\n';
  for (const r of results) {
    md += `| **${r.name}** | ${r.audits.fcp || '-'} | ${r.audits.lcp || '-'} | ${r.audits.cls || '-'} | ${r.audits.tbt || '-'} |\n`;
  }

  fs.writeFileSync('docs/lighthouse_report.md', md, 'utf-8');
  console.log('\nLaporan ringkasan disimpan ke docs/lighthouse_report.md');
}

main().catch(err => {
  console.error('Lighthouse run failed:', err);
  process.exit(1);
});
