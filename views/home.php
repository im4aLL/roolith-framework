<?php /** @var \Roolith\Template\Engine\Interfaces\TemplateContextInterface $this */ ?>
<?php $this->inject('partials/header') ?>

<p><?= $this->escape('content') ?></p>

<?php $this->inject('partials/footer') ?>
