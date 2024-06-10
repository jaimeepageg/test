// @ts-check
const { test, expect } = require("@playwright/test");

test("login error shown with invalid email", async ({ page }) => {
    await page.goto("/ceremony-account-portal/#/login");
    await page.getByLabel("Booking Reference *").click();
    await page.getByLabel("Booking Reference *").fill("test");
    await page.getByLabel("Email Address *").click();
    await page.getByLabel("Email Address *").fill("ooooo");
    await page.getByRole("button", { name: "Login" }).click();
    await expect(page.locator('[data-testid="login-email"]:invalid')).toBeVisible();
});

test("login error shown with no details", async ({ page }) => {
    await page.goto("/ceremony-account-portal/#/login");
    await page.getByRole("button", { name: "Login" }).click();
    await expect(page.locator('[data-testid="login-booking-ref"]:invalid')).toBeVisible();
});

test("login error shown with incorrect details", async ({ page }) => {
    await page.goto("/ceremony-account-portal/#/login");
    await page.getByLabel("Booking Reference *").click();
    await page.getByLabel("Booking Reference *").fill("test");
    await page.getByLabel("Email Address *").click();
    await page.getByLabel("Email Address *").fill("ooooo@test.com");
    await page.getByRole("button", { name: "Login" }).click();
    await expect(page.getByText('There was an issue logging you in')).toBeVisible();
});

test("login with new booking", async ({ page }) => {

    /**
     * We mock the API response to get the correct response without
     * having to search somewhere for a new booking that hasn't been
     * used.
     */
    await page.route('http://ceremonies.local/wp-json/sc/v1/auth', async route => {
        await route.fulfill({
            json: {
                success: true,
                token: "abc1234",
                first_login: true,
            }
        });
    });

    await page.goto("/ceremony-account-portal/#/login");
    await page.getByLabel("Booking Reference *").click();
    await page.getByLabel("Booking Reference *").fill("83200294");
    await page.getByLabel("Booking Reference *").press("Tab");
    await page.getByLabel("Email Address *").fill("blank@zipporah.co.uk");

    /**
     * Wait for the login request to complete before proceeding.
     * @type {Promise<Response>}
     */
    const responsePromise = page.waitForResponse(response => response.status() === 200);
    await page.getByRole("button", { name: "Login" }).click({ timeout: 20000 });
    await responsePromise;

    // Make sure we have redirected to the dashboard after login
    await expect(page.waitForURL("**/ceremony-account-portal/#/setup", { waitUntil: "load" })).toBeTruthy();
    await expect(page.getByText("Setting up your account")).toBeVisible();

});

test("login with existing booking", async ({ page }) => {
    await page.goto("/ceremony-account-portal/#/login");
    await page.getByLabel("Booking Reference *").click();
    await page.getByLabel("Booking Reference *").fill("83200294");
    await page.getByLabel("Booking Reference *").press("Tab");
    await page.getByLabel("Email Address *").fill("blank@zipporah.co.uk");

    /**
     * Wait for the login request to complete before proceeding.
     * @type {Promise<Response>}
     */
    const responsePromise = page.waitForResponse(response => response.status() === 200);
    await page.getByRole("button", { name: "Login" }).click({ timeout: 20000 });
    const response = await responsePromise;

    // Make sure we have redirected to the dashboard after login
    await page.waitForURL("**/ceremony-account-portal/#/", { waitUntil: "load" });

});