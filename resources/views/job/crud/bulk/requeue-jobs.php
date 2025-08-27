<?php
$form = $view->form();
?>
<div class="modal modal-fade" data-modal='{"id": "jobs-requeue"}'>
    <div class="modal-background"></div>
    <div class="modal-content modal-l">
        <?= $form->form(['action' => $action->getUrl()]) ?>
        <div class="modal-body">
            <p class="py-xs text-body"><?= $view->etrans('Are you sure you want to requeue all selected jobs?') ?></p>
        </div>
        <div class="modal-foot">
            <div class="buttons spaced">
                <span class="button primary" data-bulk-save="jobs-requeue"><?= $view->etrans('Requeue') ?></span>
                <span class="link modal-close"><?= $view->etrans('Cancel') ?></span>
            </div>
        </div>
        <?= $form->close() ?>
    </div>
</div>