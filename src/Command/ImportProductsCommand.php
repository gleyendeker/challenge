<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class ImportProductsCommand extends Command
{
    protected static $defaultName = 'import-products';

    protected function configure()
    {
        $this->setDescription('import products from json file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $filename = '/home/usuario/proyectos/challenge/products.json';
        $data = file_get_contents($filename);

        // info about normalizers and encoders: https://symfony.com/doc/current/components/serializer.html
        $normalizers = [new PropertyNormalizer(), new ArrayDenormalizer()];
        $encoders = [new JsonEncoder()];

        $serializer = new Serializer($normalizers, $encoders);

        $rows = $serializer->deserialize($data,'App\Entity\Product[]','json');
        
        dd($rows);

        return 1;

    }

}