/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'mage/translate','ko',
        'jquery','mage/validation'
    ],
    function (_, Component, creditCardData, cardNumberValidator, $t,ko,$) {
        'use strict';
        var onchangeSavedcardVal=false;
        return Component.extend({
            defaults: {
                creditCardType: '',
                creditCardExpYear: '',
                creditCardExpMonth: '',
                creditCardNumber: '',
                creditCardSsStartMonth: '',
                creditCardSsStartYear: '',
                creditCardSsIssue: '',
                creditCardVerificationNumber: '',
                isSaveCc: '',
                ProfId: '',
                selectedCardType: null,
                template: 'Ameex_Authcim/payment/cc-form',
                timeoutMessage: 'Sorry, but something went wrong. Please contact the seller.'                
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'creditCardType',
                        'creditCardExpYear',
                        'creditCardExpMonth',
                        'creditCardNumber',
                        'creditCardVerificationNumber',
                        'creditCardSsStartMonth',
                        'creditCardSsStartYear',
                        'creditCardSsIssue',
                        'isSaveCc',
                        'ProfId',
                        'selectedCardType'
                    ]);

                return this;
            },

            /**
             * Init component
             */
            initialize: function () {
                var self = this;

                this._super();

                //Set credit card number to credit card data object
                this.creditCardNumber.subscribe(function (value) {
                    var result;

                    self.selectedCardType(null);

                    if (value === '' || value === null) {
                        return false;
                    }
                    result = cardNumberValidator(value);

                    if (!result.isPotentiallyValid && !result.isValid) {
                        return false;
                    }

                    if (result.card !== null) {
                        self.selectedCardType(result.card.type);
                        creditCardData.creditCard = result.card;
                    }

                    if (result.isValid) {
                        creditCardData.creditCardNumber = value;
                        self.creditCardType(result.card.type);
                    }
                });

                //Set expiration year to credit card data object
                this.creditCardExpYear.subscribe(function (value) {
                    creditCardData.expirationYear = value;
                });

                //Set expiration month to credit card data object
                this.creditCardExpMonth.subscribe(function (value) {
                    creditCardData.expirationMonth = value;
                });

                //Set cvv code to credit card data object
                this.creditCardVerificationNumber.subscribe(function (value) {
                    creditCardData.cvvCode = value;
                });
                this.isSaveCc.subscribe(function (value) {
                  ko.observable(false);                  
                  creditCardData.savedcc = value;
                });   
                this.ProfId.subscribe(function (value) {
                    //console.log(creditCardData);
                    creditCardData.Profid = value;
                });                
            },

            /**
             * Get code
             * @returns {String}
             */


            /**
             * @returns {String}
             */
            getCode: function () {
                return 'ameex_authcim';
            },
            //disableNormal:ko.observable(false),
            setvalue:function(item, event){
            var $this = $(event.target);          
            if (event.currentTarget.checked===true) {
              $this.val('1');   
              return true;
            }
            else
            {
              $this.val('0');
              return true;
            } 
            
            },
            checkPrimarystatusDom:function(){
              var primarycard=window.checkoutConfig.payment.ccform1.primarycard[this.getCode()];              
              if(primarycard!=='0')
               {
                $('#ameex_authcim_savedcard').val(primarycard).change();
                $('.type').css('display','none');
                $('.number').css('display','none');
                $('.date').css('display','none');
                $('.savecc').css('display','none');
                $('.cvv').css('display','none');                 
               } 

            },            
            submitForm: function(){
              //console.log(onchangeSavedcardVal);
              if($('#authcim-paymets').validation() && $('#authcim-paymets').validation('isValid')&& !onchangeSavedcardVal)
              {
                this.placeOrder();
              } 
              else if(onchangeSavedcardVal)
              {
                this.placeOrder();
              } 
              else
              { 
               return $('#authcim-paymets').validation() && $('#authcim-paymets').validation('isValid');
              }
                 /*Your source code*/
              },
            /**
             * @returns {Boolean}
             */
            isActive: function () {
                return true;
            },
            /**
             * Get data
             * @returns {Object}
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_ss_start_month': this.creditCardSsStartMonth(),
                        'cc_ss_start_year': this.creditCardSsStartYear(),
                        'cc_ss_issue': this.creditCardSsIssue(),
                        'cc_type': this.creditCardType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'cc_saved_card':this.ProfId(),
                        'savecc_cc_status': this.isSaveCc()
                        
                    }
                };
            },

            /**
             * Get list of available credit card types
             * @returns {Object}
             */
            getCcAvailableTypes: function () {
                return window.checkoutConfig.payment.ccform1.availableTypes[this.getCode()];
            },

            /**
             * Get payment icons
             * @param {String} type
             * @returns {Boolean}
             */
            getIcons: function (type) {
                return window.checkoutConfig.payment.ccform1.icons.hasOwnProperty(type) ?
                    window.checkoutConfig.payment.ccform1.icons[type]
                    : false;
            },

            /**
             * Get list of months
             * @returns {Object}
             */
            getCcMonths: function () {
                return window.checkoutConfig.payment.ccform1.months[this.getCode()];
            },
            getSavedCc:function(){
              return window.checkoutConfig.payment.ccform1.savedcard[this.getCode()];
            },
            isPrimarystatus:function(){
  
               return window.checkoutConfig.payment.ccform1.primarycard[this.getCode()];
              
             },           
            /**
             * Get list of years
             * @returns {Object}
             */
            getCcYears: function () {
                return window.checkoutConfig.payment.ccform1.years[this.getCode()];
            },

            /**
             * Check if current payment has verification
             * @returns {Boolean}
             */
            hasVerification: function () {
                return window.checkoutConfig.payment.ccform1.hasVerification[this.getCode()];
            },
            hasSavedcards: function () {
                return window.checkoutConfig.payment.ccform1.hasSavedcards[this.getCode()];
            },            

            /**
             * @deprecated
             * @returns {Boolean}
             */
            hasSsCardType: function () {
                return window.checkoutConfig.payment.ccform1.hasSsCardType[this.getCode()];
            },
        disabledefault:function(item, event){ //disable input if saved card selected
          var $this = $(event.target);
          var selectedval=$this.val();
          var primarydata=window.checkoutConfig.payment.ccform1.primarycard[this.getCode()];         
          if(selectedval!=='')
          { 
            $('.type').css('display','none');
            $('.number').css('display','none');
            $('.date').css('display','none');
            $('.savecc').css('display','none');
            $('.cvv').css('display','none');
            $('.ccnumber').attr('disabled','disabled'); 
            $this.attr('primary-data',primarydata);
            $('#ameex_authcim_expiration').attr('disabled','disabled');             
            $('#ameex_authcim_expiration_yr').attr('disabled','disabled');             
            $('#ameex_authcim_savecc').attr('disabled','disabled');            
            if($('#ameex_authcim_cc_cid').length)
            { 
              $('#ameex_authcim_cc_cid').attr('disabled','disabled');               
            }
            
            onchangeSavedcardVal=true;
          }
          if(selectedval=='')
          {
            $('.type').css('display','block');
            $('.number').css('display','block');
            $('.date').css('display','block');
            $('.savecc').css('display','block');
            $('.cvv').css('display','block');
            $this.attr('primary-data','0');
            $('#ameex_authcim_expiration').removeAttr('disabled');            
            $('.ccnumber').removeAttr('disabled');            
            $('#ameex_authcim_expiration_yr').removeAttr('disabled');            
            $('#ameex_authcim_savecc').removeAttr('disabled');            
            if($('#ameex_authcim_cc_cid').length)
            { 
              $('#ameex_authcim_cc_cid').removeAttr('disabled');            
            }
            onchangeSavedcardVal=false;
          } 
        },
            /**
             * Get image url for CVV
             * @returns {String}
             */
            getCvvImageUrl: function () {
                return window.checkoutConfig.payment.ccform1.cvvImageUrl[this.getCode()];
            },
            /**
             * Get image for CVV
             * @returns {String}
             */
            getCvvImageHtml: function () {
                return '<img src="' + this.getCvvImageUrl() +
                    '" alt="' + $t('Card Verification Number Visual Reference') +
                    '" title="' + $t('Card Verification Number Visual Reference') +
                    '" />';
            },

            /**
             * @deprecated
             * @returns {Object}
             */
            getSsStartYears: function () {
                return window.checkoutConfig.payment.ccform1.ssStartYears[this.getCode()];
            },

            /**
             * Get list of available credit card types values
             * @returns {Object}
             */
            getCcAvailableTypesValues: function () {
                return _.map(this.getCcAvailableTypes(), function (value, key) {
                  
                    return {
                        'value': key,
                        'type': value
                    };
                });
            },

            /**
             * Get list of available month values
             * @returns {Object}
             */
            getCcMonthsValues: function () {
                return _.map(this.getCcMonths(), function (value, key) {
                    return {
                        'value': key,
                        'month': value
                    };
                });
            },

            /**
             * Get list of available year values
             * @returns {Object}
             */
            getCcYearsValues: function () {
                return _.map(this.getCcYears(), function (value, key) {
                    return {
                        'value': key,
                        'year': value
                    };
                });
            },

            /**
             * @deprecated
             * @returns {Object}
             */
            getSsStartYearsValues: function () {
                return _.map(this.getSsStartYears(), function (value, key) {
                    return {
                        'value': key,
                        'year': value
                    };
                });
            },

            /**
             * Is legend available to display
             * @returns {Boolean}
             */
            isShowLegend: function () {
                return false;
            },
            getCcAvailableTypesValues: function () {
                return _.map(this.getCcAvailableTypes(), function (value, key) {
                    return {
                        'value': key,
                        'type': value
                    };
                });
            },            

            /**
             * Get available credit card type by code
             * @param {String} code
             * @returns {String}
             */
            getCcTypeTitleByCode: function (code) {
                var title = '',
                    keyValue = 'value',
                    keyType = 'type';

                _.each(this.getCcAvailableTypesValues(), function (value) {
                    if (value[keyValue] === code) {
                        title = value[keyType];
                    }
                });

                return title;
            },

            /**
             * Prepare credit card number to output
             * @param {String} number
             * @returns {String}
             */
            formatDisplayCcNumber: function (number) {
                return 'xxxx-' + number.substr(-4);
            },

            /**
             * Get credit card details
             * @returns {Array}
             */
            getInfo: function () {
                return [
                    {
                        'name': 'Credit Card Type', value: this.getCcTypeTitleByCode(this.creditCardType())
                    },
                    {
                        'name': 'Credit Card Number', value: this.formatDisplayCcNumber(this.creditCardNumber())
                    }
                ];
            }
        });
    }
);
