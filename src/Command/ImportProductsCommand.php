<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ImportProductsCommand extends Command
{
    protected static $defaultName = 'import-products';
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct();

        $this->serializer = $serializer;

    }

    protected function configure()
    {
        $this->setDescription('import products from json file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $filename = '/home/usuario/proyectos/challenge/products.json';
        $data = file_get_contents($filename);

        $rows = $this->serializer->deserialize($data,'json');
        
        dd($rows);

//        $encoders = [new XmlEncoder(), new JsonEncoder()];
//        $normalizers = [new ObjectNormalizer()];

        return 1;

    }

}