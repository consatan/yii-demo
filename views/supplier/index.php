<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\Supplier;
/* @var $this yii\web\View */
/* @var $searchModel app\models\SupplierSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Suppliers');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="supplier-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Supplier', ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::button('Export', ['class' => 'btn btn-secondary collapse', 'id' => 'export-btn']) ?>
    </p>

    <div class="alert alert-secondary collapse" role="alert" id="select-all">
    All <span id="current-rows"><?= sizeof($dataProvider->getModels()) ?></span> conversations on this page have been selected. <a href='javascript:selectAll(1);'>Select all conversations that match this search</a>
    </div>
    <div class="alert alert-secondary collapse" role="alert" id="clear-all">
      All conversations in this search have been selected. <a href='javascript:selectAll(0);'>clear selection</a>
    </div>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pager' => [
            'firstPageLabel' => '<<',
            'lastPageLabel' => '>>',
            'nextPageLabel' => '>',
            'prevPageLabel' => '<',
            'linkContainerOptions' => ['class' => ['page-item']],
            'linkOptions' => ['class' => ['page-link']],
            'disabledListItemSubTagOptions' => ['class' => 'page-link'],
        ],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            ['class' => 'yii\grid\CheckboxColumn'],

            'id',
            'name',
            'code',
            [
                'label' => 'Status',
                'attribute' => 't_status',
                'filter' => ['ok' => 'OK', 'hold' => 'Hold'],
                'filterInputOptions' => ['prompt' => 'All', 'class' => 'form-control', 'id' => null],
                'format' => function($v) {
                    return $v === 'ok' ? 'OK' : 'Hold';
                },
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
<?php \app\widgets\JsBlock::begin() ?>
<script>
let selectAllPage = false;
function selectAll(all) {
    if (all) {
        selectAllPage = true;
        $('#select-all').hide();
        $('#clear-all').show();
    } else {
        selectAllPage = false;
        $('#clear-all').hide();
        $('#select-all').show();
    }
};
jQuery(function($) {
  var checkfn = function() {
    $('.select-on-check-all, .checkbox-row').change(function() {
      selectAllPage = false;

      if ($('[name="selection[]"]:checked').length > 0) {
        $('#export-btn').removeClass('collapse');
      } else {
        $('#export-btn').addClass('collapse');
      }

      if ($('.select-on-check-all:checked').length > 0 && $('.pagination').length > 0) {
          $('#select-all').show();
          return;
      }

      $('#select-all').hide();
      $('#clear-all').hide();
    });
  };


  $('#export-btn').click(function() {
      var url = window.location.href;
      var checkedIds = [];
      if (!selectAllPage) {
        $('[name="selection[]"]:checked').each(function(i, item) {
          checkedIds.push(item.value);
        });
      }
      checkedIds = checkedIds.join();
      url += (url.indexOf("?") === -1 ? "?" : "&") + "export=1&checked_ids=" + checkedIds + "&select_all=" + (selectAllPage ? 1 : 0);

      window.open(url, "_blank");
  });

  checkfn();
  $(document).on('pjax:complete', function() {
    selectAllPage = false;
    $('#select-all').hide();
    $('#clear-all').hide();
    $('#export-btn').addClass('collapse');
    $('#current-rows').text($('[name="selection[]"]').length);
    checkfn();
  })
});
</script>
<?php \app\widgets\JsBlock::end() ?>
