<?php

namespace Ceremonies\Core;

use Ceremonies\Services\Helpers;
use DI\Container;
use Illuminate\Database\Capsule\Manager as Capsule;

class Bootstrap {


    private static Container $container;

    public static function init(): void
    {
        self::setupDiContainer();
        self::loadORM();
        add_action('rest_api_init', self::class . '::loadApiRoutes');
        add_action('admin_menu', self::class . '::setupPages');
        add_action('init', self::class . '::setupUserRoles');
		add_action('init', self::class . '::setupCors');
    }

    private static function setupDiContainer()
    {
        self::$container = new Container();
    }

    public static function container() {
        return self::$container;
    }

    private static function loadORM()
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => 'wp_ceremonies_',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    public static function loadApiRoutes()
    {
        $router = Router::init();
        $router->load('routes.php')->register();
    }


    public static function setupPages()
    {
        add_menu_page('Ceremonies', 'Ceremonies', 'manage_ceremonies', 'ceremonies', 'ceremonies_load_main_page', 'dashicons-awards', 25);
    }

    public static function setupUserRoles()
    {

		// Add roles
        add_role('ad_supplier', 'Supplier', ['edit_pages']);
        add_role('ad_venue', 'Venue', ['edit_pages']);
		add_role('staff', 'Staff', ['edit_pages']);

		// Add capabilities
	    $staff = get_role('staff');
	    $staff->add_cap('manage_ceremonies');
		$admin = get_role('administrator');
		$admin->add_cap('manage_ceremonies');

	}

	public static function setupCors() {
		$origin = get_http_origin();
		if (str_contains('ceremonies.local', $origin)) {
			header( "Access-Control-Allow-Origin: ceremonies.local" );
			header( "Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE" );
			header( "Access-Control-Allow-Credentials: true" );
			header( 'Access-Control-Allow-Headers: Origin, X-Requested-With, X-WP-Nonce, Content-Type, Accept, Authorization' );
			if ( 'OPTIONS' == $_SERVER['REQUEST_METHOD'] ) {
				status_header( 200 );
				exit();
			}
		}
	}

}
