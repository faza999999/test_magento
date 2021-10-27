<?php
namespace Test\AddProductToCart\Controller\Index;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;
use Magento\Setup\Exception;

class Index extends Action
{
    protected $formKey;
    protected $cart;
    protected $product;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Model\Product $product,
        array $data = []) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->product = $product;
        parent::__construct($context);
    }

    public function execute()
    {
        $productId = self::createSimpleProduct();
        var_dump($productId);
        $params = array(
            'form_key' => $this->formKey->getFormKey(),
            'product' => $productId,
            'qty'   => 1
        );
        $_product = $this->product->load($productId);
        $this->cart->addProduct($_product, $params);

        $productId = self::createSimpleProduct();
        $params = array(
            'form_key' => $this->formKey->getFormKey(),
            'product' => $productId,
            'qty'   => 1
        );
        $_product = $this->product->load($productId);
        $this->cart->addProduct($_product, $params);

        $productId = self::createConfigurableProduct();
        $params = array(
            'form_key' => $this->formKey->getFormKey(),
            'product' => $productId,
            'qty'   => 1
        );
        $_product = $this->product->load($productId);
        $this->cart->addProduct($_product, $params);

        $productId = self::createGroupedProduct();
        $params = array(
            'form_key' => $this->formKey->getFormKey(),
            'product' => $productId,
            'qty'   => 1
        );
        $_product = $this->product->load($productId);
        $this->cart->addProduct($_product, $params);
        $this->cart->save();
    }

    public static function createSimpleProduct() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // instance of object manager
        $product = $objectManager->create('\Magento\Catalog\Model\Product');
        $product->setSku('sku'.uniqid()); // Set your sku here
        $product->setName(uniqid()); // Name of Product
        $product->setWebsiteIds(array(1));
        $product->setAttributeSetId(4); // Attribute set id
        $product->setStatus(1); // Status on product enabled/ disabled 1/0
        $product->setWeight(10); // weight of product
        $product->setVisibility(4); // visibilty of product (catalog / search / catalog, search / Not visible individually)
        $product->setTaxClassId(0); // Tax class id
        $product->setTypeId('simple'); // type of product (simple/virtual/downloadable/configurable)
        $product->setPrice(100); // price of product
        $product->setStockData(
            array(
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'is_in_stock' => 1,
                'qty' => 1
            )
        );
        $product->save();
        return $product->getId();
    }

    public static function createConfigurableProduct() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $configurable_product = $objectManager->create('\Magento\Catalog\Model\Product');

        $configurable_product->setSku('sku'.uniqid());
        $configurable_product->setName('CONFIG PRODUCT NAME');
        $configurable_product->setAttributeSetId(4);
        $configurable_product->setStatus(1);
        $configurable_product->setTypeId('configurable');
        $configurable_product->setPrice(0);
        $configurable_product->setWebsiteIds(array(1));
        $configurable_product->setCategoryIds(array(2));
        $configurable_product->setStockData(array(
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'is_in_stock' => 1,
            )
        );

        $size_attr_id = $configurable_product->getResource()->getAttribute('product_size')->getId();
        $color_attr_id = $configurable_product->getResource()->getAttribute('color')->getId();

        $configurable_product->getTypeInstance()->setUsedProductAttributeIds(array($color_attr_id, $size_attr_id), $configurable_product); //attribute ID of attribute 'size_general' in my store

        $configurableAttributesData = $configurable_product->getTypeInstance()->getConfigurableAttributesAsArray($configurable_product);
        $configurable_product->setCanSaveConfigurableAttributes(true);
        $configurable_product->setConfigurableAttributesData($configurableAttributesData);
        $configurableProductsData = array();
        $configurable_product->setConfigurableProductsData($configurableProductsData);
        try {
            $configurable_product->save();
        } catch (Exception $ex) {
            echo '<pre>';
            print_r($ex->getMessage());
            die;
        }

        $productId = $configurable_product->getId();

        $associatedProductIds = [3, 5, 7];

        try{
            $configurable_product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId); // Load Configurable Product
            $configurable_product->setAssociatedProductIds($associatedProductIds); // Setting Associated Products
            $configurable_product->setCanSaveConfigurableAttributes(true);
            $configurable_product->save();
        } catch (Exception $e) {
            echo "<pre>";
            print_r($e->getMessage());
            die;
        }
        return $productId;
    }

    public static function createGroupedProduct(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $grouped_product = $objectManager->create(Magento\Catalog\Model\Product::class);
        $grouped_product->setName('Grouped Product Name');
        $grouped_product->setSku('my-sku'.uniqid());
        $grouped_product->setTypeId('grouped');
        $grouped_product->setAttributeSetId(4);
        $grouped_product->setStatus(1);
        $grouped_product->setWebsiteIds(array(1));
        $grouped_product->setVisibility(4);
        $category_id = array(2, 3);
        $grouped_product->setCategoryIds($category_id);
        $grouped_product->setStockData(
            [
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'min_sale_qty' => 1,
                'max_sale_qty' => 2,
                'is_in_stock' => 1,
                'qty' => 1,
            ]

        );
        $grouped_product->save();
        $associated_id = [3, 5, 7];
        $associated_array = [];
        $associated_product_position = 0;
        foreach ($associated_id as $product_id)
        {
            $associated_product_position++;
            $product_repository_interface = $objectManager->get('\Magento\Catalog\Api\ProductRepositoryInterface')->getById($product_id);
            $product_link_interface = $objectManager->create('\Magento\Catalog\Api\Data\ProductLinkInterface');
            $product_link_interface->setSku($grouped_product->getSku())
                ->setLinkType('associated')
                ->setLinkedProductSku($product_repository_interface->getSku())
                ->setLinkedProductType($product_repository_interface->getTypeId())
                ->setPosition($associated_product_position)
                ->getExtensionAttributes()
                ->setQty(1);
            $associated_array[] = $product_link_interface;
        }
        $grouped_product->setProductLinks($associated_array);
        $grouped_product->save();
        return $grouped_product->getId();
    }
}
