// @ts-check
const { test, expect } = require('@playwright/test');

test('should create groups with groups sync', async ({ page, request }) => {
	const result = await request.post('/?hello-login=event', {
		headers: {
			'Accept': 'application/json',
			'Content-Type': 'application/secevent+jwt'
		},
		data: "eyJhbGciOiJSUzI1NiIsImN0eSI6IkpXVCIsInR5cCI6ImFwcGxpY2F0aW9uL3NlY2V2ZW50K2p3dCJ9.eyJpc3MiOiJodHRwczovL2lzc3Vlci5oZWxsby5jb29wIiwiYXVkIjoiNGMxZWM5M2ItMjcxNC00ZDcyLTk1ODItYjA5YzUxNWM1YmQ4IiwianRpIjoiRWtCbXdUNzRBYzI1elJHNnBmMlBQIiwiaWF0IjoxNjc5MTAzNjkyLCJldmVudHMiOnsiaHR0cHM6Ly9oZWxsby5jb29wL2ZlZGVyYXRpb24vZ3JvdXBzL3N5bmMiOnsiZ3JvdXBzIjpbeyJ2YWx1ZSI6IjEyMzQiLCJkaXNwbGF5IjoiQWRtaW5zIn0seyJ2YWx1ZSI6IjM0NTYiLCJkaXNwbGF5IjoiTWFya2V0aW5nIn1dLCJvcmciOiJleGFtcGxlLmNvbSJ9fX0.hFKsybtyaS5tbYS2bxOxV1fVezqhZhx2piB-O70YT7J-miNYAeWYixNqeoP72IHDnd8gPtrAWnmm9JdWmLpd1z0SV-cB5zBJ_GLwyN_v5xfr98YIhxyBlneGA7ul-Kr64JN1iPURqlL4jXe5dOVApNYYa-nNhWSY3BXWHznfPwqoeoow3sZA818fIn4q71LzPQ3ovyanzc3WxAzIUn-PwuR_nkuW8sk_zMFiUUNNsh4py0CAD1OPCGYVhIAGOMt7lwy3RE1ucWIx-xdFQkhtX7dPDdbYZ0bFRDc_osRQdYj3_RyxYB72ocdNx-b8NyMaE8HImX8oiKnDeMOCRyxMa27UpAvrtR3JdS1BUTQsKYSdWhHjVSQDjqO3H_SF5Ii7NXbWc_WfBIXbJk2GjXR3KBdu9t2ZhBom5jAmChk6hHWfm-0cM0IFiV4n6Djlxj5m_v8IoVGn9H5rnJqKpH7Wln3wVl56BVqCBWRla76-IMxZIt_DMyHHqxCvFxGEY_0b26-aZE8sxNrWgE6_vGKrgys3zJv5J7wpmWmRKX6h9IQn7r_yfm6702XWkheP7hXPAVFyPAcMSDM7_VffE_IeOgrvLnr0PHxJDFxWVXP2h-o1deqJD5lQe2V5I43aSe77oYw1QniSlXRcgiRXktDd-ed0EPvILfx1TuNP2PzHxVg"
	});

	expect(result.ok()).toBeTruthy();

	await page.goto('/wp-login.php');

	// Sign in as admin
	await page.getByRole('button', { name: 'Continue with username or email' }).click();
	await page.getByLabel('Username or Email Address').click();
	await page.getByLabel('Username or Email Address').fill('admin');
	await page.getByLabel('Password', { exact: true }).click();
	await page.getByLabel('Password', { exact: true }).fill('password');
	await page.getByRole('button', { name: 'Log In' }).click();


	// Navigate to Hellō Login federation settings page and check that it is configured.
	await page.getByRole('navigation').getByRole('link', { name: 'Settings', exact: true }).click();
	await page.getByRole('link', { name: 'Hellō Login', exact: true }).click();

	await expect(page).toHaveTitle(/Hellō Login/);

	await page.getByRole('link', { name: 'Federation', exact: true }).click();
	await expect(page.getByRole('heading', { name: 'example.com', level: 2 })).toHaveCount(1);

	await expect(page.getByText('Admins', { exact: true })).toHaveCount(1);
	await expect(page.getByText('Marketing', { exact: true })).toHaveCount(1);

	const result2 = await request.post('/?hello-login=event', {
		headers: {
			'Accept': 'application/json',
			'Content-Type': 'application/secevent+jwt'
		},
		data: "eyJhbGciOiJSUzI1NiIsImN0eSI6IkpXVCIsInR5cCI6ImFwcGxpY2F0aW9uL3NlY2V2ZW50K2p3dCJ9.eyJpc3MiOiJodHRwczovL2lzc3Vlci5oZWxsby5jb29wIiwiYXVkIjoiNGMxZWM5M2ItMjcxNC00ZDcyLTk1ODItYjA5YzUxNWM1YmQ4IiwianRpIjoiRWtCbXdUNzRBYzI1elJHNnBmMlBQIiwiaWF0IjoxNjc5MTAzNjkyLCJldmVudHMiOnsiaHR0cHM6Ly9oZWxsby5jb29wL2ZlZGVyYXRpb24vZ3JvdXBzL3N5bmMiOnsiZ3JvdXBzIjpbeyJ2YWx1ZSI6IjEyMzQiLCJkaXNwbGF5IjoiRW5naW5lZXJpbmcifSx7InZhbHVlIjoiMzQ1NiIsImRpc3BsYXkiOiJTYWxlcyJ9XSwib3JnIjoiZXhhbXBsZS5vcmcifX19.FYallmfu7gu4K7TuY_6As9u5uxz8N7OsEB6z5nW8GDlgOoyKpEUIACWmlovyWqH1XClzyaOuAwKTBXug0HdjEKZv-ggT_bI5mBIlnQc_-o7J-0eXHVLgTH0vKcnXuTnkbFNr3OqTZqiWEDAZp5S6fIZUqo9sjD1Jmnr91x-wOKJheGbzzE3gNp2BIbrO8U_pUt2EQJYTP6Ywnrxo6NjKVZB9Dr-G40pXWrUO5ZWmRbeqNboLi_nXu7WOSHBfMPwLMgBHmAFUQDtFqem_XgypDdm9dBrwq2C3FyjCt_nVy_yQYphQN9I9PV2nnwp8f73zb3Y2CdM1bupWbJH5CeNU275de4hhyb4Kaa0GyMgAu3AJ6FN3PCvxvRJy8s1m88NgbGLAnbn9ybjzPS7Qw2Pv-zfjO76uF0cshS_JeXeKqnq1imPDjCxG9wm0Q_2h36KxQ-QrZL0Kth5zF-yOc115gvXRJZCiKGbO_0eF_lxPzFBmotdloVfb4ikO4LBlFzP40dQfcGWT_sdz76auEVFnTUmqM170uZnI_2OWZfyRJwtUcmKceLPmN0oLXcm0xh_KEyRAmM2JbegS_DZAybUX56DaX5YeJzCRISXeQNbVDR8ZYvjxZPKCgbKtbqg-bSbA9S7CHpN6NpsbEfa153IKs2C-ismNLwm8VzE3baIKsVY"
	});

	expect(result2.ok()).toBeTruthy();

	page.reload();

	await expect(page.getByRole('heading', { name: 'example.com', level: 2 })).toHaveCount(1);
	await expect(page.getByRole('heading', { name: 'example.org', level: 2 })).toHaveCount(1);

	await expect(page.getByText('Admins', { exact: true })).toHaveCount(1);
	await expect(page.getByText('Marketing', { exact: true })).toHaveCount(1);
	await expect(page.getByText('Engineering', { exact: true })).toHaveCount(1);
	await expect(page.getByText('Sales', { exact: true })).toHaveCount(1);
});
