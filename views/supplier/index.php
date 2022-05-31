<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\bootstrap4\Modal;
use app\models\Supplier;
/* @var $this yii\web\View */
/* @var $searchModel app\models\SupplierForm */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Suppliers');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="supplier-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
      <?= Html::a('Create Supplier', ['create'], ['class' => 'btn btn-success']) ?>
      <?= Html::button('Export', ['class' => 'btn btn-secondary', 'disabled' => true, 'id' => 'column-select-btn', 'data-toggle' => 'modal', 'data-target' => '#export-modal']) ?>
      <span class="alert alert-secondary collapse" role="alert" id="export-tip"></span>
    </p>
    <?php Modal::begin([
        'options' => ['id' => 'export-modal'],
        'title' => 'Select export columns',
        'closeButton' => ['label'],
        'centerVertical' => true,
        'footer' => Html::button('Export', ['class' => 'btn btn-primary', 'id' => 'export-btn']),
    ]); ?>
    <?php foreach($searchModel->attributeLabels() as $name => $label): ?>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" value="<?= $name ?>" id="column-<?= $name ?>" checked <?= $name === 'id' ? 'disabled' : '' ?>>
      <label class="form-check-label" for="column-<?= $name ?>"><?= $label ?></label>
    </div>
    <?php endforeach ?>
    <?php Modal::end(); ?>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'options' => ['id' => 'supplier-grid'],
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
<!-- use block to highlight javascript syntax -->
<?php \app\widgets\JsBlock::begin() ?>
<script>
jQuery(function($) {
  var selectAllPage = false,
      columnSelectBtn = $('#column-select-btn'),
      exportBtn = $('#export-btn'),
      exportTip = $('#export-tip'),
      exportModal = $('#export-modal'),
      selectAllTip = 'All {{number}} suppliers on this page have been selected. <a href="javascript:void(0);">Select all suppliers that match this search</a>',
      clearAllTip = 'All suppliers in this search have been selected. <a href="javascript:void(0);">Clear selection</a>',
      resetExportTip = function() {
        selectAllPage = false;
        exportTip.html(selectAllTip.replace('{{number}}', $('#supplier-grid tbody tr').length));
      },
      resetAll = function() {
        selectAllPage = false;
        exportTip.addClass('collapse');
        resetExportTip();
        columnSelectBtn.prop('disabled', true);
        $('.select-on-check-all').prop('checked', false);
        $('#supplier-grid [name="selection[]"]:checked').each(function(i, item) {
          item.checked = false;
        });
      };

  exportTip.delegate('a', 'click', function(e) {
    selectAllPage = !selectAllPage;
    if (selectAllPage) {
      exportTip.html(clearAllTip);
    } else {
      resetAll();
    }
  });

  $(document).delegate('.select-on-check-all, .checkbox-row', 'change', function(e) {
    selectAllPage = false;
    var checkedNum = $('#supplier-grid [name="selection[]"]:checked').length;
    columnSelectBtn.prop('disabled', checkedNum === 0);

    if (checkedNum > 0 && $('.select-on-check-all:checked').length > 0) {
      if ($('.pagination').length > 0) {
        resetExportTip();
      } else {
        selectAllPage = true;
        exportTip.html(clearAllTip);
      }
      exportTip.removeClass('collapse');
    } else {
      exportTip.addClass('collapse');
    }
  });

  exportBtn.click(function() {
    var ids = [],
        url = '<?= Url::toRoute('supplier/export') ?>',
        indexUrl = '<?= Url::toRoute('supplier/index') ?>',
        sliceIndex = indexUrl.indexOf('?r=');

    url += url.indexOf('?') !== -1 ? '&' : '?';
    if (!selectAllPage) {
      $('#supplier-grid [name="selection[]"]:checked').each(function(i, item) {
        ids.push(item.value);
      });
      url += 'SupplierForm[export_ids]=' + ids.join();
    } else {
      url += window.location.search.slice(1).replace(indexUrl.slice(sliceIndex + 1), '');
    }

    exportModal.find('input[type="checkbox"]:checked').each(function(i, item) {
      url += '&SupplierForm[export_columns][]=' + item.value;
    });

    window.open(url, '_blank');
    exportModal.modal('toggle');
  });

  exportModal.on('show.bs.modal', function() {
    $(this).find('input[type="checkbox"]:not(:checked)').each(function(i, item) {
      item.checked = true;
    });
  });

  $(document).on('pjax:complete', function() {
    exportTip.addClass('collapse');
    resetExportTip();
    columnSelectBtn.prop('disabled', true);
  })
});
</script>
<?php \app\widgets\JsBlock::end() ?>
