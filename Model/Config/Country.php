<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Jpay\Payments\Model\Config;

class Country
{
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Please Select Country')],
            ['value' => 'eg', 'label' => __('Egypt')]
/*            ['value' => 'gh', 'label' => __('Ghana')],
            ['value' => 'ci', 'label' => __('Ivory Coast')],
            ['value' => 'ke', 'label' => __('Kenya')],
            ['value' => 'ma', 'label' => __('Morocco')],
            ['value' => 'ng', 'label' => __('Nigeria')],
            ['value' => 'tn', 'label' => __('Tunisia')],
            ['value' => 'ug', 'label' => __('Uganda')], */
        ];
    }
}
