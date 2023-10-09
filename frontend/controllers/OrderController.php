<?php
namespace frontend\controllers;
use yii\web\Controller;
use Yii;
use common\models\Order;
use common\models\OrderAddress;

class OrderController extends \frontend\base\Controller
{
    public function actionCheckout()
    {
        $order = new Order();
        // if (Yii::$app->user->isGuest) {
        // }

        if ($order->load(Yii::$app->request->post()) && $order->validate()) {
            $order->status = Order::STATUS_DRAFT;
            $order->created_at = time();
            $order->created_by = Yii::$app->user->id; 

            if ($order->save()) {
                Yii::$app->session->setFlash('success', 'Order has been placed successfully.');
                return $this->redirect(['order/success']);
            } else {
                Yii::$app->session->setFlash('error', 'Failed to save order.');
            }
        }

  
        return $this->render('checkout', [
            'order' => $order,
            'cartItems' => [],
            'productQuantity' => 0,
            'totalPrice' => $totalPrice,
        ]);
    }
}
