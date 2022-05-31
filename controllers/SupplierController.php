<?php

namespace app\controllers;

use app\models\Supplier;
use app\models\SupplierSearch;
use app\services\SupplierService;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;

/**
 * SupplierController implements the CRUD actions for Supplier model.
 */
class SupplierController extends Controller
{
    /** {@inheritdoc} */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                ],
            ]
        );
    }

    /**
     * Lists all Supplier models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new SupplierSearch(['scenario' => SupplierSearch::SCENARIO_SEARCH]);
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Supplier model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     *
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Supplier(['scenario' => Supplier::SCENARIO_CREATE]);

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->validate() && $model->save()) {
                return $this->redirect(['index']);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Export selected to csv
     *
     * @return void
     * @throws \yii\web\BadRequestHttpException throws if validation fails
     */
    public function actionExport()
    {
        $searchModel = new SupplierSearch(['scenario' => SupplierSearch::SCENARIO_EXPORT]);
        $dataProvider = $searchModel->export($this->request->queryParams);
        if ($searchModel->hasErrors()) {
            throw new BadRequestHttpException(VarDumper::export($searchModel->getErrors()));
        }

        SupplierService::exportToCsv($searchModel, $dataProvider);
        exit;
    }
}
