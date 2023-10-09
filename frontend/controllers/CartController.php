<?php
namespace frontend\controllers;
use Yii;
use common\models\CartItem;
use common\models\Product;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use common\models\Order;
use common\models\OrderAddress;
/**
 * Class CartController
 * @package frontend\controllers
 */
class CartController extends \frontend\base\Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::class,
                'only' => ['add'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST', 'DELETE'],
                ]
            ]
        ];
    }
    public function actionIndex()
    {
        if (\Yii::$app->user->isGuest) {
            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            $totalPrice = CartItem::getTotalPriceForGuest();
        } else {
            $cartItems = CartItem::getItemsForUser(\Yii::$app->user->id);
            $totalPrice = CartItem::getTotalPriceForUser(\Yii::$app->user->id);
        }
    
        return $this->render('index', [
            'items' => $cartItems,
            'totalPrice' => $totalPrice, // Menyertakan total harga ke tampilan
        ]);
    }
    
    public function actionAdd()
    {
        $id = \Yii::$app->request->post('id');
        $product = Product::find()->id($id)->published()->one();
        if (!$product) {
            throw new NotFoundHttpException("Product does not exist");
        }
        if (\Yii::$app->user->isGuest) {

            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            $found = false;
            foreach ($cartItems as &$item) {
                if ($item['id'] == $id) {
                    $item['quantity']++;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $cartItem = [
                    'id' => $id,
                    'name' => $product->name,
                    'image' => $product->image,
                    'price' => $product->price,
                    'quantity' => 1,
                    'total_price' => $product->price
                ];
                $cartItems[] = $cartItem;
            }
            \Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            $userId = \Yii::$app->user->id;
            $cartItem = CartItem::find()->userId($userId)->productId($id)->one();
            if ($cartItem) {
                $cartItem->quantity++;
            } else {
                $cartItem = new CartItem();
                $cartItem->product_id = $id;
                $cartItem->created_by = $userId;
                $cartItem->quantity = 1;
            }
            if ($cartItem->save()) {
                return [
                    'success' => true
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => $cartItem->errors
                ];
            }
        }
    }
    public function actionDelete($id)
    {
        if (\Yii::$app->user->isGuest) {
            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            foreach ($cartItems as $i => $cartItem) {
                // if ($cartItem['id'] == $id){
                if ($cartItem['id'] == $id) {
                    array_splice($cartItems, $i, 1);
                    break;
                }
            }
            \Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            CartItem::deleteAll(['product_id' => $id, 'created_by' => Yii::$app->user->id]);
        }

        return $this->redirect(['index']);
    }

    public function actionChangeQuantity()
    {
        $id = \Yii::$app->request->post('id');
        $product = Product::find()->id($id)->published()->one();
        if (!$product) {
            throw new NotFoundHttpException("Product does not exist");
        }
        $quantity = \Yii::$app->request->post('quantity');
        if (isGuest()) {
            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            foreach ($cartItems as &$cartItem) {
                if ($cartItem['id'] === $id) {
                    $cartItem['quantity'] = $quantity;
                    break;
                }
            }
            \Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            $cartItem = CartItem::find()->userId(currUserId())->productId($id)->one();
            if ($cartItem) {
                $cartItem->quantity = $quantity;
                $cartItem->save();
            }
        }

        return CartItem::getTotalQuantityForUser(currUserId());
    }
    public function actionCheckout()
    {
        $order = new Order();
        $orderAddress = new OrderAddress();
        $cartItems = [];
    
        if (!Yii::$app->user->isGuest) {
            // Jika pengguna login, gunakan keranjang belanja pengguna
            $cartItems = CartItem::getItemsForUser(Yii::$app->user->id);
    
            // Anda mungkin juga ingin mengisi informasi pelanggan jika tersedia dalam model User
            $user = Yii::$app->user->identity;
            $order->firstname = $user->firstname;
            $order->lastname = $user->lastname;
            $order->email = $user->email;
        } elseif (Yii::$app->session->has(CartItem::SESSION_KEY)) {
            // Jika pengguna tidak login tetapi ada keranjang belanja dalam sesi
            $cartItems = Yii::$app->session->get(CartItem::SESSION_KEY);
        }
    
        $productQuantity = CartItem::getTotalQuantityForUser(Yii::$app->user->id);
        $totalPrice = CartItem::getTotalPriceForUser(Yii::$app->user->id);
    
        if (Yii::$app->request->isPost) {
            // Jika ada data yang dikirimkan melalui POST (saat pengguna melakukan checkout)
            // Isi model Order dan OrderAddress dengan data yang dikirimkan
    
            if ($order->load(Yii::$app->request->post()) && $orderAddress->load(Yii::$app->request->post())) {
                // Validasi data
                $isValid = $order->validate() && $orderAddress->validate();
    
                if ($isValid) {
                    // Set atribut-atribut lain yang diperlukan untuk pesanan
                    $order->status = Order::STATUS_DRAFT;
                    $order->created_at = time();
                    $order->created_by = Yii::$app->user->id;
    
                    
                    if ($order->save()) {
                        
                        $orderAddress->order_id = $order->id;
                        if ($orderAddress->save()) {
                            
                            if (!Yii::$app->user->isGuest) {
                                CartItem::clearCart(Yii::$app->user->id);
                            } else {
                                
                                Yii::$app->session->remove(CartItem::SESSION_KEY);
                            }
    
                            
                            return $this->redirect(['order/success']);
                        }
                    }
                }
            }
        }
    
        return $this->render('checkout', [
            'order' => $order,
            'orderAddress' => $orderAddress,
            'cartItems' => $cartItems,
            'productQuantity' => $productQuantity,
            'totalPrice' => $totalPrice
        ]);
    }
    
}