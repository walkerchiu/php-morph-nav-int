<?php

namespace WalkerChiu\MorphNav;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use WalkerChiu\MorphNav\Models\Entities\Nav;
use WalkerChiu\MorphNav\Models\Entities\NavLang;
use WalkerChiu\MorphNav\Models\Repositories\NavRepository;

class NavRepositoryTest extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected $repository;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        //$this->loadLaravelMigrations(['--database' => 'mysql']);
        $this->loadMigrationsFrom(__DIR__ .'/../migrations');
        $this->withFactories(__DIR__ .'/../../src/database/factories');

        $this->repository = $this->app->make(NavRepository::class);
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
     * A basic functional test on NavRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\Repository
     *
     * @return void
     */
    public function testMorphNavRepository()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-nav.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-nav.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-nav.soft_delete', 1);

        // Give
        $faker = \Faker\Factory::create();
        for ($i=1; $i<=3; $i++)
            $this->repository->save([
                'serial'     => $faker->isbn10,
                'identifier' => $faker->slug,
                'order'      => $faker->randomNumber
            ]);

        // Get and Count records after creation
            // When
            $records = $this->repository->get();
            $count   = $this->repository->count();
            // Then
            $this->assertCount(3, $records);
            $this->assertEquals(3, $count);

        // Find someone
            // When
            $record = $this->repository->find(1);
            // Then
            $this->assertNotNull($record);

            // When
            $record = $this->repository->find(4);
            // Then
            $this->assertNull($record);

        // Delete someone
            // When
            $this->repository->deleteByIds([1]);
            $count = $this->repository->count();
            // Then
            $this->assertEquals(2, $count);

            // When
            $this->repository->deleteByExceptIds([3]);
            $count = $this->repository->count();
            $record = $this->repository->find(3);
            // Then
            $this->assertEquals(1, $count);
            $this->assertNotNull($record);

            // When
            $count = $this->repository->where('id', '>', 0)->count();
            // Then
            $this->assertEquals(1, $count);

            // When
            $count = $this->repository->whereWithTrashed('id', '>', 0)->count();
            // Then
            $this->assertEquals(3, $count);

            // When
            $count = $this->repository->whereOnlyTrashed('id', '>', 0)->count();
            // Then
            $this->assertEquals(2, $count);

        // Force delete someone
            // When
            $this->repository->forcedeleteByIds([3]);
            $records = $this->repository->get();
            // Then
            $this->assertCount(0, $records);

        // Restore records
            // When
            $this->repository->restoreByIds([1, 2]);
            $count = $this->repository->count();
            // Then
            $this->assertEquals(2, $count);
    }

    /**
     * Unit test about Lang creation on NavRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphNav\Models\Repositories\MorphNavRepository
     * 
     * @return void
     */
    public function testcreateLangWithoutCheck()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-nav.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-nav.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-nav.soft_delete', 1);

        // Give
        factory(Nav::class)->create();

        // Find record
            // When
            $record = $this->repository->find(1);
            // Then
            $this->assertNotNull($record);

        // Create Lang
            // When
            $lang = $this->repository->createLangWithoutCheck(['morph_type' => get_class($record), 'morph_id' => $record->id, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello']);
            // Then
            $this->assertInstanceOf(NavLang::class, $lang);
    }

    /**
     * Unit test about Enable and Disable on NavRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphNav\Models\Repositories\MorphNavRepository
     *
     * @return void
     */
    public function testEnableAndDisable()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-nav.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-nav.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-nav.soft_delete', 1);

        $faker = \Faker\Factory::create();

        // Give
        factory(Nav::class)->create(['is_enabled' => 1]);
        factory(Nav::class, 3)->create();

        // Count records
            // When
            $count = $this->repository->count();
            $count_enabled = $this->repository->ofEnabled(null, null)->count();
            $count_disabled = $this->repository->ofDisabled(null, null)->count();
            // Then
            $this->assertEquals(4, $count);
            $this->assertEquals(1, $count_enabled);
            $this->assertEquals(3, $count_disabled);

        // Enable records
            // When
            $this->repository->whereToEnable(null, null, 'id', '>', 3);
            $count_enabled = $this->repository->ofEnabled(null, null)->count();
            $count_disabled = $this->repository->ofDisabled(null, null)->count();
            // Then
            $this->assertEquals(2, $count_enabled);
            $this->assertEquals(2, $count_disabled);

        // Disable records
            // When
            $this->repository->whereToDisable(null, null, 'id', '>', 0);
            $count_enabled = $this->repository->ofEnabled(null, null)->count();
            $count_disabled = $this->repository->ofDisabled(null, null)->count();
            // Then
            $this->assertEquals(0, $count_enabled);
            $this->assertEquals(4, $count_disabled);
    }

    /**
     * Unit test about Query List on NavRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphNav\Models\Repositories\MorphNavRepository
     *
     * @return void
     */
    public function testQueryList()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-nav.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-nav.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-nav.soft_delete', 1);

        $faker = \Faker\Factory::create();

        // Give
        factory(Nav::class, 4)->create();

        // Get query
            // When
            sleep(1);
            $this->repository->find(3)->touch();
            $records = $this->repository->ofNormal(null, null)->get();
            // Then
            $this->assertCount(4, $records);

            // When
            $record = $records->first();
            // Then
            $this->assertArrayNotHasKey('deleted_at', $record->toArray());
            $this->assertEquals(3, $record->id);

        // Get query of trashed records
            // When
            $this->repository->deleteByIds([4]);
            $this->repository->deleteByIds([1]);
            $records = $this->repository->ofTrash(null, null)->get();
            // Then
            $this->assertCount(2, $records);

            // When
            $record = $records->first();
            // Then
            $this->assertArrayHasKey('deleted_at', $record);
            $this->assertEquals(1, $record->id);
    }

    /**
     * Unit test about FormTrait on NavRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphNav\Models\Repositories\MorphNavRepository
     *     WalkerChiu\Core\Models\Forms\FormTrait
     *
     * @return void
     */
    public function testFormTrait()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-nav.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-nav.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-nav.soft_delete', 1);

        $faker = \Faker\Factory::create();

        // Name
            // Give
            $db_morph_1 = factory(Nav::class)->create();
            $db_morph_2 = factory(Nav::class)->create();
            $db_lang_1 = $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_1->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello']);
            $db_lang_2 = $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_2->id, 'morph_type' => Nav::class, 'code' => 'zh_tw', 'key' => 'name', 'value' => '您好']);
            // When
            $result_1 = $this->repository->checkExistName(null, null, 'en_us', null, 'Hello');
            $result_2 = $this->repository->checkExistName(null, null, 'en_us', null, 'Hi');
            $result_3 = $this->repository->checkExistName(null, null, 'en_us', $db_morph_1->id, 'Hello');
            $result_4 = $this->repository->checkExistName(null, null, 'en_us', $db_morph_1->id, '您好');
            $result_5 = $this->repository->checkExistName(null, null, 'zh_tw', $db_morph_1->id, '您好');
            $result_6 = $this->repository->checkExistNameOfEnabled(null, null, 'en_us', null, 'Hello');
            // Then
            $this->assertTrue($result_1);
            $this->assertTrue(!$result_2);
            $this->assertTrue(!$result_3);
            $this->assertTrue(!$result_4);
            $this->assertTrue($result_5);
            $this->assertTrue(!$result_6);

        // Serial, Identifier
            // Give
            $db_morph_3 = factory(Nav::class)->create(['serial' => '123', 'identifier' => 'A123']);
            $db_morph_4 = factory(Nav::class)->create(['serial' => '124', 'identifier' => 'A124']);
            $db_morph_5 = factory(Nav::class)->create(['serial' => '125', 'identifier' => 'A125', 'is_enabled' => 1]);
            // When
            $result_1 = $this->repository->checkExistSerial(null, null, null, '123');
            $result_2 = $this->repository->checkExistSerial(null, null, $db_morph_3->id, '123');
            $result_3 = $this->repository->checkExistSerial(null, null, $db_morph_3->id, '124');
            $result_4 = $this->repository->checkExistSerialOfEnabled(null, null, $db_morph_4->id, '124');
            $result_5 = $this->repository->checkExistSerialOfEnabled(null, null, $db_morph_4->id, '125');
            // Then
            $this->assertTrue($result_1);
            $this->assertTrue(!$result_2);
            $this->assertTrue($result_3);
            $this->assertTrue(!$result_4);
            $this->assertTrue($result_5);
            // When
            $result_1 = $this->repository->checkExistIdentifier(null, null, null, 'A123');
            $result_2 = $this->repository->checkExistIdentifier(null, null, $db_morph_3->id, 'A123');
            $result_3 = $this->repository->checkExistIdentifier(null, null, $db_morph_3->id, 'A124');
            $result_4 = $this->repository->checkExistIdentifierOfEnabled(null, null, $db_morph_4->id, 'A124');
            $result_5 = $this->repository->checkExistIdentifierOfEnabled(null, null, $db_morph_4->id, 'A125');
            // Then
            $this->assertTrue($result_1);
            $this->assertTrue(!$result_2);
            $this->assertTrue($result_3);
            $this->assertTrue(!$result_4);
            $this->assertTrue($result_5);
    }

    /**
     * Unit test about Auto Complete on NavRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphNav\Models\Repositories\MorphNavRepository
     *
     * @return void
     */
    public function testAutoComplete()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-nav.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-nav.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-nav.soft_delete', 1);

        $faker = \Faker\Factory::create();

        // Give
        $db_morph_1 = factory(Nav::class)->create(['serial' => 'A123', 'is_enabled' => 1]);
        $db_morph_2 = factory(Nav::class)->create(['serial' => 'A124', 'is_enabled' => 1]);
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_1->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello']);
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_1->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'description', 'value' => 'Good Morning!']);
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_1->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello World']);
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_1->id, 'morph_type' => Nav::class, 'code' => 'zh_tw', 'key' => 'name', 'value' => '您好']);
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_1->id, 'morph_type' => Nav::class, 'code' => 'zh_tw', 'key' => 'name', 'value' => '早安']);
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_2->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Bye']);

        // List array by name of enabled records
            // When
            $records = $this->repository->autoCompleteNameOfEnabled(null, null, 'en_us', 'H');
            // Then
            $this->assertCount(1, $records);

            // When
            $records = $this->repository->autoCompleteNameOfEnabled(null, null, 'zh_tw', 'H');
            // Then
            $this->assertCount(0, $records);

        // List array by serial of enabled records
            // When
            $records = $this->repository->autoCompleteSerialOfEnabled(null, null, 'en_us', 'A');
            // Then
            $this->assertCount(2, $records);
    }

    /**
     * Unit test about List on NavRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphNav\Models\Repositories\MorphNavRepository
     *
     * @return void
     */
    public function testList()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-nav.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-nav.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-nav.soft_delete', 1);
        Config::set('wk-morph-nav.output_format', 'array');

        $faker = \Faker\Factory::create();

        // When
        $records = $this->repository->list(null, null, 'en_us', [], true, null, null, [], true);
        // Then
        $this->assertTrue(is_array($records));
        $this->assertTrue(empty($records));

        // Give
        $db_morph_1  = factory(Nav::class)->create();
        $db_morph_2  = factory(Nav::class)->create();
        $db_morph_3  = factory(Nav::class)->create(['is_enabled' => 1]);
        $db_morph_4  = factory(Nav::class)->create(['is_enabled' => 1]);
        $db_morph_5  = factory(Nav::class)->create(['is_enabled' => 1]);
        $db_morph_6  = factory(Nav::class)->create(['is_enabled' => 1]);
        $db_morph_7  = factory(Nav::class)->create(['is_enabled' => 1]);
        $db_morph_8  = factory(Nav::class)->create(['is_enabled' => 1]);
        $db_morph_9  = factory(Nav::class)->create(['is_enabled' => 1]);
        $db_morph_10 = factory(Nav::class)->create();

        // Give
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_3->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello']);
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_3->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'description', 'value' => 'Good Morning!']);
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_3->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello World']);
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_3->id, 'morph_type' => Nav::class, 'code' => 'zh_tw', 'key' => 'name', 'value' => '您好']);
        $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_4->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hi']);

        // When
        $records = $this->repository->list(null, null, 'en_us', [], true, null, null, [], true);
        // Then
        $this->assertCount(2, $records);

        // When
        $records_1 = $this->repository->list(null, null, 'en_us', ['name' => 'H']);
        $records_2 = $this->repository->list(null, null, 'en_us', ['name' => 'H', 'description' => 'G']);
        $records_3 = $this->repository->list(null, null, 'en_us', ['name' => 'Hi']);
        // Then
        $this->assertEquals(2, $records_1->count());
        $this->assertEquals(1, $records_2->count());
        $this->assertEquals(1, $records_3->count());
    }

    /**
     * Unit test about ListMenu on NavRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphNav\Models\Repositories\MorphNavRepository
     *
     * @return void
     */
    public function testListMenu()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-nav.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-nav.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-nav.soft_delete', 1);

        $faker = \Faker\Factory::create();

        // When
        $records = $this->repository->listMenu(null, null, 'en_us', 'zh_tw');
        // Then
        $this->assertTrue(is_array($records));
        $this->assertTrue(empty($records));

        // Top menu
            // Give
            $db_morph_1 = factory(Nav::class)->create(['is_enabled' => 1]);
            $db_lang_1  = factory(NavLang::class)->create(['morph_id' => $db_morph_1->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name']);
            $db_lang_2  = factory(NavLang::class)->create(['morph_id' => $db_morph_1->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name']);
            // When
            $count = $this->repository->count();
            $records = $this->repository->listMenu(null, null, 'en_us', 'zh_tw');
            // Then
            $this->assertEquals(1, $count);
            $this->assertArrayHasKey('identifier', $records[0]);
            $this->assertArrayHasKey('name', $records[0]);
            $this->assertArrayHasKey('description', $records[0]);
            $this->assertArrayNotHasKey('child', $records[0]);

        // Child menu
            // Give
            $db_morph_2 = factory(Nav::class)->create(['ref_id' => $db_morph_1->id, 'is_enabled' => 1]);
            $db_lang_3  = factory(NavLang::class)->create(['morph_id' => $db_morph_2->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name']);
            $db_lang_4  = factory(NavLang::class)->create(['morph_id' => $db_morph_2->id, 'morph_type' => Nav::class, 'code' => 'en_us', 'key' => 'name']);

            // When
            $count = $this->repository->count();
            $records = $this->repository->listMenu(null, null, 'en_us', 'zh_tw', null, $db_morph_1->id);
            // Then
            $this->assertEquals(2, $count);
            $this->assertArrayHasKey('identifier', $records[0]);
            $this->assertArrayHasKey('name', $records[0]);
            $this->assertArrayHasKey('description', $records[0]);
            $this->assertArrayNotHasKey('child', $records[0]);

            // When
            $count = $this->repository->count();
            $records = $this->repository->listMenu(null, null, 'en_us', 'zh_tw', null, null, 1);
            // Then
            $this->assertEquals(2, $count);
            $this->assertArrayHasKey('child', $records[0]);
            $this->assertArrayHasKey('identifier', $records[0]['child'][0]);
            $this->assertArrayHasKey('name', $records[0]['child'][0]);
            $this->assertArrayHasKey('description', $records[0]['child'][0]);
            $this->assertArrayNotHasKey('child', $records[0]['child'][0]);
    }
}
