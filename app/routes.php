<?php
/**
 * Add all routes to be added to the WP REST API.
 * Has access to $this (Ceremonies\Core\Router) as this file is called inside
 * a Router method.
 *
 * @var Ceremonies\Core\Router $this
 */

use Ceremonies\Controllers\{AccountController,
    AuthController,
    BookingController,
    ChoicesController,
    CronController,
    EndToEndTestingController,
    FaqController,
    FormController,
    ListingController,
    MigrationController,
    NoteController,
    PackageController,
    PaymentsController,
    RegistrarController,
    ReminderController,
    TestController};
use Ceremonies\Middleware\{
	Auth,
	CronAuth
};

// Example Route Formats:
// $this->add(route: '/example-route', method: 'POST', callback: [ExampleController::class, 'route'], middleware: [Auth::class]);
// $this->add(route: '/no-auth-route', callback: [ExampleController::class, 'otherRoute']);

// Registrars routes
$this->add(route: '/registrars/test', callback: [RegistrarController::class, 'test']);
$this->add(route: '/registrars/venues', callback: [RegistrarController::class, 'venues']);
$this->add(route: '/registrars/locations', callback: [RegistrarController::class, 'locations']);
$this->add(route: '/registrars/availability', callback: [RegistrarController::class, 'availability']);

// Payments routes
$this->add(route: '/payments', callback: [PaymentsController::class, 'index']);
$this->add(route: '/payments/(?P<id>[\d]+)', callback: [PaymentsController::class, 'single']);
$this->add(route: '/payments/test', callback: [PaymentsController::class, 'test']);
$this->add('/payments/invoke', 'POST', [PaymentsController::class, 'invoke']);
$this->add(route: '/payments/return', callback: [PaymentsController::class, 'return']);
$this->add(route: '/payments/error', callback: [PaymentsController::class, 'error']);
$this->add('/payments/renewal', 'POST', [PaymentsController::class, 'renewal']);

// Packages routes
$this->add(route: '/packages', callback: [PackageController::class, 'index']);
$this->add(route: '/packages/(?P<id>[\d]+)', callback: [PackageController::class, 'single']);
$this->add(route: '/packages/renewal-reminders', callback: [PackageController::class, 'renewalReminders']);
$this->add(route: '/packages/expired-check', callback: [PackageController::class, 'expiredCheck']);
$this->add('/packages/send-renewal', 'POST', [PackageController::class, 'sendRenewal']);

// Listing routes
$this->add('/listings', 'POST', [ListingController::class, 'store']);
$this->add(route: '/listings/name-check', callback: [ListingController::class, 'nameCheck']);

// Account routes
$this->add('/account', 'POST', [AccountController::class, 'update']);
$this->add('/account/password', 'POST', [AccountController::class, 'password']);
$this->add(route: '/account/email-check', callback: [AccountController::class, 'emailCheck']);

// Forms
$this->add(route: '/form', callback: [FormController::class, 'index']);
$this->add('/form', 'POST', [FormController::class, 'handle']);
$this->add(route: '/form/(?P<id>[\d]+)', callback: [FormController::class, 'single']);

// Test
$this->add(route: '/zip/search', callback: [TestController::class, 'search']);
$this->add(route: '/zip/tasks', callback: [TestController::class, 'tasks']);
$this->add(route: '/zip/statuses', callback: [TestController::class, 'statuses']);
$this->add(route: '/zip/payment', request: 'POST', callback: [TestController::class, 'payment']);
$this->add(route: '/zip/cats', callback: [TestController::class, 'categories']);
$this->add(route: '/zip/venues', callback: [TestController::class, 'venues']);

/* Ceremony Account Portal */

// Auth
$this->add('/auth', 'POST', [AuthController::class, 'auth']);
$this->add('/auth/refresh', 'POST', [AuthController::class, 'refresh']);
$this->add(route: '/dashboard', callback: [ BookingController::class, 'dashboard'], middleware: [Auth::class]);
$this->add(route: '/booking/initial-setup', callback: [BookingController::class, 'init'], middleware: [Auth::class]);
$this->add(route: '/forms', callback: [ChoicesController::class, 'single'], middleware: [Auth::class]);

