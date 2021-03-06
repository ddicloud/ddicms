<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-06-02 17:20:53
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-06-22 17:49:50
 */

namespace admin\controllers\addons;

use admin\controllers\AController;
use admin\models\addons\models\Bloc as BlocSearch;
use admin\models\enums\BlocStatus;
use admin\models\enums\ReglevelStatus;
use common\helpers\ArrayHelper;
use common\helpers\ErrorsHelper;
use common\helpers\ImageHelper;
use common\helpers\ResultHelper;
use diandi\addons\models\Bloc;
use diandi\addons\models\BlocLevel;
use diandi\addons\models\BlocStore;
use diandi\admin\models\AuthAssignmentGroup;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * BlocController implements the CRUD actions for Bloc model.
 */
class BlocController extends AController
{
    public $modelSearchName = 'BlocSearch';

    public $modelClass = '';

    public $extras = [];

    /**
     * Lists all Bloc models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new BlocSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $dataProvider = ArrayHelper::objectToarray($dataProvider);

        $parentMent = $dataProvider['allModels'];

        $lists = ArrayHelper::itemsMerge($parentMent, 0, 'bloc_id', 'pid', 'children');

        return ResultHelper::json(200, '获取成功', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'list' => $lists,
        ]);
    }

    public function actionStoreGroup()
    {
        // 校验数据权限
        $user_id = Yii::$app->user->id;
        $group = AuthAssignmentGroup::find()->where(['user_id' => $user_id])->asArray()->all();
        $where = [];
        $list = [];
        $defaultRoles = Yii::$app->authManager->defaultRoles;
        // 确定我的权限角色与系统默认有交集，name就显示所有集团
        $diff = array_intersect($defaultRoles, array_column($group, 'item_name'));
        $where = [];
        if (empty($diff)) {
            // 查找自己的数据
            $store_ids = array_column($group, 'store_id');
            $where['s.store_id'] = $store_ids;
        }

        $list = Bloc::find()->alias('b')->joinWith('store as s')->filterWhere($where)->asArray()->all();

        foreach ($list as $key => &$value) {
            $value['label'] = $value['business_name'];
            $value['id'] = $value['bloc_id'];
            $store = $value['store'];
            if (!empty($value['store'])) {
                foreach ($store as $k => &$val) {
                    $val['label'] = $val['name'];
                    $val['id'] = $val['store_id'];
                }
                $value['children'] = $store;
                $lists[] = $value;
            } else {
                unset($list[$key]);
            }
        }

        return ResultHelper::json(200, '获取成功', [
            'list' => $lists,
        ]);
    }

    public function actionStoreList()
    {
        $stores = BlocStore::find()->with(['bloc'])->asArray()->all();
        foreach ($stores as $key => &$value) {
            $value['logo'] = ImageHelper::tomedia($value['logo']);
        }

        return ResultHelper::json(200, '获取成功',$stores);
    }

    /**
     * Displays a single Bloc model.
     *
     * @param int $id
     *
     * @return mixed
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $view = Bloc::find()->where(['bloc_id' => $id])->asArray()->one();

        $view['extra'] = !empty($view['extra']) ? unserialize($view['extra']) : $view['extra'];

        $view['provinceCityDistrict'] = [$view['province'], $view['city'], $view['district']];

        return ResultHelper::json(200, '获取成功', $view);
    }

    /**
     * Creates a new Bloc model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        global $_GPC;

        $model = new Bloc();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            $data['province'] = $data['provinceCityDistrict']['0'];
            $data['city'] = $data['provinceCityDistrict']['1'];
            $data['district'] = $data['provinceCityDistrict']['2'];

            $pid = $_GPC['pid'];
            $data['group_bloc_id'] = Bloc::find()->where(['bloc_id' => $pid])->select('group_bloc_id')->scalar();

            if ($model->load($data, '') && $model->save()) {
                return ResultHelper::json(200, '创建成功', $model);
            } else {
                $msg = ErrorsHelper::getModelError($model);

                return ResultHelper::json(400, $msg);
            }
        }
    }

    /**
     * Updates an existing Bloc model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param int $id
     *
     * @return mixed
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        global $_GPC;

        $model = $this->findModel($id);

        if (Yii::$app->request->isPut) {
            $data = Yii::$app->request->post();

            $data['province'] = $data['provinceCityDistrict']['0'];
            $data['city'] = $data['provinceCityDistrict']['1'];
            $data['district'] = $data['provinceCityDistrict']['2'];

            $pid = $_GPC['pid'];
            $data['group_bloc_id'] = Bloc::find()->where(['bloc_id' => $pid])->select('group_bloc_id')->scalar();
            if ($model->load($data, '') && $model->save()) {
                return ResultHelper::json(200, '编辑成功', $model);
            } else {
                $msg = ErrorsHelper::getModelError($model);

                return ResultHelper::json(400, $msg);
            }
        }
    }

    /**
     * Deletes an existing Bloc model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param int $id
     *
     * @return mixed
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return ResultHelper::json(200, '删除成功');
    }

    /**
     * Finds the Bloc model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return Bloc the loaded model
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Bloc::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('请检查数据是否存在');
    }

    /**
     * Creates a new Bloc model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionParentbloc()
    {
        $model = new Bloc([
            'extras' => $this->extras,
        ]);

        $parents = $model->find()->select(['bloc_id', 'pid', 'business_name as label', 'bloc_id as id'])->asArray()->all();

        $parentBloc = ArrayHelper::itemsMerge($parents, 0, 'bloc_id', 'pid', 'children');

        return ResultHelper::json(200, '获取成功', $parentBloc);
    }

    /**
     * Creates a new Bloc model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionChildbloc()
    {
        global $_GPC;

        $model = new Bloc([
            'extras' => $this->extras,
        ]);

        $where = [];

        $where['pid'] = Yii::$app->params['bloc_id'];

        if (!empty($_GPC['level_num'])) {
            $where['level_num'] = $_GPC['level_num'];
        }

        $childs = $model->find()->where($where)->select(['bloc_id', 'pid', 'business_name as label', 'bloc_id as id'])->asArray()->all();

        return ResultHelper::json(200, '获取成功', $childs);
    }

    /**
     * Updates an existing Bloc model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param int $id
     *
     * @return mixed
     *
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionStores($id)
    {
        $stores = BlocStore::find()->where(['bloc_id' => $id])->asArray()->all();

        return ResultHelper::json(200, '获取成功', $stores);
    }

    public function actionReglevel()
    {
        $list = ReglevelStatus::listData();

        $lists = [];

        foreach ($list as $key => $value) {
            $lists[] = [
                'text' => $value,
                'value' => $key,
            ];
        }

        return ResultHelper::json(200, '获取成功', $lists);
    }

    public function actionBlocstatus()
    {
        $list = BlocStatus::listData();

        $lists = [];

        foreach ($list as $key => $value) {
            $lists[] = [
                'text' => $value,
                'value' => $key,
            ];
        }

        return ResultHelper::json(200, '获取成功', $lists);
    }

    public function actionLevels()
    {
        global $_GPC;

        $global_bloc_id = Yii::$app->params['global_bloc_id'];

        $levels = BlocLevel::find()->where(['global_bloc_id' => $global_bloc_id])->select(['level_num as value', 'name as text'])->asArray()->all();

        return ResultHelper::json(200, '获取成功', $levels);
    }
}
