const {test, expect} = require("@playwright/test");

test.describe.configure({ mode: 'serial' });

/**
 * Store the page outside the tests, so we can share the
 * same session across all test. Means we only need to log in once.
 * @type {import('@playwright/test').Page}
 **/
let page;

test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
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
    await page.waitForURL("/ceremony-account-portal/#/", { waitUntil: "load" });
});

test.afterAll(async () => {
    await page.close();
});

test("can view notice", async () => {
    await page.goto("/ceremony-account-portal/#/");
    await page.getByRole("link", { name: "Book appointment" }).click();

    const button = await page.getByRole("link", { name: "Book appointment" });

    await expect(button).toBeVisible();
    await expect(button).toHaveAttribute(
        "href",
        "https://staffordshire.zipporah.co.uk/Registrars.Staffordshire.Sandpit/NoticeOfMarriageBookingProcess/IndexFromCeremony?bookingId=83200294",
    );
});

test("can see out of area notice", async () => {
    await page.goto("/ceremony-account-portal/#/");
    await page.getByRole("link", { name: "Book appointment" }).click();
    await expect(page.getByTestId('out-of-area-notice')).toBeVisible();
});