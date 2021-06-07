<?php

namespace App\Command;

use App\Entity\Product;
use JsonMachine\JsonMachine;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Console\Command\Command;
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

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setDescription('import products from json file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // info about normalizers and encoders: https://symfony.com/doc/current/components/serializer.html

        $filename = '/home/usuario/proyectos/challenge/products.json';

        /* ****************** */
        /* streamed solution: */
        /* ****************** */

        // setting up serializer
        $normalizers = array(
            new ObjectNormalizer(null,null,null, new ReflectionExtractor()),
            new ArrayDenormalizer(),
        );
        $encoders = [new JsonEncoder()];
        $serializer = new Serializer($normalizers, $encoders);

        // using https://github.com/halaxa/json-machine to proccess objects one by one. Products are returned as arrays
        $products = JsonMachine::fromFile($filename);


        // update/create DB proucts
        foreach ($products as $id => $product) {

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

        // export to CSV
        $productsFromDb = $this->em->getRepository(Product::class)->findAll();

        $this->toCSV($productsFromDb);

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
        }

        fclose($fp);

    }

}