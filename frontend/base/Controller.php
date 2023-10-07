<?php
/**
 * User: TheCodeholic
 * Date: 12/12/2020
 * Time: 7:04 PM
 */

namespace frontend\base;


use common\models\CartItem;

/**
 * Class Controller
 *
 * @package frontend\base
 */
class Controller extends \yii\web\Controller
{
    public function beforeAction($action)
    {
        $this->view->params['cartItemCount'] = CartItem::getTotalQuantityForUser(\Yii::$app->user->id);


        return parent::beforeAction($action);
    }
}