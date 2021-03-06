<?php

/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Plugin\Checkout\Model;

class ChangeShippingAddress extends \Magento\Checkout\Model\GuestShippingInformationManagement{

    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;
    /**
     * @var \Magento\Checkout\Api\ShippingInformationManagementInterface
     */
    protected $shippingInformationManagement;
    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @codeCoverageIgnore
     */
    protected $_checkoutSession;

    /**
     * @var \Magestore\Storepickup\Model\StoreFactory
     */
    protected $_storeCollection;
    /**
     * @var \Magento\Sales\Api\Data\OrderAddressInterface
     */
    protected $_orderAddressInterface;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magestore\Storepickup\Model\StoreFactory $storeCollection,
        \Magento\Sales\Api\Data\OrderAddressInterface $orderAddressInterface,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_storeCollection = $storeCollection;
        $this->_orderAddressInterface = $orderAddressInterface;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->shippingInformationManagement = $shippingInformationManagement;
    }

    public function aroundSaveAddressInformation(
        \Magento\Checkout\Model\GuestShippingInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        /*
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');*/

        if($addressInformation->getShippingMethodCode()=="storepickup" /*&& !$customerSession->isLoggedIn()*/){
            //print_r($addressInformation->getShippingAddress()->getData());
            $storepickup_session = $this->_checkoutSession->getData('storepickup_session');
            $datashipping = [];
            $storeId = $storepickup_session['store_id']??null;
            if(is_null($storeId))
                $storeId=1;
            $collectionstore = $this->_storeCollection->create();
            $store = $collectionstore->load($storeId, 'storepickup_id');
            $datashipping['firstname'] = (string)__('Store');
            $datashipping['lastname'] = $store->getData('store_name');
            $datashipping['street'][0] = $store->getData('address');
            $datashipping['city'] = $store->getCity();
            $datashipping['region'] = $store->getState();
            $datashipping['postcode'] = $store->getData('zipcode');
            $datashipping['country_id'] = $store->getData('country_id');
            $datashipping['company'] = '';
            if ($store->getFax()) {
                $datashipping['fax'] = $store->getFax();
            } else {
                unset($datashipping['fax']);
            }

            if ($store->getPhone()) {
                $datashipping['telephone'] = $store->getPhone();
            } else {
                unset($datashipping['telephone']);
            }

			$datashipping['save_in_address_book'] = 0;
            $datashipping['shipping_method'] = 'storepickup_storepickup';
            //var_dump($datashipping);die();
            $addressInformation->getShippingAddress()->addData($datashipping);

           
            //var_dump($addressInformation->getShippingAddress()->getData());
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            return $this->shippingInformationManagement->saveAddressInformation(
                $quoteIdMask->getQuoteId(),
                $addressInformation
            );
        }else{
            
            $data = $addressInformation->getShippingAddress()->getData();
            
            if(!isset($data['firstname']) || $data['firstname']=='First Name' || $data['firstname']==''){
                try{
                    throw new \Magento\Framework\Exception\LocalizedException('Please fill all shiping details.');
                }
                catch(\Magento\Framework\Exception\LocalizedException $e){
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $messageManager = $objectManager->get('\Magento\Framework\Message\ManagerInterface');
                    $messageManager->addExceptionMessage(
                        $e,
                        __($e->getMessage())
                    );
                }
                

            }
            return  $proceed($cartId,$addressInformation);
        }
        
    }

}