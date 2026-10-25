const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const outDir = path.join(__dirname, '..', 'docs', 'screenshots');

if (!fs.existsSync(outDir)) {
    fs.mkdirSync(outDir, { recursive: true });
}

const viewports = [
    { name: 'landing-375px.png', width: 375, height: 812, isMobile: true },
    { name: 'landing-768px.png', width: 768, height: 1024, isMobile: false },
    { name: 'landing-1024px.png', width: 1024, height: 768, isMobile: false },
    { name: 'landing-1440px.png', width: 1440, height: 900, isMobile: false },
];

async function capture() {
    console.log('Launching Chrome from:', chromePath);
    const browser = await puppeteer.launch({
        executablePath: chromePath,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();

    for (const vp of viewports) {
        console.log(`Setting viewport: ${vp.width}x${vp.height} (${vp.name})`);
        await page.setViewport({
            width: vp.width,
            height: vp.height,
            isMobile: vp.isMobile,
            hasTouch: vp.isMobile,
            deviceScaleFactor: 2
        });

        await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle0' });
        const filePath = path.join(outDir, vp.name);
        await page.screenshot({ path: filePath, fullPage: false });
        console.log(`Saved screenshot: ${filePath}`);
    }

    await browser.close();
    console.log('All screenshots captured successfully!');
}

capture().catch(err => {
    console.error('Error capturing screenshots:', err);
    process.exit(1);
});
