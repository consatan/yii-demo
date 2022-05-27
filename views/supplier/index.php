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
      <?= Html::button('Export', ['class' => 'btn btn-secondary', 'disabled' => true, 'id' => 'export-btn']) ?>
      <span class="alert alert-secondary collapse" role="alert" id="export-tips"></span>
    </p>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pager' => [
            'class' => 'yii\bootstrap4\LinkPager',
            'firstPageLabel' => '<<',
            'lastPageLabel' => '>>',
            'nextPageLabel' => '>',
            'prevPageLabel' => '<',
        ],
        'columns' => [
            ['class' => 'yii\grid\CheckboxColumn'],

            [
                'label' => 'ID',
                'attribute' => 'id',
                'filterInputOptions' => [
                    'placeholder' => 'Support format:10, >10, [10,20)',
                    'class' => 'form-control',
                ],
            ],
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
<!-- use block to highlight javascript syntax -->
<?php \app\widgets\JsBlock::begin() ?>
<script>
jQuery(function($) {
  var selectAllPage = false,
      exportBtn = $('#export-btn'),
      exportTips = $('#export-tips'),
      selectAllTips = 'All suppliers on this page have been selected. <a href="javascript:void(0);">Select all suppliers that match this search</a>',
      clearAllTips = 'All suppliers in this search have been selected. <a href="javascript:void(0);">Clear selection</a>',
      resetExportTips = function() {
        selectAllPage = false;
        exportTips.html(selectAllTips);
      };

  exportTips.delegate('a', 'click', function(e) {
    selectAllPage = !selectAllPage;
    exportTips.html(selectAllPage ? clearAllTips : selectAllTips);
  });

  $(document).delegate('.select-on-check-all, .checkbox-row', 'change', function(e) {
    selectAllPage = false;
    exportBtn.prop('disabled', $('[name="selection[]"]:checked').length === 0);

    // if ($('.select-on-check-all:checked').length > 0 && $('.pagination').length > 0) {
    if ($('.select-on-check-all:checked').length > 0) {
      resetExportTips();
      exportTips.removeClass('collapse');
    } else {
      exportTips.addClass('collapse');
    }
  });

  exportBtn.click(function() {
    var ids = [],
        url = '<?= Url::toRoute('supplier/export') ?>';

    url += url.indexOf('?') !== -1 ? '&' : '?';
    if (!selectAllPage) {
      $('[name="selection[]"]:checked').each(function(i, item) {
        ids.push(item.value);
      });
      url += 'ids=' + ids.join();
    } else {
      url += window.location.search.slice(1);
    }

    window.open(url, '_blank');
  });

  $(document).on('pjax:complete', function() {
    resetExportTips();
    exportTips.addClass('collapse');
    exportBtn.prop('disabled', true);
  })
});
</script>
<?php \app\widgets\JsBlock::end() ?>
