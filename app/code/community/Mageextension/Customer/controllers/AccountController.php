<?php

require_once 'Mage/Customer/controllers/AccountController.php';
class Mageextension_Customer_AccountController extends Mage_Customer_AccountController
{
         
	public function indexAction()
    { 
		//echo "Hello";die;
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $this->getLayout()->getBlock('content')->append(
            $this->getLayout()->createBlock('customer/account_dashboard')
        );
        $this->getLayout()->getBlock('head')->setTitle($this->__('My Account'));
        $this->renderLayout();
    }
	
	/**
     * Login post action
     */
    public function loginPostAction()
    {
		
		
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $session = $this->_getSession();
		
		//checking is it from checkout
		$session->setCustomerRedirectFlag(0);
		$context = $this->getRequest()->getPost('context');
		$customerAlwaysRedirectToUrl = Mage::getStoreConfig('mageextension/customerredirect/always');
		if((!$customerAlwaysRedirectToUrl)){
			$session->setCustomerRedirectFlag(1);
		}
		
        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
						
                    }
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $session->addError($message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $session->addError($this->__('Login and password are required.'));
            }
        }

        $this->_loginPostRedirect();
    }
	
   
    protected function _loginPostRedirect()
    {
		//customer session
		$session = $this->_getSession();  
		$preUrl = $session->getBeforeAuthUrl(true);

		$cusromerRedirectStatus = Mage::getStoreConfig('mageextension/customerredirect/active');
		$customerRedirectUrl = Mage::getStoreConfig('mageextension/customerredirect/redirecturl');
		
		$redirectUrl = $preUrl;
		if(($cusromerRedirectStatus) && ($customerRedirectUrl) && ($session->getCustomerRedirectFlag()) && (strpos($preUrl, 'checkout/onepage') == '')){
			$redirectUrl = Mage::getUrl().$customerRedirectUrl;
		}
		
		if(($cusromerRedirectStatus) && ($customerRedirectUrl) && (!$session->getCustomerRedirectFlag())){
			$redirectUrl = Mage::getUrl().$customerRedirectUrl;
		}
		      
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirectUrl($redirectUrl);
            return;
        }
        
        $this->_redirectUrl($preUrl);          
    }
    protected function _welcomeCustomer(Mage_Customer_Model_Customer $customer, $isJustConfirmed = false)
    {
        $this->_getSession()->addSuccess(
            $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName())
        );
        if ($this->_isVatValidationEnabled()) {

            $configAddressType = Mage::helper('customer/address')->getTaxCalculationAddressType();
            $userPrompt = '';
            switch ($configAddressType) {
                case Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING:
                    $userPrompt = $this->__('If you are a registered VAT customer, please click <a href="%s">here</a> to enter you shipping address for proper VAT calculation', Mage::getUrl('customer/address/edit'));
                    break;
                default:
                    $userPrompt = $this->__('If you are a registered VAT customer, please click <a href="%s">here</a> to enter you billing address for proper VAT calculation', Mage::getUrl('customer/address/edit'));
            }
            $this->_getSession()->addSuccess($userPrompt);
        }

        $customer->sendNewAccountEmail(
            $isJustConfirmed ? 'confirmed' : 'registered',
            '',
            Mage::app()->getStore()->getId()
        );


$cusromerRedirectStatus = Mage::getStoreConfig('mageextension/accountredirect/active');
		$customerCreateRedirectUrl = Mage::getStoreConfig('mageextension/accountredirect/redirecturl');


if($cusromerRedirectStatus==1)
{
	$successUrl = Mage::getUrl().$customerCreateRedirectUrl;
	
	}
else{
         $successUrl = Mage::getUrl('*/*/index', array('_secure'=>true));}
        if ($this->_getSession()->getBeforeAuthUrl()) {
			
			if($cusromerRedirectStatus==1)
{
	$successUrl = Mage::getUrl().$customerCreateRedirectUrl;
}else
			{
			
            $successUrl = $this->_getSession()->getBeforeAuthUrl(true);
		}
        }
        return $successUrl;
    
    }
	
}
