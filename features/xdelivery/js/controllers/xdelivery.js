/**
 * xdelivery Home version 1 controllers
 */
angular.module('starter')
    .controller('XdeliveryHomeController', function (Dialog, Loader, Customer, $rootScope, SB, $scope, $state, $stateParams, $translate, Xdelivery, $controller, Application, $cordovaBarcodeScanner, $timeout) {
        $scope.value_id = Xdelivery.value_id = $stateParams.value_id;
        $scope.is_loading = false;
        $scope.payout = {};
        $scope.settings = {};

     angular.extend(this, $controller('XdeliveryProductCommanController', {
            Dialog: Dialog,
            $rootScope: $rootScope,
            $scope: $scope,
            $stateParams: $stateParams
        }));

    $scope.loadContent = function () {
            $scope.is_loading = true;          
            Xdelivery
            .findAll()
            .then(function (data) {
			    $scope.page_title = data.page_title;
                $scope.payout = data;
                $scope.settings = Xdelivery.settings = data.settings;
                if($scope.settings.is_food_app){
                    Xdelivery.store_id = data.store_id;
                }else{
                    Xdelivery.store_id = null;
                }
                
            }, function (error) {
                $scope.is_loading = false;
                Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
            })
            .then(function () { // Finally!
                $scope.is_loading = false;
            });
    };

    $scope.loadContent(); // Load content
    /**
     *category Image
     */
    $scope.CategoryImage = function (image) {      
        if (image != '' && image != null && image != "null") {
            return IMAGE_URL + 'images/application' + image;
        } else {
            return "./features/xdelivery/assets/media/default-image.png"
        }
    }; 


    /**
     *Slider Image 
     */
    $scope.SliderImage = function (image) {      
        if (image != '' && image != null && image != "null") {
            return IMAGE_URL + 'images/application' + image;
        } else {
            return "./features/xdelivery/assets/media/default-image.png"
        }
    }; 

    $scope.forTranslate = function(text){
        return $translate.instant(text, "xdelivery");
    }


   $scope.productScanCamera = function () {
    if (!Application.is_webview) {

        $cordovaBarcodeScanner.scan().then(function (barcodeData) {                   
            if (barcodeData.text !== '') {
                $timeout(function () {
                    var qrCode = barcodeData.text.replace('sendback:', ''); 
                    
                    if(qrCode != ''){
                        var productId = qrCode;                        
                        //verify scan
                       $scope.productDeails(productId); 

                    }else{
                         Dialog.alert($translate.instant("Error", "xdelivery"), $translate.instant('Invalid code.', "xdelivery") , $translate.instant('OK', "xdelivery"), -1);
                    }                            
                });

            }else{
                Dialog.alert($translate.instant("Error", "xdelivery") , $translate.instant("Unreadable QRCode, sorry", "xdelivery"), $translate.instant('OK', "xdelivery"), -1, "xdelivery");
            }
            
        }, function (error) {
            Dialog.alert($translate.instant("Error", "xdelivery"), 'An error occurred while reading the code.', $translate.instant('OK', "xdelivery"), -1);
        });

     } else {
        Dialog.alert($translate.instant("Info", "xdelivery") , $translate.instant("This will open the code scan camera on your device", "xdelivery"), $translate.instant("Ok", "xdelivery"), -1);
    }
};


}).controller('XdeliveryCartController', function (Dialog, Loader, $timeout, Customer, $rootScope, SB, $scope, $state, $stateParams, $translate, Xdelivery, $ionicActionSheet, $session) {
        $scope.value_id = Xdelivery.value_id = $stateParams.value_id;
        $scope.is_loading = false;
        $scope.payout = {};
        $scope.settings = Xdelivery.settings;
        $scope.carts = Xdelivery.carts;

        $scope.forTranslate = function(text){
             return $translate.instant(text, "xdelivery");
        }

        /**
         *Image
         */
        $scope.ProductImage = function (image) {      
            if (image != '' && image != null && image != "null") {
                return IMAGE_URL + 'images/application' + image;
            } else {
                return "./features/xdelivery/assets/media/default-image.png"
            }
        }; 

        $scope.loadContent = function (is_loading = true) {
                $scope.is_loading = is_loading;
                Xdelivery
                .cartProduct()
                .then(function (data) {
                    $scope.payout = data;
                    Xdelivery.carts.main = data;
                    if(data.in_out_of_stock){
                         Dialog.alert($translate.instant("Warring"), data.in_out_of_stock+' '+$translate.instant("Item out of stock, Please remove from your cart!"), $translate.instant("OK") , -1);
                    }
                    console.log('store_id', Xdelivery.store_id);
                    if(Xdelivery.store_id == null){
                        Xdelivery.store_id = data.store_id;
                    }

                }, function (error) {
                    $scope.is_loading = false;
                    Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                })
                .then(function () { // Finally!
                    $scope.is_loading = false;
                });
        };

        $scope.loadContent(true); // Load content

        $scope.increaseQuantity = function (product_id, child_product_id, cart_key, current_qty) {
                if($scope.settings.max_qty_per_product < current_qty){
                    Dialog.alert($translate.instant("Error"), $translate.instant("Quantity of products in cart must be "+$scope.settings.max_qty_per_product+" or less", "xdelivery"), $translate.instant("OK") , -1);
                    return true;
                }

                Loader.show();
                Xdelivery
                .increaseQuantity(product_id, child_product_id, cart_key)
                .success(function (data) {
                     $scope.loadContent(false);
                }, function (error) {
                    Loader.hide();
                    Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                }).error(function (error) {
                    Loader.hide();
                    Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                })
                .then(function () { // Finally!
                    Loader.hide();
                });
        };
  

        $scope.decreaseQuantity = function (product_id, child_product_id, cart_key) {
                Loader.show();
                Xdelivery
                .decreaseQuantity(product_id, child_product_id, cart_key)
                .success(function (data) {
                     $scope.loadContent(false);
                }, function (error) {
                    Loader.hide();
                    Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                }).error(function (error) {
                    Loader.hide();
                    Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                }).then(function () { // Finally!
                    Loader.hide();
                });
        };
        
        $scope.login = function(){
            var oldDeviceUid = $session.getDeviceUid();
           if (!Customer.isLoggedIn()) {
                Customer.loginModal($scope, function () { 
                    Loader.show();
                    $timeout(function () {
                       
                        Xdelivery
                        .syncDeviceAndCustomerCart(oldDeviceUid)
                        .success(function (data) {
                             $scope.loadContent(true);
                        }, function (error) {
                            Loader.hide();
                            Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                        }).error(function (error) {
                            Loader.hide();
                            Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                        }).then(function () { // Finally!
                            Loader.hide();
                        });

                        $scope.is_logged_in = Customer.isLoggedIn();
                        $scope.customer = Customer.customer;
                        $state
                        .go('home')
                        .then(function () {
                            $state
                                .go("xdelivery-home", { value_id: $scope.value_id }, { reload: true })
                                .then(function () {
                                    Loader.hide();
                                    $state.go("xdelivery-cart", { value_id: $scope.value_id }, { reload: true });
                                });;
                        });
                    }, 1000);
                });
            } 
        }

        $scope.checkoutNow = function () {

            if(!Customer.isLoggedIn()){
                $scope.login(); return true;
            }
            Xdelivery.carts.is_delivery_applied = undefined;
            if($scope.settings.min_qty_shopping_cart > $scope.payout.total_item){
                Dialog.alert($translate.instant("Error"), $translate.instant("Minimum order quantity required", "xdelivery")+' '+$scope.settings.min_qty_shopping_cart, $translate.instant("OK") , -1);
                return true;
            }

            if($scope.settings.max_qty_shopping_cart < $scope.payout.total_item){
                Dialog.alert($translate.instant("Error"), $translate.instant("Maximum order quantity allowed is", "xdelivery")+' '+$scope.settings.min_qty_shopping_cart, $translate.instant("OK") , -1);
                return true;
            }

            if($scope.settings.min_order_value > $scope.payout.total_amount){
                Dialog.alert($translate.instant("Error"), $translate.instant("Minimum order amount required", "xdelivery")+' '+$scope.settings.min_order_value, $translate.instant("OK") , -1);
                return true;
            }

            if($scope.settings.max_order_value < $scope.payout.total_amount){
                Dialog.alert($translate.instant("Error"), $translate.instant("Maximum order amount allowed", "xdelivery")+' '+$scope.settings.min_order_value, $translate.instant("OK") , -1);
                return true;
            }

            if(!$scope.settings.enable_to_deliver && !$scope.settings.enable_to_pickup){
                Xdelivery.carts.delivery = 'delivery';
                $state.go("xdelivery-checkout", { value_id: $scope.value_id }, { reload: true } );
                return true;
            }

            if(!$scope.settings.enable_to_deliver && $scope.settings.enable_to_pickup){
                Xdelivery.carts.delivery = 'pickup';
                $state.go("xdelivery-checkout", { value_id: $scope.value_id }, { reload: true } );
                return true;
            }

            if($scope.settings.enable_to_deliver && !$scope.settings.enable_to_pickup){
                Xdelivery.carts.delivery = 'delivery';
                $state.go("xdelivery-checkout", { value_id: $scope.value_id }, { reload: true } );
                return true;
            }


            var buttonIndexing = [];
            var buttonPositon = 0;
            var sheetButtons =  [];

            if($scope.settings.enable_to_deliver){
                sheetButtons[buttonPositon] = { text: $translate.instant("Home Delivery", 'xdelivery') };
                buttonIndexing[buttonPositon] = 'delivery';
                buttonPositon = buttonPositon + 1;
            }

            if($scope.settings.enable_to_pickup){
                sheetButtons[buttonPositon] = { text: $translate.instant("Pick-up", 'xdelivery') };
                buttonIndexing[buttonPositon] = 'pickup';
                buttonPositon = buttonPositon + 1;
            }

            // Show the action sheet
            var hideSheet = $ionicActionSheet.show({
                buttons: sheetButtons,
                cancelText: $translate.instant("Cancel", "xdelivery"),
                titleText: $translate.instant("Your order", "xdelivery"),
                cancel: function () {
                    hideSheet();
                },
                buttonClicked: function (index) {
                    Xdelivery.carts.delivery = 'delivery';
                    
                    if(buttonIndexing[index] == "delivery"){
                       Xdelivery.carts.delivery = 'delivery';
                    }
                    
                    if(buttonIndexing[index] == "pickup"){
                        Xdelivery.carts.delivery = 'pickup';
                    }                    
                    $state.go("xdelivery-checkout", { value_id: $scope.value_id }, { reload: true } );
                }
            });
        }


        $scope.applyDiscont = function(call_type){
            console.log($scope.payout.discount_code);
               Loader.show();
                Xdelivery
                .applyDiscont($scope.payout.discount_code,$scope.payout.tips_amount,call_type) //tips_amount,call_type by DN
                .success(function (data) {
                    if(data.status){
                        $scope.payout = data.cart;
                        Xdelivery.carts.main = data.cart;
                        Dialog.alert($translate.instant("Success"), data.message, $translate.instant("OK") , -1);
                    }else{
                        Dialog.alert($translate.instant("Error"), data.message, $translate.instant("OK") , -1);
                    }
                }, function (error) {
                    Loader.hide();
                    Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                }).error(function (error) {
                    Loader.hide();
                    Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                }).then(function () { // Finally!
                    Loader.hide();
                });
        }

        $scope.applyTips = function(action){
               console.log($scope.payout.tips_amount);
            
            $timeout(function () {
            
                Loader.show();
                Xdelivery
                .applyTips($scope.payout.tips_amount)
                .success(function (data) {
                    if(data.status){
                        $scope.payout = data.cart;
                        Xdelivery.carts.main = data.cart;
                        //Dialog.alert($translate.instant("Success"), data.message, $translate.instant("OK") , -1);
                    }else{
                        //Dialog.alert($translate.instant("Error"), data.message, $translate.instant("OK") , -1);
                    }
                }, function (error) {
                    Loader.hide();
                    Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                }).error(function (error) {
                    Loader.hide();
                    Dialog.alert($translate.instant("Error"), error.message, $translate.instant("OK") , -1);
                }).then(function () { // Finally!
                    Loader.hide();
                });

            }, 2500);
        }


    $scope.removeDiscont = function () {
       $scope.loadContent(false);
    }
    
});
