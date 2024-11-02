<?php

namespace WalkerChiu\MorphNav;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use WalkerChiu\MorphNav\Models\Entities\Nav;
use WalkerChiu\MorphNav\Models\Entities\NavLang;

class NavTest extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ .'/../migrations');
        $this->withFactories(__DIR__ .'/../../src/database/factories');
    }

    /**
     * To load your package service provider, override the getPackageProviders.
     *
     * @param \Illuminate\Foundation\Application  $app
     * @return Array
     */
    protected function getPackageProviders($app)
    {
        return [\WalkerChiu\Core\CoreServiceProvider::class,
                \WalkerChiu\MorphNav\MorphNavServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
    }

    /**
     * A basic functional test on Nav.
     *
     * For WalkerChiu\MorphNav\Models\Entities\Nav
     * 
     * @return void
     */
    public function testMorphNav()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-nav.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-nav.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-nav.soft_delete', 1);

        // Give
        $record_1 = factory(Nav::class)->create();
        $record_2 = factory(Nav::class)->create();
        $record_3 = factory(Nav::class)->create(['is_enabled' => 1]);

        // Get records after creation
            // When
            $records = Nav::all();
            // Then
            $this->assertCount(3, $records);

        // Delete someone
            // When
            $record_2->delete();
            $records = Nav::all();
            // Then
            $this->assertCount(2, $records);

        // Resotre someone
            // When
            Nav::withTrashed()
                    ->find(2)
                    ->restore();
            $record_2 = Nav::find(2);
            $records = Nav::all();
            // Then
            $this->assertNotNull($record_2);
            $this->assertCount(3, $records);

        // Return Lang class
            // When
            $class = $record_2->lang();
            // Then
            $this->assertEquals($class, NavLang::class);

        // Scope query on enabled records
            // When
            $records = Nav::ofEnabled()
                               ->get();
            // Then
            $this->assertCount(1, $records);

        // Scope query on disabled records
            // When
            $records = Nav::ofDisabled()
                               ->get();
            // Then
            $this->assertCount(2, $records);
    }
}
