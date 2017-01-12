//copy right 2016 commercebees
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'ameex_authcim',
                component: 'Ameex_Authcim/js/view/method-renderer/authcimmain'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);