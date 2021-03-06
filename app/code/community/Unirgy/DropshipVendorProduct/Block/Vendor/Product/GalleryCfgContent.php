<?php

class Unirgy_DropshipVendorProduct_Block_Vendor_Product_GalleryCfgContent extends Mage_Core_Block_Template
{
    public function getId()
    {
        if ($this->getData('id')===null) {
            $this->setData('id', Mage::helper('core')->uniqHash('id_'));
        }
        return $this->getData('id');
    }

    public function getHtmlId()
    {
        return $this->getId();
    }
    protected function _beforeToHtml()
    {
        $this->setTemplate('unirgy/udprod/vendor/product/config_gallery.phtml');
        return parent::_beforeToHtml();
    }
    public function doSuffix($prefix='')
    {
        return $prefix.$this->getCfgAttribute()->getId().'_'.$this->getCfgAttributeValue();
    }
    protected $_uploader;
    public function getUploader()
    {
        if (null === $this->_uploader) {
            $url = Mage::getModel('core/url')->addSessionParam()
                ->getUrl('udprod/vendor/upload', array('image_field'=>$this->doSuffix('image')));
            $this->_uploader = $this->getLayout()->createBlock('udprod/vendor_product_cfgUploader');
            $this->_uploader->getConfig()
                ->setUrl($url)
                ->setFileField($this->doSuffix('image'))
                ->setFilters(array(
                    'images' => array(
                        'label' => Mage::helper('adminhtml')->__('Images (.gif, .jpg, .png)'),
                        'files' => array('*.gif', '*.jpg','*.jpeg', '*.png')
                    )
                ));
        }
        return $this->_uploader;
    }

    public function getUploaderHtml()
    {
        return $this->getUploader()->toHtml();
    }

    public function getMediaAttributes()
    {
        return $this->getProduct()->getMediaAttributes();
    }

    public function getImageTypes()
    {
        $imageTypes = array();
        foreach ($this->getMediaAttributes() as $attribute) {
            /* @var $attribute Mage_Eav_Model_Entity_Attribute */
            $imageTypes[$attribute->getAttributeCode()] = array(
                'label' => $attribute->getFrontend()->getLabel(),
                'field' => $this->getAttributeFieldName($attribute)
            );
        }
        return $imageTypes;
    }

    public function getAttributeFieldName($attribute)
    {
        $name = $attribute->getAttributeCode();
        $name = sprintf("media_gallery[cfg_attributes][%s-%s][%s]",
            $this->getCfgAttribute()->getId(),
            $this->getCfgAttributeValue(),
            $name
        );
        if ($suffix = $this->getForm()->getFieldNameSuffix()) {
            $name = $this->getForm()->addSuffixToName($name, $suffix);
        }
        return $name;
    }

    public function getImageTypesJson()
    {
        return Mage::helper('core')->jsonEncode($this->getImageTypes());
    }

    public function getImagesValuesJson()
    {
        $values = array();
        foreach ($this->getMediaAttributes() as $attribute) {
            /* @var $attribute Mage_Eav_Model_Entity_Attribute */
            $values[$attribute->getAttributeCode()] = $this->getProduct()->getData(
                $attribute->getAttributeCode()
            );
        }
        return Mage::helper('core')->jsonEncode($values);
    }

    public function getMainImageData()
    {
        $mainImgData = array();
        $imgData = $this->getImagesData();
        foreach ($imgData as $img) {
            if (empty($mainImgData)) {
                $mainImgData = $img;
            }
            if (!empty($img['main'])) {
                $mainImgData = $img;
                break;
            }
        }
        return $mainImgData;
    }
    public function getImagesData()
    {
        if(is_array($this->getProduct()->getMediaGallery())) {
            $value = $this->getProduct()->getMediaGallery();
            if(count($value['images'])>0) {
                $images = array();
                $_images = $value['images'];
                try {
                $cfgAttrId = $this->getCfgAttribute()->getId();
                $cfgAttrVal = $this->getCfgAttributeValue();
                foreach ($_images as $image) {
                    if ($this->isCfgUploadImagesSimple()
                        || (isset($image['super_attribute'])
                        && isset($image['super_attribute'][$cfgAttrId])
                        && $image['super_attribute'][$cfgAttrId] == $cfgAttrVal
                    )) {
                        $image['url'] = Mage::getSingleton('catalog/product_media_config')->getMediaUrl($image['file']);
                        $image['main'] = @$image['super_attribute']['main'];
                        $images[] = $image;
                    }
                }
                } catch (Exception $e) {
                    var_dump($value);
                    die("$e");
                }
                return $images;
            }
        }
        return array();
    }

    public function getImagesJson()
    {
        return Mage::helper('core')->jsonEncode($this->getImagesData());
    }

    public function getJsObjectName()
    {
        return $this->getId() . 'JsObject';
    }

    public function hasUseDefault()
    {
        return false;
    }

    protected $_product;
    public function setProduct($product)
    {
        if ($this->isCfgUploadImagesSimple()) {
            $simples = Mage::helper('udprod')->getFilteredSimpleProductData($product, array($this->getCfgAttribute()->getAttributeCode()=>$this->getCfgAttributeValue()));
            if (empty($simples)) {
                $this->_product = Mage::helper('udprod')->initProductEdit(array(
                    'id' => false,
                    'type_id' => 'simple',
                    'template_id' => $product->getId(),
                    'data' => array()
                ));
            } else {
                foreach ($simples as $simple) {
                    $this->_product = $simple['product'];
                    break;
                }
            }
            $this->_product->getResource()->getAttribute('media_gallery')->getBackend()->afterLoad($this->_product);
        } else {
            $this->_product = $product;
        }
        return $this;
    }
    public function getProduct()
    {
        return $this->_product;
    }
    public function isCfgUploadImagesSimple()
    {
        return Mage::getSingleton('udprod/source')->isCfgUploadImagesSimple();
    }
}