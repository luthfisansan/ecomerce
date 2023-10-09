<?php

namespace frontend\controllers;

use common\models\CartItem;
use common\models\Order;
use common\models\OrderAddress;
use common\models\Product;
use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CartController extends \frontend\base\Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::class,
                'only' => ['add', 'create-order', 'submit-payment'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST', 'DELETE'],
                    'create-order' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        $userId = Yii::$app->user->id;
        $cartItems = CartItem::getItemsForUser($userId);
        $order = new Order();
        $orderAddress = new OrderAddress();
        $productQuantity = CartItem::getTotalQuantityForUser($userId);
        $totalPrice = CartItem::getTotalPriceForUser($userId);

        return $this->render('index', [
            'items' => $cartItems,
            'order' => $order,
            'orderAddress' => $orderAddress,
            'productQuantity' => $productQuantity,
            'totalPrice' => $totalPrice,
        ]);
    }

    public function actionAdd()
    {
        $id = Yii::$app->request->post('id');
        $product = Product::find()->id($id)->published()->one();
        if (!$product) {
            throw new NotFoundHttpException("Product does not exist");
        }

        if (Yii::$app->user->isGuest) {
            $cartItems = Yii::$app->session->get(CartItem::SESSION_KEY, []);
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
                    'total_price' => $product->price,
                ];
                $cartItems[] = $cartItem;
            }

            Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            $userId = Yii::$app->user->id;
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
                    'success' => true,
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => $cartItem->errors,
                ];
            }
        }
    }

    public function actionDelete($id)
    {
        if (Yii::$app->user->isGuest) {
            $cartItems = Yii::$app->session->get(CartItem::SESSION_KEY, []);
            foreach ($cartItems as $i => $cartItem) {
                if ($cartItem['id'] == $id) {
                    array_splice($cartItems, $i, 1);
                    break;
                }
            }
            Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            CartItem::deleteAll(['product_id' => $id, 'created_by' => Yii::$app->user->id]);
        }

        return $this->redirect(['index']);
    }

    public function actionChangeQuantity()
    {
        $id = Yii::$app->request->post('id');
        $product = Product::find()->id($id)->published()->one();
        if (!$product) {
            throw new NotFoundHttpException("Product does not exist");
        }
        $quantity = Yii::$app->request->post('quantity');
        if (Yii::$app->user->isGuest) {
            $cartItems = Yii::$app->session->get(CartItem::SESSION_KEY, []);
            foreach ($cartItems as &$cartItem) {
                if ($cartItem['id'] === $id) {
                    $cartItem['quantity'] = $quantity;
                    break;
                }
            }
            Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            $cartItem = CartItem::find()->userId(Yii::$app->user->id)->productId($id)->one();
            if ($cartItem) {
                $cartItem->quantity = $quantity;
                $cartItem->save();
            }
        }

        return CartItem::getTotalQuantityForUser(Yii::$app->user->id);
    }

    public function actionCheckout()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }
    
        $userId = Yii::$app->user->id;
        $cartItems = CartItem::getItemsForUser($userId);
    
        if (empty($cartItems)) {
            return $this->redirect([Yii::$app->homeUrl]);
        }
    
        $order = new Order();
    
        if ($order->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $order->total_price = CartItem::getTotalPriceForUser($userId);
                $order->status = Order::STATUS_DRAFT;
                $order->created_at = time();
                $order->created_by = $userId;
    
                if ($order->save()) {
                    foreach ($cartItems as $cartItem) {
                        $orderItem = new OrderItem();
                        $orderItem->product_name = $cartItem['name'];
                        $orderItem->product_id = $cartItem['id'];
                        $orderItem->unit_price = $cartItem['price'];
                        $orderItem->order_id = $order->id;
                        $orderItem->quantity = $cartItem['quantity'];
                        $orderItem->save();
                    }
    
                    $orderAddress = new OrderAddress();
                    $orderAddress->load(Yii::$app->request->post());
                    $orderAddress->order_id = $order->id;
                    $orderAddress->save();
    
                    // Hapus barang dari keranjang
                    CartItem::clearCartItems($userId);
    
                    $transaction->commit();
    
                    return $this->render('pay-now', [
                        'order' => $order,
                    ]);
                } else {
                    $transaction->rollBack();
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
            }
        }
    
        return $this->render('checkout', [
            'order' => $order,
            'cartItems' => $cartItems,
        ]);
    }
    
    

}
