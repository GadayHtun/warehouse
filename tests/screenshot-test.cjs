// Screenshot test for warehouse app
// Run: node tests/screenshot-test.cjs

const { chromium } = require('playwright');

const BASE_URL = 'https://warehouse-mesm.onrender.com/';
const SCREENSHOT_DIR = './screenshots';

async function takeScreenshots() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1280, height: 720 }
  });
  const page = await context.newPage();

  try {
    // Warm up - Render free tier cold start
    console.log('⏳ Warming up (cold start can take 30-60s)...');
    await page.goto(`${BASE_URL}/login`, { timeout: 90000, waitUntil: 'domcontentloaded' });
    await page.waitForSelector('input[name="email"]', { timeout: 60000 });
    console.log('✅ Site is awake');

    // 1. Login page
    console.log('📸 Capturing login page...');
    await page.screenshot({ path: `${SCREENSHOT_DIR}/01-login.png`, fullPage: true });
    console.log('✅ Login page captured');

    // 2. Login with admin credentials
    console.log('🔐 Logging in...');
    await page.fill('input[name="email"]', 'admin@warehouse.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 30000 }).catch(() => {
      console.log('⚠️  No redirect to dashboard, waiting for network idle...');
    });
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: `${SCREENSHOT_DIR}/02-dashboard.png`, fullPage: true });
    console.log('✅ Dashboard captured');

    // 3. Products page
    console.log('📦 Capturing products page...');
    await page.goto(`${BASE_URL}/products`, { waitUntil: 'networkidle' });
    await page.screenshot({ path: `${SCREENSHOT_DIR}/03-products.png`, fullPage: true });
    console.log('✅ Products page captured');

    // 4. Locations page
    console.log('📍 Capturing locations page...');
    await page.goto(`${BASE_URL}/locations`, { waitUntil: 'networkidle' });
    await page.screenshot({ path: `${SCREENSHOT_DIR}/04-locations.png`, fullPage: true });
    console.log('✅ Locations page captured');

    // 5. Suppliers page
    console.log('🏢 Capturing suppliers page...');
    await page.goto(`${BASE_URL}/suppliers`, { waitUntil: 'networkidle' });
    await page.screenshot({ path: `${SCREENSHOT_DIR}/05-suppliers.png`, fullPage: true });
    console.log('✅ Suppliers page captured');

    console.log('\n🎉 All screenshots saved to ./screenshots/');

  } catch (error) {
    console.error('❌ Error:', error.message);
    await page.screenshot({ path: `${SCREENSHOT_DIR}/error.png`, fullPage: true });
  } finally {
    await browser.close();
  }
}

takeScreenshots();
