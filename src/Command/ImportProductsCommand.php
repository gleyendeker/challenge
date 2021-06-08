<?php

namespace App\Command;

use App\Entity\Product;
use JsonMachine\JsonMachine;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

class ImportProductsCommand extends Command
{
    protected static $defaultName = 'import-products';
    protected $progressBar;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
        $this->productRepository = $this->em->getRepository(Product::class);
    }

    protected function configure()
    {
        $this->setDescription('import products from json file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = '/home/usuario/proyectos/challenge/products.json';

        // using https://github.com/halaxa/json-machine to proccess objects one by one. Products are returned as arrays
        $products = JsonMachine::fromFile($filename);

        // update/create DB proucts
        $foundProducts = 0;
        foreach ($products as $id => $product) {
            $this->createOrUpdate($product);
            $foundProducts++;
        }
        $output->writeln("origin products found: $foundProducts");

        // show how many unsync products were found
        $unsyncProducts = $this->productRepository->findBySynchronized(false);
        $output->writeln("unsynced products found: " . sizeof($unsyncProducts));

        // export all products to CSV
        $this->toCSV($this->productRepository->findAll());
        $output->writeln("export to csv finished");

        return 1;
    }

    private function toCSV(Array $products){

        $fp = fopen("products.csv", "w");

        foreach ($products as $product) {
            fputcsv(
                $fp, // The file pointer
                [$product], // using toString
                ';' // The delimiter
            );
            $product->setSynchronized(true);
            $this->em->persist($product);
            $this->em->flush();
        }
        fclose($fp);
    }

    private function createOrUpdate ($product){

        $serializer = $this->setUpSerializer();

        // each product is an array so I have to use denormalize insted of deserialize
        $productFromJson = $serializer->denormalize($product, Product::class, 'json');
        $productFromDb = $this->em->getRepository(Product::class)->findOneByStyleNumber($productFromJson->getStyleNumber());

        // if the product doesn's exist create it, otherwise update it
        if (!$productFromDb){
            $productFromJson->setSynchronized(false);
            $this->em->persist($productFromJson);
        }
        else {
            $dirty = $productFromDb->updateWith($productFromJson);
            // if the product changed then save the changes, otherwise we don't do anything
            if($dirty){
                $productFromDb->setSynchronized(false);
                $this->em->persist($productFromDb);
            }
        }

        $this->em->flush();
    }

    private function setUpSerializer() {

        // info about normalizers and encoders: https://symfony.com/doc/current/components/serializer.html
        $normalizers = array(
            new ObjectNormalizer(null,null,null, new ReflectionExtractor()),
            new ArrayDenormalizer(),
        );
        $encoders = [new JsonEncoder()];

        return new Serializer($normalizers, $encoders);
    }

}