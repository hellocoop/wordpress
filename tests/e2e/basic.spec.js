// @ts-check
const { test, expect } = require('@playwright/test');

test('login page not modified', async ({ page }) => {
	await page.goto('http://localhost:8888/wp-login.php');

	const body = page.locator('body');

	await expect(body).toContainText("Log In");
	await expect(body).not.toContainText("Hellō");
});

test('plugin installed and active', async ({ page }) => {
	await page.goto('http://localhost:8888/wp-login.php');

	// Sign in as admin
	await page.getByLabel('Username or Email Address').click();
	await page.getByLabel('Username or Email Address').fill('admin');
	await page.getByLabel('Password', { exact: true }).click();
	await page.getByLabel('Password', { exact: true }).fill('password');
	await page.getByRole('button', { name: 'Log In' }).click();

	// Navigate to plugins page
	await page.getByRole('link', { name: 'Plugins', exact: true }).click();

	// Check if Hellō Login is present and active.
	const pluginRow = page.getByRole('row', { name: 'Hellō Login' });
	await expect(pluginRow).toHaveCount(1);
	await expect(pluginRow).toContainText(/Deactivate/);
	await expect(pluginRow).toContainText(/Settings/);
//	await expect(pluginRow).toContainText(/Enable auto-updates/);

	// Navigate to Hellō Login settings page and check that it is not configured.
	await page.getByLabel('Main menu').getByRole('link', { name: 'Settings', exact: true }).click();
	await page.getByRole('link', { name: 'Hellō Login', exact: true }).click();

	await expect(page).toHaveTitle(/Hellō Login/);
	await expect(page.getByRole('button', { name: 'Quickstart' })).toHaveCount(1);
});
