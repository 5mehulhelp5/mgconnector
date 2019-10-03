<?php
/**
 * Created by PhpStorm.
 * User: bnaya
 * Date: 4/27/17
 * Time: 11:32 AM
 */

namespace Remarkety\Mgconnector\Helper;
use Magento\Framework\App\Cache\TypeList;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use \Magento\Config\Model\ResourceModel\Config;

class ConfigHelper
{
    const RM_STORE_ID = 'remarkety/mgconnector/public_storeId';
    const WEBHOOKS_ENABLED = 'remarkety/mgconnector/webhooks';
    const PRODUCT_WEBHOOKS_DISABLED = 'remarkety/mgconnector/product_webhooks_disable';
    const FORCE_NON_ASYNC_WEBHOOKS = 'remarkety/mgconnector/force_non_async_webhooks';
    const FORCE_ASYNC_WEBHOOKS = 'remarkety/mgconnector/forceasyncwebhooks';
    const USE_CATEGORIES_FULL_PATH = 'remarkety/mgconnector/categories_full_path';
    const ENABLE_WEBHOOKS_TIMER = 'remarkety/mgconnector/enable_webhooks_timer';
    const POS_ATTRIBUTE_CODE = 'remarkety/mgconnector/pos_attribute_code';
    const EVENT_CART_VIEW_ENABLED = 'remarkety/mgconnector/event_cart_view_enabled';
    const EVENT_SEARCH_VIEW_ENABLED = 'remarkety/mgconnector/event_search_view_enabled';
    const EVENT_CATEGORY_VIEW_ENABLED = 'remarkety/mgconnector/event_category_view_enabled';

    const ASYNC_MODE_OFF = 0;
    const ASYNC_MODE_ON = 1;
    const ASYNC_MODE_ON_CUSTOMERS_SYNC = 2;

    protected $_activeStore;
    protected $_scopeConfig;
    protected $configResource;
    protected $cacheTypeList;
    private $_useCategoriesFullPath = null;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManager $sManager,
        Config $configResource,
        TypeList $cacheTypeList
    ){
        $this->_scopeConfig = $scopeConfig;
        $this->_activeStore = $sManager->getStore();
        $this->configResource = $configResource;
        $this->cacheTypeList = $cacheTypeList;
    }

    public function isStoreInstalled($storeId){
        $installed = $this->_scopeConfig->getValue(\Remarkety\Mgconnector\Model\Install::XPATH_INSTALLED, \Remarkety\Mgconnector\Model\Install::STORE_SCOPE, $storeId);
        return !empty($installed);
    }

    public function getRemarketyPublicId($store = null)
    {
        $store = is_null($store) ? $this->_activeStore : $store;
        $store_id = is_numeric($store) ? $store : $store->getId();
        $id = $this->_scopeConfig->getValue(self::RM_STORE_ID, ScopeInterface::SCOPE_STORES, $store_id);
        return (empty($id) || is_null($id)) ? false : $id;
    }

    public function isWebhooksGloballyEnabled(){
        $webhooksEnabled = $this->_scopeConfig->getValue(self::WEBHOOKS_ENABLED);
        if(is_null($webhooksEnabled) || !empty($webhooksEnabled)){
            return true;
        }
        return false;
    }

    public function getPOSAttributeCode(){
        $pos_attribute_code = $this->_scopeConfig->getValue(self::POS_ATTRIBUTE_CODE);
        if(empty($pos_attribute_code)){
            return null;
        }
        return $pos_attribute_code;
    }

    public function isEventCartViewEnabled() {
        $cart_view_code = $this->_scopeConfig->getValue(self::EVENT_CART_VIEW_ENABLED);
        return $cart_view_code == 1;
    }

    public function isEventSearchViewEnabled() {
        $search_view_code = $this->_scopeConfig->getValue(self::EVENT_SEARCH_VIEW_ENABLED);
        return $search_view_code == 1;
    }

    public function isEventCategoryViewEnabled() {
        $category_view_code = $this->_scopeConfig->getValue(self::EVENT_CATEGORY_VIEW_ENABLED);
        return $category_view_code == 1;
    }

    /**
     * @param string $code
     */
    public function setPOSAttributeCode($code){
        $this->configResource->saveConfig(
            self::POS_ATTRIBUTE_CODE,
            $code,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->cacheTypeList->cleanType('config');
    }

    public function setEventSearchViewEnabled($status){
        $current_status = $this->isEventSearchViewEnabled();
        $this->configResource->saveConfig(
            self::EVENT_SEARCH_VIEW_ENABLED,
            $status ? 1 : 0,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        if($current_status != $status){
            $this->cacheTypeList->cleanType('config');
            $this->cacheTypeList->cleanType('full_page');
        }
    }

    public function setEventCartViewEnabled($status){
        $current_status = $this->isEventCartViewEnabled();
        $this->configResource->saveConfig(
            self::EVENT_CART_VIEW_ENABLED,
            $status ? 1 : 0,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        if($current_status != $status){
            $this->cacheTypeList->cleanType('config');
            $this->cacheTypeList->cleanType('full_page');
        }
    }

    public function setEventCategoryViewEnabled($status){
        $current_status = $this->isEventCategoryViewEnabled();
        $this->configResource->saveConfig(
            self::EVENT_CATEGORY_VIEW_ENABLED,
            $status ? 1 : 0,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        if($current_status != $status){
            $this->cacheTypeList->cleanType('config');
            $this->cacheTypeList->cleanType('full_page');
        }
    }

    public function shouldSendProductUpdates(){
        $webhooks = $this->_scopeConfig->getValue(self::PRODUCT_WEBHOOKS_DISABLED);
        if(empty($webhooks)){
            return true;
        }
        return false;
    }

    public function forceSyncWebhooks(){
        $async = $this->_scopeConfig->getValue(self::FORCE_NON_ASYNC_WEBHOOKS);
        if(!empty($async)){
            return true;
        }
        return false;
    }

    public function forceSyncCustomersWebhooks(){
        $async = $this->_scopeConfig->getValue(self::FORCE_ASYNC_WEBHOOKS);
        if(!empty($async) && $async == self::ASYNC_MODE_ON_CUSTOMERS_SYNC){
            return true;
        }
        return false;
    }

    public function shouldLogWebhooksTiming(){
        $enabled = $this->_scopeConfig->getValue(self::ENABLE_WEBHOOKS_TIMER);
        if(!empty($enabled)){
            return true;
        }
        return false;
    }

    /**
     * @param bool $enabled
     */
    public function setWebhooksGloballStatus($enabled){
        $this->configResource->saveConfig(
            self::WEBHOOKS_ENABLED,
            $enabled ? 1 : 0,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->cacheTypeList->cleanType('config');
    }

    public function useCategoriesFullPath(){
        if(is_null($this->_useCategoriesFullPath)) {
            $fullPath = $this->_scopeConfig->getValue(self::USE_CATEGORIES_FULL_PATH);
            $this->_useCategoriesFullPath = !empty($fullPath);
        }
        return $this->_useCategoriesFullPath;
    }

    public function setCategoriesFullPath($value = true){
        $this->configResource->saveConfig(
            self::USE_CATEGORIES_FULL_PATH,
            $value ? 1 : 0,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->cacheTypeList->cleanType('config');
    }
}
