<?php

use Siberian\Assets;
use Siberian\Translation;
use Siberian_Module as Module;
use PaymentMethod\Model\Gateway;

$init = function($bootstrap) {
    Assets::registerScss([
        '/app/local/modules/Xdelivery/features/xdelivery/scss/xdelivery.scss'
    ]);

    Translation::registerExtractor(
        'xdelivery',
        'Xdelivery',
        '/app/local/modules/Xdelivery/resources/translations/default/xdelivery.po');
    
};

