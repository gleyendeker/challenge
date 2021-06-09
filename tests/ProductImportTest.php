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
        // loading in memory all products only for test purposes
        $filename = __DIR__ . '/../products.json';
        $numberOfProductsToBeImported = count(json_decode(file_get_contents($filename)));

        // check if the database is empty
        $importedProducts = $this->em->getRepository(Product::class)->findAll();
        $this->assertCount(0, $importedProducts);

        // run the command
        $this->commandTester->execute([]);

        //test the number of imported products
        $importedProducts = $this->em->getRepository(Product::class)->findAll();
        $this->assertCount($numberOfProductsToBeImported, $importedProducts);
//        $this->assertSame($numberOfProductsToBeImported, sizeof($importedProducts));
    }

    public function testRandomProductsAttributes()
    {
        // run the command
        $this->commandTester->execute([]);

        //test the number of imported products
        $importedProducts = $this->em->getRepository(Product::class)->findAll();

        // select a random imported product
        $foundProducts = count($importedProducts);
        $importedProductFromDB = $importedProducts[rand(1,$foundProducts-1)];

        // loading in memory all products only for test purposes
        $filename = __DIR__ . '/../products.json';
        $productsToBeImported = json_decode(file_get_contents($filename));

        // pick the same randon imported product from the json file
        $found_key = array_search($importedProductFromDB->getStyleNumber(), array_column($productsToBeImported, 'styleNumber'));
        $importedProductFromJson = $productsToBeImported[$found_key];

        // compare the product from the json file vs the imported product
        $this->assertSame($importedProductFromJson->styleNumber, $importedProductFromDB->getStyleNumber());
        $this->assertSame($importedProductFromJson->name, $importedProductFromDB->getName());
        $this->assertSame((float) $importedProductFromJson->price->amount, $importedProductFromDB->getPrice()->getAmount());
        $this->assertSame($importedProductFromJson->price->currency, $importedProductFromDB->getPrice()->getCurrency());
        $this->assertSame($importedProductFromJson->images, $importedProductFromDB->getImages());

    }
}
