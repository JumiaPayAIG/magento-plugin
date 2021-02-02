<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Jumia\jPay\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class jPayCountry implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Please Select Country')],
            ['value' => 'Egypt', 'label' => __('Egypt')],
            ['value' => 'Ghana', 'label' => __('Ghana')],
            ['value' => 'Ivory-Coast', 'label' => __('Ivory Coast')],
            ['value' => 'Kenya', 'label' => __('Kenya')],
            ['value' => 'Morocco', 'label' => __('Morocco')],
            ['value' => 'Nigeria', 'label' => __('Nigeria')],
            ['value' => 'Tunisia', 'label' => __('Tunisia')],
            ['value' => 'Uganda', 'label' => __('Uganda')],
        ];
    }
}