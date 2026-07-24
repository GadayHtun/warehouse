const { chromium } = require('playwright');

async function debug() {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 720 } });

  try {
    // Navigate and wait
    console.log('Going to site...');
    const response = await page.goto('https://warehouse-mesm.onrender.com/login', {
      timeout: 120000,
      waitUntil: 'domcontentloaded'
    });

    console.log('Status:', response.status());
    console.log('URL:', page.url());
    console.log('Title:', await page.title());

    // Wait a bit
    await page.waitForTimeout(5000);

    // Get page content
    const html = await page.content();
    console.log('\n--- HTML (first 3000 chars) ---');
    console.log(html.substring(0, 3000));

    // Screenshot
    await page.screenshot({ path: './screenshots/debug.png', fullPage: true });
    console.log('\n✅ Debug screenshot saved');

    // Check for any form elements
    const inputs = await page.$$('input');
    console.log('Input elements found:', inputs.length);
    const forms = await page.$$('form');
    console.log('Form elements found:', forms.length);

  } catch (error) {
    console.error('Error:', error.message);
  } finally {
    await browser.close();
  }
}

debug();
