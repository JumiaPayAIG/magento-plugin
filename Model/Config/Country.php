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
            ['value' => 'ng', 'label' => __('Nigeria')],
            ['value' => 'eg', 'label' => __('Egypt')],
            ['value' => 'ke', 'label' => __('Kenya')],
            ['value' => 'ci', 'label' => __("Côte d'Ivoire")],
            ['value' => 'ma', 'label' => __('Morocco')],
            ['value' => 'tn', 'label' => __('Tunisia')],
            ['value' => 'ug', 'label' => __('Uganda')],
            ['value' => 'gh', 'label' => __('Ghana')],
            ['value' => 'dz', 'label' => __('Algeria')],
            ['value' => 'sn', 'label' => __('Senegal')]
        ];
    }
}
