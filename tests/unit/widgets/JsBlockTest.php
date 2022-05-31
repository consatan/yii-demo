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
        $renderingResult = $widget->view->js[$widget->pos][md5($js)];

        $this->assertEquals($js, $renderingResult);
    }

    public function testMultipJsContent()
    {
        $js1 = "let a = 1;";
        $js2 = "let b = 2;";

        JsBlock::begin();
        echo <<<Javascript
<script>
{$js1}
</script>
<script>
{$js2}
</script>
Javascript;
        $widget = JsBlock::end();

        $expected = <<<DOC

{$js1}
</script>
<script>
{$js2}

DOC;
        $renderingResult = $widget->view->js[$widget->pos][md5($expected)];
        $this->assertEquals($renderingResult, $expected);
    }

    public function testException()
    {
        try {
            JsBlock::begin(['renderInPlace' => true]);
            echo "<script>let a = 1;</script>";
            $widget = JsBlock::end();
        } catch (\Exception $e) {
            $this->assertEquals($e->getMessage(), 'not implemented yet !');
        }
    }
}
