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
    await page.goto("/ceremony-account-portal/#/forms/complete");
});

test.afterAll(async () => {
    await page.close();
});

test("can see and click form steps", async () => {
    await page.getByRole('button', { name: 'Step Two Registrar and Entrance' }).click();
    await expect(page.getByRole('heading', { name: 'Meeting the Registrar' })).toBeVisible();
});

test("can see prefilled form data", async () => {
    await page.getByRole('button', { name: 'Step One Contact and Basic' }).click();
    await expect(page.getByLabel('Contact Name')).toHaveValue('Anonymous Anonymous')
    await expect(page.getByLabel('Home Telephone')).toHaveValue('00000000000')
    await expect(page.getByLabel('Contact Email Address')).toHaveValue('blank@zipporah.co.uk')
});

test("can select dropdowns", async () => {
    await page.getByLabel('Please indicate the parties').click();
    await page.getByRole('option', { name: 'Bride and Bride' }).click();
});

test("can see dynamic text input labels update", async () => {
    await page.getByRole('button', { name: 'Step One Contact and Basic' }).click();
    await page.getByLabel('Please indicate the parties').click();
    await page.getByRole('option', { name: 'Groom and Groom' }).click();
    await expect(page.getByText('Details of the Groom and Groom')).toBeVisible();
    await expect(page.getByText("Groom's Full Name")).toHaveCount(2);
});

test("can see dynamic radio input labels update", async () => {
    await page.getByRole('button', { name: 'Step One Contact and Basic' }).click();
    await page.locator('input[name="partner_one_name"]').fill('Partner One Name');
    await page.locator('input[name="partner_two_name"]').fill('Partner Two Name');
    await page.getByRole('button', { name: 'Step Five Exchange of Rings' }).click();
    await expect(page.getByText('Partner One Name will be giving a ring')).toBeVisible();
    await expect(page.getByText('Partner Two Name will be giving a ring')).toBeVisible();
});

test("can upload files", async () => {
});

test("can see field from radio conditional component", async () => {
    await page.getByRole('button', { name: 'Step Two Registrar and Entrance' }).click();
    await page.getByRole('heading', { name: 'Meeting the Registrar' }).click();
    await page.getByText('Yes').click();
    await page.getByLabel('Who will perform this duty? *').click();
});

test("can see field from select conditional component", async () => {
    await page.getByRole('button', { name: 'Step One Contact and Basic' }).click();
    await page.locator('input[name="partner_one_name"]').fill('Partner One Name');
    await page.locator('input[name="partner_two_name"]').fill('Partner Two Name');
    await page.getByRole('button', { name: 'Step Two Registrar and Entrance' }).click();
    await page.getByLabel('Would you like to enter the').click();
    await page.getByRole('option', { name: 'Separately' }).click();
    await expect(page.getByText('If entering separately, do')).toBeVisible();
    await page.getByText('Both wish to be accompanied').click();
    await expect(page.locator('input[name="person_accompanying_partner_1"]')).toBeVisible();

});

test("can see form validation", async () => {
    await page.getByRole('button', { name: 'Next Step' }).click();
    await expect(page.locator('input[name="partner_one_name"]')).toHaveValue('');
    await expect(page.locator('input[name="partner_one_name"]')).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('input[name="partner_one_name"]')).toHaveAttribute('data-invalid', 'true');
    await expect(page.locator('input[name="partner_one_name"]')).toHaveCSS('border-color', 'rgb(232, 91, 91)');
});

test("cannot skip to last step and submit", async () => {
    await page.getByRole('button', { name: '8 Step Eight Guests and' }).click();
    await page.getByText('I have finished the form').click();
    await page.getByRole('button', { name: 'Submit your choices' }).click();
    await expect(page.getByLabel('Form could not be submitted')).toBeVisible();
});

test("can submit form", async () => {
    await page.getByLabel('Please indicate the parties').click();
    await page.getByRole('option', { name: 'Bride and Groom' }).click();
    await page.getByLabel('Bride\'s Full Name *').fill('name one');
    await page.getByLabel('Groom\'s Full Name *').fill('name two');
    await page.getByRole('button', { name: 'Next Step' }).click();
    await page.getByLabel('See the registrar together or').click();
    await page.getByRole('option', { name: 'Together' }).click();
    await page.getByLabel('Would you like to enter the').click();
    await page.getByRole('option', { name: 'Separately' }).click();
    await page.getByText('Both wish to be accompanied').click();
    await page.getByLabel('Who will accompany name one? *').fill('Test');
    await page.getByLabel('Who will accompany name two? *').fill('Test 2');
    await page.getByText('No', { exact: true }).click();
    await page.getByRole('button', { name: 'Next Step' }).click();
    await page.locator('section div').filter({ hasText: 'Declaratory:I do solemnly' }).nth(3).click();
    await page.locator('section div').filter({ hasText: 'Declaratory:By replying \'I am' }).nth(3).click();
    await page.getByRole('button', { name: 'Next Step' }).click();
    await page.getByText('We do not wish to add an').click();
    await page.getByRole('button', { name: 'Next Step' }).click();
    await page.getByText('name one will be giving a ring').click();
    await page.getByText('Yes').nth(1).click();
    await page.getByLabel('What is the ring bearers name').fill('Ring Bear');
    await page.getByLabel('What is their relationship to').fill('Pet');
    await page.getByRole('button', { name: 'Next Step' }).click();
    await page.getByText('A reading from those provided').first().click();
    await page.getByLabel('Please select a reading *').click();
    await page.getByRole('option', { name: 'When You Marry Her' }).click();
    await page.getByLabel('Who will be reading your').fill('Person');
    await page.getByText('We do not wish to have a').nth(1).click();
    await page.getByRole('button', { name: 'Next Step' }).click();
    await page.getByLabel('Whilst guests are assembling *').fill('Song 1');
    await page.getByLabel('On entrance of bride/groom *').fill('Song 2 - Blur');
    await page.getByLabel('Whilst signing the schedule *').fill('Song 3');
    await page.getByLabel('Whilst departing *').fill('Song 4');
    await page.getByRole('button', { name: 'Next Step' }).click();
    await page.getByLabel('Number of Guests *').fill('100');
    await page.getByLabel('Name of witness 1 *').fill('name');
    await page.getByLabel('Name of witness 1 *').fill('witness 1');
    await page.getByLabel('Name of witness 2 *').fill('witness 2');
    await page.getByLabel('Is there anything you would').click();
    await page.getByLabel('Is there anything you would').fill('test');
    await page.locator('div').filter({ hasText: /^I have finished the form$/ }).getByRole('img').click();
    await page.getByRole('button', { name: 'Submit your choices' }).click();
    await expect(page.getByRole('heading', { name: 'Thank you!' })).toBeVisible();
    await page.getByRole('button', { name: 'Return to Dashboard' }).click();
    await page.waitForURL("/ceremony-account-portal/#/", { waitUntil: "load" });
    await expect(page.getByText('Your choices have been submitted for review, you will be notified when they are approved.')).toBeVisible();
});