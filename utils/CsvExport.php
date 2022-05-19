<?php
namespace app\utils;

use yii\db\ActiveQuery;

/**
 * CsvExport
 *
 * helper class to output an CSV from a CActiveRecord array.
 *
 * example usage:
 *
 * CsvExport::export(
 *   People::model()->findAll(), // a CActiveRecord array OR any CModel array
 *   array(
 *     'idpeople'=>array('number'),      'number' and 'date' are strings used by CFormatter
 *     'birthofdate'=>array('date'),
 *   )
 *   ,true,'registros-hasta--'.date('d-m-Y H-i').".csv");
 *
 *
 * Please refer to CFormatter about column definitions, this class will use CFormatter.
 *
 * @author  Christian Salazar <christiansalazarh@gmail.com> @bluyell @yiienespanol (twitter)
 * @author  Chopin Ngo <consatan@gmail.com>
 * @licence Protected under MIT Licence.
 * @date 07 october 2012.
 */
class CsvExport
{
    /**
     * export a data set to CSV output.
     * Please refer to CFormatter about column definitions, this class will use CFormatter.
     *
     * @param \yii\db\ActiveQuery $query
     * @param array $coldefs example: 'colname'=>array('number') (See also CFormatter about this string)
     * @param bool $printHeader    boolean, true print col headers taken from coldefs array key
     * @param ?string $fileName if set (defaults null) it echoes the output to browser using binary transfer headers
     * @param string $separator if set (defaults to ';') specifies the separator for each CSV field
     *
     * @return string
     * @throws \yii\base\InvalidArgumentException throw if value format failed
     */
    public static function export(
        ActiveQuery $query,
        array $coldefs,
        bool $printHeader = true,
        ?string $fileName = null,
        string $separator = ','
    ): string {
        $eof = "\r\n";
        $content = '';

        if ($fileName !== null) {
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename={$fileName}");
            header("Content-Type: application/octet-stream");
            header("Content-Transfer-Encoding: binary");
        }

        if ($printHeader) {
            $header = implode($separator, array_keys($coldefs)). $eof;
            if ($fileName !== null) {
                echo $header;
            } else {
                $content .= $header;
            }
        }

        $separatorLen = -1 * strlen($separator);
        foreach ($query->asArray()->batch(500) as $rows) {
            foreach ($rows as $row) {
                $line = '';
                foreach ($coldefs as $col => $config) {
                    if (isset($row[$col])) {
                        $val = $row[$col];
                        if (is_array($config)) {
                            foreach ($config as $conf) {
                                if(!empty($conf)) {
                                    $val = \Yii::$app->formatter->format($val, $conf);
                                }
                            }
                        }

                        $line .= $val . $separator;
                    }
                }

                $item = trim(substr($line, 0, $separatorLen)) . $eof;
                if ($fileName !== null) {
                    echo $item;
                } else {
                    $content .= $item;
                }
            }
        }

        return $content;
    }
}
