define(
   [
       'jquery',
       'ko',
       'uiComponent',
       'mage/validation'
   ],
   function ($, ko, Component) {
       "use strict";
       return Component.extend({
           defaults: {
  template: 'app\code\Ameex\Authcim\view\frontend\web\template\payment\cc-form.html'
           },
           /* Validation Form*/
            validateForm: function (form) {
                 return $(form).validation() && $(form).validation('isValid');
            },
submitForm: function(){
   if (!this.validateForm('#authcim-paymets')) {
       return;
   }
   /*Your source code*/
}            
        });
   }
);