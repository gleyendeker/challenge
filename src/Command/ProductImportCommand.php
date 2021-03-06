<?php

namespace App\Command;

use App\Entity\Product;
use JsonMachine\JsonMachine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

class ProductImportCommand extends Command
{
    protected static $defaultName = 'app:import-products';

    protected $em;
    protected $productRepository;
    protected $output;
    protected $newProducts;
    protected $updatedProducts;
    protected $validator;
    protected $notValidProducts;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        parent::__construct();

        $this->em = $em;
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->validator = $validator;

        $this->updatedProducts = 0;
        $this->newProducts = 0;
        $this->notValidProducts = 0;
    }

    protected function configure()
    {
        $this->setDescription('import products from json file');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $filename = __DIR__ . '/../../products.json';
        
        // using https://github.com/halaxa/json-machine to proccess objects one by one. Products are returned as arrays
        $products = JsonMachine::fromFile($filename);

        // update/create DB products
        $foundProducts = 0;
        foreach ($products as $id => $product) {
            $this->createOrUpdateProduct($product);
            $foundProducts++;
        }

        $this->output->writeln('found products: ' . $foundProducts);
        $this->output->writeln('new products: ' . $this->newProducts);
        $this->output->writeln('updated products: ' . $this->updatedProducts);
        $this->output->writeln('not valid products: ' . $this->notValidProducts);

        // export all products to CSV
        $this->toCSV($this->productRepository->findAll(), 'products');

        $this->output->writeln("csv synced ok");

        return 1;

    }

    private function toCSV(Array $products, String $filename){

        $fp = fopen("$filename.csv", "w");

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

    private function createOrUpdateProduct ($product){

        $serializer = $this->setUpSerializer();

        // each product is an array so I have to use denormalize it (insted of deserialize)
        $productFromJson = $serializer->denormalize($product, Product::class, 'json');
        $productFromDb = $this->em->getRepository(Product::class)->findOneByStyleNumber($productFromJson->getStyleNumber());

        // if the product doesn's exist create it, otherwise update it
        if (!$productFromDb){
            $productFromJson->setSynchronized(false);
            // validate the product fields
            if (!$this->isValid($productFromJson)) return;
            $this->em->persist($productFromJson);
            $this->newProducts++;
        } else {
            $isDirty = $productFromDb->updateWith($productFromJson);
            // if the product changed then save the changes, otherwise we don't do anything
            if($isDirty){
                $productFromDb->setSynchronized(false);
                // validate the product fields
                if (!$this->isValid($productFromDb)) return;
                $this->em->persist($productFromDb);
                $this->updatedProducts++;
            }
        }

        $this->em->flush();

        return;
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

    protected function isValid($product){

        $errors = $this->validator->validate($product);

        if (count($errors) > 0){
            $this->output->write('the product' . $product->getStyleNumber() . ' is not valid. ');
            foreach ($errors as $violation) {
                $this->output->write($violation->getMessage() . ' ');
            }
            $this->output->writeln('');
            $this->notValidProducts++;
            return false;
        }else{
            return true;
        }
    }
}