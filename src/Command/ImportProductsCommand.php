<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\PassThruDecoder;
use JsonMachine\JsonMachine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

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
        /* NOT streamed solution: */
        /* ****************** */
//        $data = file_get_contents($filename);
//
//        $normalizers = array(
//            new ObjectNormalizer(
//                null,
//                null,
//                null,
//                new ReflectionExtractor()
//            ),
//            new ArrayDenormalizer(),
//        );
//        $encoders = [new JsonEncoder()];
//        $serializer = new Serializer($normalizers, $encoders);
//
//        $rows = $serializer->deserialize($data,Product::class.'[]','json');
//
//        foreach ($rows as $row) {
//            $this->em->persist($row);
//        }
//        $this->em->flush();
//
//        die();

        /* ****************** */
        /* streamed solution: */
        /* ****************** */


        // https://github.com/salsify/jsonstreamingparser

        // setting up serializer
        $normalizers = array(
            new ObjectNormalizer(null,null,null, new ReflectionExtractor()),
            new ArrayDenormalizer(),
        );
        $encoders = [new JsonEncoder()];
        $serializer = new Serializer($normalizers, $encoders);

        // using https://github.com/halaxa/json-machine to proccess objects one by one
        $products = JsonMachine::fromFile($filename, '', new PassThruDecoder);

        foreach ($products as $id => $product) {
            $productObject = $serializer->deserialize($product,Product::class,'json');
            $this->em->persist($productObject);
        }

        $this->em->flush();

        return 1;

    }

}