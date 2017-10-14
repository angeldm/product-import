<?php

namespace BigBridge\ProductImport\Test\Integration;

use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Api\ProductRepositoryInterface;
use BigBridge\ProductImport\Model\Data\SimpleProduct;
use BigBridge\ProductImport\Model\ImportConfig;
use BigBridge\ProductImport\Model\ImporterFactory;
use BigBridge\ProductImport\Model\Data\Product;

/**
 * Integration test. It can only be executed from within a shop that has
 *
 * - a attribute set called 'Default'
 * - a store view called 'default'
 *
 * @author Patrick van Bergen
 */
class ImportTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ImporterFactory */
    private static $factory;

    /** @var ProductRepositoryInterface $repository */
    private static $repository;

    public static function setUpBeforeClass()
    {
        // include Magento
        require_once __DIR__ . '/../../../../../index.php';

        /** @var ImporterFactory $factory */
        self::$factory = ObjectManager::getInstance()->get(ImporterFactory::class);

        /** @var ProductRepositoryInterface $repository */
        self::$repository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
    }

    public function testInsertAndUpdate()
    {
        $success = true;

        $config = new ImportConfig();
        $config->eavAttributes = ['name', 'price'];
        $config->resultCallbacks[] = function (Product $product) use (&$success) {
            $success = $success && $product->ok;
        };

        list($importer, $error) = self::$factory->create($config);

        $sku1 = uniqid("bb");
        $sku2 = uniqid("bb");

        $products = [
            ["Big Blue Box", $sku1, 'Default', '3.25', 'admin'],
            ["Big Yellow Box", $sku2, 'Default', '4.00', 'admin'],
            ["Grote Gele Doos", $sku2, 'Default', '4.25', 'default'],
        ];

        foreach ($products as $data) {
            $product = new SimpleProduct();
            $product->name = $data[0];
            $product->sku = $data[1];
            $product->attribute_set_name = $data[2];
            $product->price = $data[3];
            $product->store_view_code = $data[4];

            $importer->insert($product);
        }

        $importer->flush();

        $products2 = [
            ["Big Blueish Box", $sku1, 'Default', '3.45', 'admin'],
            ["Big Yellowish Box", $sku2, 'Default', '3.95', 'admin'],
            ["Grote Gelige Doos", $sku2, 'Default', '4.30', 'default'],
        ];

        foreach ($products2 as $data) {
            $product = new SimpleProduct();
            $product->name = $data[0];
            $product->sku = $data[1];
            $product->attribute_set_name = $data[2];
            $product->price = $data[3];
            $product->store_view_code = $data[4];

            $importer->insert($product);
        }

        $importer->flush();

        $product1 = self::$repository->get($sku1);
        $this->assertTrue($product1->getAttributeSetId() > 0);
        $this->assertEquals($products2[0][0], $product1->getName());
        $this->assertEquals($products2[0][3], $product1->getPrice());

        $product2 = self::$repository->get($sku2, false, 0);
        $this->assertTrue($product2->getAttributeSetId() > 0);
        $this->assertEquals($products2[1][0], $product2->getName());
        $this->assertEquals($products2[1][3], $product2->getPrice());

        $product2a = self::$repository->get($sku2, false, 1);
        $this->assertTrue($product2a->getAttributeSetId() > 0);
        $this->assertEquals($products2[2][0], $product2a->getName());
        $this->assertEquals($products2[2][3], $product2a->getPrice());
    }

    public function testEmptyFields()
    {
        $config = new ImportConfig();
        $config->eavAttributes = ['name', 'color', 'special_price'];

        list($importer, $error) = self::$factory->create($config);

        $sku1 = uniqid("bb");

        $product = new SimpleProduct();
        $product->name = "Big Purple Box";
        $product->sku = $sku1;
        $product->attribute_set_name = "Default";
        $product->special_price = null;
        // note: color is missing completely

        $importer->insert($product);

        $importer->flush();

        $this->assertTrue($product->ok);

        $product1 = self::$repository->get($sku1);
        $this->assertEquals(null, $product1->getPrice());
    }

    public function testResultCallback()
    {
        $log = "";
        $lastId = null;

        $config = new ImportConfig();
        $config->eavAttributes = ['name', 'price'];
        $config->resultCallbacks[] = function(Product $product) use (&$log, &$lastId) {

            if ($product->ok) {
                $log .= sprintf("%s: success! sku = %s, id = %s\n", $product->lineNumber, $product->sku, $product->id);
                $lastId = $product->id;
            } else {
                $log .= sprintf("%s: failed! error = %s\n", $product->lineNumber, $product->error);
            }

        };

        list($importer, $error) = self::$factory->create($config);

        $lines = [
            ['Purple Box', "", "3.95"],
            ['Yellow Box', uniqid('bb'), "2.95"]
        ];

        foreach ($lines as $i => $line) {

            $product = new SimpleProduct();
            $product->name = $line[0];
            $product->sku = $line[1];
            $product->price = $line[2];
            $product->lineNumber = $i + 1;

            $importer->insert($product);
        }

        $importer->flush();

        $this->assertEquals("1: failed! error = missing sku\n2: success! sku = {$lines[1][1]}, id = {$lastId}\n", $log);
    }
}