<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "cart_items".
 *
 * @property int $id
 * @property int $product_id
 * @property int $quantity
 * @property int|null $created_by
 *
 * @property int $id
 * @property int $product_id
 * @property int $quantity
 * @property int|null $created_by
 * @property User     $createdBy
 * @property Product  $product
 * @property User $createdBy
 * @property Products $product
 */
class CartItem extends \yii\db\ActiveRecord
{
    const SESSION_KEY = 'CART_ITEMS';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cart_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'quantity'], 'required'],
            [['product_id', 'quantity', 'created_by'], 'default', 'value' => null],
            [['product_id', 'quantity', 'created_by'], 'integer'],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }
    public static function getTotalQuantityForUser($currUserId)
    {
        if (\Yii::$app->user->isGuest) {
            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            $sum = 0;
            foreach ($cartItems as $cartItem) {
                $sum += $cartItem['quantity'];
            }
        } else {
            $sum = CartItem::findBySql(
                "SELECT SUM(quantity) FROM cart_items WHERE created_by = :userId", ['userId' => $currUserId]
            )->scalar();
        }
        return $sum;
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'quantity' => 'Quantity',
            'created_by' => 'Created By',
        ];
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery|\common\models\query\UserQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery|\common\models\query\ProductQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Products::class, ['id' => 'product_id']);
    }

    /**
     * {@inheritdoc}
     * @return \common\models\query\CartItemQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \common\models\query\CartItemQuery(get_called_class());
    }
    public static function getTotalPriceForUser($currUserId)
{
    if (\Yii::$app->user->isGuest) {
        $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
        $sum = 0;
        foreach ($cartItems as $cartItem) {
            $sum += $cartItem['quantity'] * $cartItem['price'];
        }
    } else {
        $sum = CartItem::findBySql(
            "SELECT SUM(c.quantity * p.price) 
            FROM cart_items c 
            LEFT JOIN products p on p.id = c.product_id 
            WHERE c.created_by = :userId", ['userId' => $currUserId]
        )->scalar();
    }
    return $sum;
}

    public static function getItemsForUser($currUserId)
    {
        return CartItem::findBySql(
                        "SELECT
                               c.product_id as id,
                               p.image,
                               p.name,
                               p.price,
                               c.quantity,
                               p.price * c.quantity as total_price
                        FROM cart_items c
                                 LEFT JOIN products p on p.id = c.product_id
                         WHERE c.created_by = :userId",
            ['userId' => $currUserId])
            ->asArray()
            ->all();
    }
    
}