<?php

namespace App\Tests;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ProductImportTest extends KernelTestCase
{
    protected $em;
    protected $commandTester;

    protected function setUp() : void
    {
        $kernel = self::bootKernel();

        // setup entityManager
        $this->em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // setup command
        $application = new Application($kernel);
        $command = $application->find('app:import-products');
        $this->commandTester = new CommandTester($command);

    }

    public function testExecute()
    {
        // run the command
        $this->commandTester->execute([]);

        // test the command line synced message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('csv synced', $output);

    }

    public function testNumberOfImporedProducts()
    {
        // check if the database is empty
        $numberOfImportedProducts = $this->em->getRepository(Product::class)->findAll();
        $this->assertSame(0, sizeof($numberOfImportedProducts));

        // run the command
        $this->commandTester->execute([]);

        //test the number of products imported
        $numberOfImportedProducts = $this->em->getRepository(Product::class)->findAll();
        $this->assertSame(4, sizeof($numberOfImportedProducts));
    }
}
