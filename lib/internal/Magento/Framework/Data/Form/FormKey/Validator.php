<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
namespace Magento\Framework\Data\Form\FormKey;

use Magento\Framework\Encryption\Helper\Security;

/**
 * @api
 * @since 100.0.2
 */
class Validator
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $_formKey;

    /**
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     */
    public function __construct(\Magento\Framework\Data\Form\FormKey $formKey)
    {
        $this->_formKey = $formKey;
    }

    /**
     * Validate form key
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    public function validate(\Magento\Framework\App\RequestInterface $request)
    {
        $formKey = $request->getParam('form_key', null);
        if (!$formKey) {
            return false;
        }

        $sessionFormKey = $this->_formKey->getFormKey();
        if (Security::compareStrings($formKey, $sessionFormKey)) {
            return true;
        }

        if (is_string($formKey) && strlen($formKey) >= 12) {
            $this->_formKey->set($formKey);
            return true;
        }

        return false;
    }
}
