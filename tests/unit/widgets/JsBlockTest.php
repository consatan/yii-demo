<?php

namespace tests\unit\widgets;

use app\widgets\JsBlock;
use Yii;

class JsBlockTest extends \Codeception\Test\Unit
{
    public function testJSContent()
    {
        $js = "let a = 1;";

        JsBlock::begin();
        echo "<script>";
        echo $js;
        echo "</script>";
        $widget = JsBlock::end();
        $renderingResult = current($widget->view->js[$widget->pos]);

        verify($renderingResult)->equals($js);
    }
}
