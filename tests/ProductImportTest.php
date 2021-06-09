<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ProductImportTest extends KernelTestCase
{
    protected $productImportCommand;

//    public function __construct(productImportCommand $productImportCommand)
//    {
//        parent::__construct();
//        $this->productImportCommand = $productImportCommand;
//    }

//    protected function setUp() : void
//    {
//        $kernel = self::bootKernel();
//
//        // the injection in the constructor doesn't work, so I found this way to get the service
//        $this->productImportCommand = self::$container->get('App\Command\ProductImportCommand');
//    }
//
//    public function testSomething(): void
//    {
//        $this->setUp();
//
//        $this->productImportCommand->execute();
//
//        $this->assertTrue(true);
//
//    }

    public function testExecute()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('app:import-products');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // pass arguments to the helper
            //  'username' => 'Wouter',
            // prefix the key with two dashes when passing options,
            // e.g: '--some-option' => 'option_value',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertTrue(true);
//        $this->assertStringContainsString('Username: Wouter', $output);

        // ...
    }
}
