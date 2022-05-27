<?php

namespace app\controllers;

use app\models\Supplier;
use app\models\SupplierSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SupplierController implements the CRUD actions for Supplier model.
 */
class SupplierController extends Controller
{
    /**
     * {@inheritdoc}
     */
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
        $searchModel = new SupplierSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Supplier model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Supplier();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
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
     * @return void|string  return error message in index page if validation fails
     */
    public function actionExport()
    {
        $searchModel = new SupplierSearch();
        $dataProvider = $searchModel->export($this->request->queryParams);
        if ($searchModel->hasErrors()) {
            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        }

        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=supplier_" . date('YmdHis') . ".csv");
        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: binary");

        // Using `$this->response->sendStreamAsFile` requires waiting for db query to complete
        // and write all bytes to the stream before send response.
        // The client must wait for the response to be ready before starting the download.
        //
        // Use batch db query along with `php://output` to output stream, save client time and server's memory(or disk space)
        $handle = fopen('php://output', 'wb');
        fputcsv($handle, ['id', 'name', 'code', 'status']);
        foreach ($dataProvider->query->each() as $supplier) {
            fputcsv($handle, [$supplier->id, $supplier->name, $supplier->code, $supplier->t_status]);
        }
        fclose($handle);
        exit;
    }
}
