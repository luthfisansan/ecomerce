<?php
<<<<<<< HEAD
/** @var \common\models\Order $order */
/** @var \common\models\OrderAddress $orderAddress */
/** @var array $cartItems */
/** @var int $productQuantity */

/** @var float $totalPrice */

use yii\bootstrap4\ActiveForm;

=======
>>>>>>> 88503521a696b05b095162bdc09a73eee8540dde
?>

<?php $form = ActiveForm::begin([
    'id' => 'checkout-form',
]); ?>
<div class="row">
    <div class="col">
        <div class="card mb-3">
            <div class="card-header">
                <h5>Account information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($order, 'firstname')->textInput(['autofocus' => true]) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($order, 'lastname')->textInput(['autofocus' => true]) ?>
                    </div>
                </div>
                <?= $form->field($order, 'email')->textInput(['autofocus' => true]) ?>

<<<<<<< HEAD
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5>Address information</h5>
            </div>
            <div class="card-body">
                <?= $form->field($orderAddress, 'address') ?>
                <?= $form->field($orderAddress, 'city') ?>
                <?= $form->field($orderAddress, 'state') ?>
                <?= $form->field($orderAddress, 'country') ?>
                <?= $form->field($orderAddress, 'zipcode') ?>
            </div>
=======
<div class="card">
    <div class="card-header">
        <h3>Your cart items</h3>
    </div>
    <div class="card-body p-0">
    <?php if (!empty($items)): ?>
        <table class="table table-hover">
            <thead>
            <tr>
                <th>Product</th>
                <th>Image</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Total Price</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr data-id="<?php echo $item['id'] ?>" data-url="<?php echo \yii\helpers\Url::to(['/cart/change-quantity']) ?>">
                    <td><?php echo $item['name'] ?></td>
                    <td>
                        <img src="<?php echo \common\models\Product::formatImageUrl($item['image']) ?>"
                             style="width: 50px;"
                             alt="<?php echo $item['name'] ?>">
                    </td>
                    <td><?php echo Yii::$app->formatter->asCurrency($item['price']) ?></td>
                    <td>
                        <input type="number" min="1" class="form-control item-quantity" style="width: 60px" value="<?php echo $item['quantity'] ?>">
                    </td>
                    <td><?php echo Yii::$app->formatter->asCurrency($item['total_price']) ?></td>
                    <td>
                        <?php echo \yii\helpers\Html::a('Delete', ['/cart/delete', 'id' => $item['id']], [
                            'class' => 'btn btn-outline-danger btn-sm',
                            'data-method' => 'post',
                            'data-confirm' => 'Are you sure you want to remove this product from cart?'
                        ]) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="card-body text-right">
            <a href="<?php echo \yii\helpers\Url::to(['/cart/checkout']) ?>" class="btn btn-primary">Checkout</a>
        </div>
        <div class="card-footer text-right">
            <!-- <strong>Total Harga Keseluruhan:  -->
>>>>>>> 88503521a696b05b095162bdc09a73eee8540dde
        </div>
    </div>
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h5>Order Summary</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td colspan="2"><?php echo $productQuantity ?> Products</td>
                    </tr>
                    <tr>
                        <td>Total Price</td>
                        <td class="text-right">
                            <?php echo Yii::$app->formatter->asCurrency($totalPrice) ?>
                        </td>
                    </tr>
                </table>

                <p class="text-right mt-3">
                    <button class="btn btn-secondary">Checkout</button>
                </p>
            </div>
        </div>
    </div>
</div>
<?php ActiveForm::end(); ?>