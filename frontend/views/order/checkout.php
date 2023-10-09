<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;

// $this->title = $name;
?>
<div class="site-checkout">
    <h1>Checkout Information</h1>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Customer Information</h5>
            <p class="card-text">
                <strong>First Name:</strong> <?= Html::encode($order->firstname) ?><br>
                <strong>Last Name:</strong> <?= Html::encode($order->lastname) ?><br>
            </p>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title">Order Summary</h5>
            <p class="card-text">
                <strong>Total Price:</strong> <?= Yii::$app->formatter->asCurrency($totalPrice) ?><br>
            </p>
        </div>
    </div>

    <!-- Tambahkan bagian lain dari tampilan checkout di sini -->
</div>

