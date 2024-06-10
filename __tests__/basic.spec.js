// @ts-check
const { test, expect } = require("@playwright/test");

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

test("can view dashboard", async () => {
  await page.goto("/ceremony-account-portal/#/");

  await expect(
    page.getByRole("heading", { name: "Anonymous & Anonymous" }),
  ).toBeVisible({ timeout: 20000 });

  await expect(
    page.getByRole("heading", { name: "Thursday 28th of December" }),
  ).toBeVisible();
});

test("can view payments", async () => {
  await page.goto("/ceremony-account-portal/#/");
  await page.getByRole("button", { name: "Make Payment" }).click();

  await expect(
    page.getByRole("button", { name: "Pay remaining balance" }),
  ).toBeVisible();

  await expect(page.getByRole("cell", { name: "Booking Fee" })).toBeVisible();
  await expect(page.getByRole("cell", { name: "£100" })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Pay remaining balance' })).toBeVisible();
});

test("can view choices form", async () => {
  await page.goto("/ceremony-account-portal/#/");
  await page.getByRole("button", { name: "Complete Ceremony Choices" }).click();

  // await expect(
  //   page.getByRole("button", { name: "Update choices" }),
  // ).toHaveAttribute("href", "#/forms/complete");

  await expect(
    page.getByRole("button", { name: "Fill Out Ceremony Choices" }),
  ).toBeVisible();

  await page.getByRole("button", { name: "Fill Out Ceremony Choices" }).click();
  await expect(page.getByLabel("Contact Name")).toBeVisible();
});

test("can see breadcrumbs", async () => {
  await page.goto("/ceremony-account-portal/#/");
  await page.getByRole("link", { name: "Book appointment" }).click();
  await expect(page.getByRole('link', { name: 'Give Notice' })).toBeVisible();
});

test("can see header ceremony summary", async () => {
  await page.goto("/ceremony-account-portal/#/");
  await page.getByRole("link", { name: "Book appointment" }).click();
  await expect(page.getByTestId('booking-summary')).toBeVisible();
});

test("can see more info link", async () => {
  await page.goto("/ceremony-account-portal/#/");
  await page.getByRole('link', { name: 'View Information' })
});

test("can submit contact form", async () => {
  await page.goto("/ceremony-account-portal/#/");
  await page.getByPlaceholder('Message').click();
  await page.getByPlaceholder('Message').fill('test');
  await page.getByRole('button', { name: 'Send Message' }).click();
  await expect(page.getByText('Message Sent')).toBeVisible();
  await expect(page.getByText('We have received your message')).toBeVisible();
});