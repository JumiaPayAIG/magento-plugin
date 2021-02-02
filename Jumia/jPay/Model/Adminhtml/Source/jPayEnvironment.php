<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Jumia\jPay\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class jPayEnvironment implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'Sandbox', 'label' => __('Sandbox')],
            ['value' => 'Live', 'label' => __('Live')],

        ];
    }
}