// Form routes
$this->add('/forms', 'POST', [ChoicesController::class, 'complete'], [Auth::class]);
$this->add(route: '/forms/test', callback: [ChoicesController::class, 'test']);
$this->add('/forms/autosave', 'POST', [ChoicesController::class, 'autosave'], [Auth::class]);
$this->add(route: '/forms/generate', callback: [ChoicesController::class, 'generate']);
$this->add( '/forms/files', 'GET', [ChoicesController::class, 'files'], [Auth::class]);
$this->add( '/forms/files', 'POST', [ChoicesController::class, 'addFiles'], [Auth::class]);
$this->add( '/forms/files', 'DELETE', [ChoicesController::class, 'deleteFiles'], [Auth::class]);

// Portal Routes
$this->add('/booking/notice', 'GET', [BookingController::class, 'notice'], [Auth::class]);
$this->add('/booking/choices', 'GET', [BookingController::class, 'choices'], [Auth::class]);
$this->add('/booking/form', 'POST', [BookingController::class, 'form'], [Auth::class]);
$this->add('/booking/payments', 'GET', [BookingController::class, 'payments'], [Auth::class]);
$this->add('/booking/invoke-payment', 'GET', [PaymentsController::class, 'bookingInvoke'], [Auth::class]);
$this->add('/booking/complete-payment', 'GET', [BookingController::class, 'singlePayment'], [Auth::class]);
$this->add(route: '/faqs', callback: [FaqController::class, 'index'], middleware: [Auth::class]);

// Admin routes
$this->add(route: '/forms', callback: [ChoicesController::class, 'index']);
$this->add(route: '/forms/(?P<id>[\d]+)', callback: [ChoicesController::class, 'single']);

$this->add(route: '/bookings', callback: [BookingController::class, 'index']);
$this->add(route: '/bookings/(?P<id>[\d]+)', callback: [BookingController::class, 'single']);
$this->add(route: '/bookings/(?P<id>[\d]+)', request: 'PATCH', callback: [BookingController::class, 'singleUpdate']);
$this->add(route: '/bookings/choices/(?P<id>[\d]+)', callback: [BookingController::class, 'singleChoices']);
$this->add(route: '/bookings/clients/(?P<id>[\d]+)', callback: [BookingController::class, 'singleClients']);
$this->add(route: '/bookings/tasks/(?P<id>[\d]+)', callback: [BookingController::class, 'singleTasks']);
$this->add(route: '/bookings/tasks', request: 'PATCH', callback: [BookingController::class, 'updateTask']);
$this->add(route: '/bookings/choices/approve', request: 'POST', callback: [BookingController::class, 'approveChoices']);
$this->add(route: '/bookings/choices/deny', request: 'POST', callback: [BookingController::class, 'denyChoices']);
$this->add(route: '/bookings/choices', request: 'PATCH', callback: [BookingController::class, 'updateChoice']);
$this->add(route: '/bookings/notes/(?P<id>[\d]+)', callback: [NoteController::class, 'index']);
$this->add(route: '/bookings/notes', request: 'POST', callback: [NoteController::class, 'insert']);
$this->add(route: '/bookings/pdf/(?P<id>[\d]+)', callback: [BookingController::class, 'bookingPdf']);
$this->add(route: '/bookings/admin-email', request: 'POST', callback: [BookingController::class, 'adminEmail']);
$this->add(route: '/bookings/unlock-form', request: 'POST', callback: [BookingController::class, 'unlockForm']);

// Test
$this->add(route: '/mail-test', callback: [TestController::class, 'mailTest']);
//$this->add(route: '/test/slack', callback: [TestController::class, 'slack']);
$this->add(route: '/data-cleanup', request: 'POST', callback: [TestController::class, 'cleanup']);
$this->add(route: '/test', callback: [TestController::class, 'test']);

// Crons
$this->add(route: '/reminders/notice', callback: [ReminderController::class, 'notice'], middleware: [CronAuth::class]);
$this->add(route: '/reminders/choices', callback: [ReminderController::class, 'choices'], middleware: [CronAuth::class]);
$this->add(route: '/reminders/fees', callback: [ReminderController::class, 'fees'], middleware: [CronAuth::class]);
$this->add(route: '/cron/data-retention', callback: [CronController::class, 'dataRetention'], middleware: [CronAuth::class]);

// Migration
$this->add('/migrate', 'GET', [MigrationController::class, 'import']);

// E2E Testing
$this->add('/e2e/teardown', 'DELETE', [EndToEndTestingController::class, 'teardown']);