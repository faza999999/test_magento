<?php
namespace Test\Prefix;
class Product extends \Magento\Catalog\Model\Product
{
    public function getName()
    {
        $changeNamebyPreference = '[Best]'.$this->_getData('name') ;
        return $changeNamebyPreference;
    }
}
