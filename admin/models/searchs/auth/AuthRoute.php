<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-05-19 16:10:28
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-01-16 21:14:42
 */


namespace admin\models\searchs\auth;

use yii\base\Model;
use common\components\DataProvider\ArrayDataProvider;
use admin\models\auth\AuthRoute as AuthRouteModel;
use yii\data\Pagination;


/**
 * AuthRoute represents the model behind the search form of `admin\models\auth\AuthRoute`.
 */
class AuthRoute extends AuthRouteModel
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'is_sys', 'pid', 'created_at', 'updated_at', 'route_type'], 'integer'],
            [['name', 'description', 'title', 'data', 'module_name'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider

     */
    public function search($params)
    {
        global $_GPC;
        $query = AuthRouteModel::find()->with(['addons']);


        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return false;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'is_sys' => $this->is_sys,
            'pid' => $this->pid,
            'route_type' => $this->route_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'data', $this->data])
            ->andFilterWhere(['like', 'module_name', $this->module_name]);

        $count = $query->count();
        $pageSize   = $_GPC['pageSize'];
        $page       = $_GPC['page'];
        // ???????????????????????????????????????
        $pagination = new Pagination([
            'totalCount' => $count,
            'pageSize' => $pageSize,
            'page' => $page - 1,
            // 'pageParam'=>'page'
        ]);

        $list = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->asArray()
            ->all();

        //foreach ($list as $key => &$value) {
        //    $value['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
        //    $value['update_time'] = date('Y-m-d H:i:s',$value['update_time']);
        //} 


        $provider = new ArrayDataProvider([
            'key' => 'id',
            'allModels' => $list,
            'totalCount' => isset($count) ? $count : 0,
            'total' => isset($count) ? $count : 0,
            'sort' => [
                'attributes' => [
                    //'member_id',
                ],
                'defaultOrder' => [
                    //'member_id' => SORT_DESC,
                ],
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ]
        ]);

        return $provider;
    }
}
