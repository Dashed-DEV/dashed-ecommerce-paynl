<?php

namespace Qubiqx\QcommerceEcommercePaynl\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Qubiqx\QcommerceEcommercePaynl\QcommerceEcommercePaynlServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Qubiqx\\QcommerceEcommercePaynl\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            QcommerceEcommercePaynlServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_qcommerce-ecommerce-paynl_table.php.stub';
        $migration->up();
        */
    }
}
