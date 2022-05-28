<?php

namespace app\services;

use app\models\SupplierSearch;
use yii\data\ActiveDataProvider;

class SupplierService
{
    /**
     * Export db query to csv file.
     *
     * @param \app\models\SupplierSearch $supplierModel
     * @param \yii\data\ActiveDataProvider $dataProvider
     *
     * @return void
     * @throws \Throwable throw if db query fails
     */
    public static function exportToCsv(SupplierSearch $supplierModel, ActiveDataProvider $dataProvider)
    {
        $columns = $supplierModel->export_columns;
        $labels = $supplierModel->attributeLabels();
        $header = [];
        foreach ($columns as $key) {
            $header[] = $labels[$key];
        }

        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=suppliers_" . date('YmdHis') . ".csv");
        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: binary");

        // Using `$this->response->sendStreamAsFile` requires waiting for db query to complete
        // and write all bytes to the stream before send response.
        // The client must wait for the response to be ready before starting the download.
        //
        // Use batch db query along with `php://output` to output stream, save client time and server's memory(or disk space)
        $handle = fopen('php://output', 'wb');
        fputcsv($handle, $header);
        try {
            foreach ($dataProvider->query->each() as $supplier) {
                $row = [];
                foreach ($columns as $key) {
                    $row[] = $supplier[$key] ?? null;
                }

                fputcsv($handle, $row);
            }
        } catch (\Throwable $t) {
            throw $t;
        } finally {
            fclose($handle);
        }

        exit;
    }
}
