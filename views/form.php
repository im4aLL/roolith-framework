<?php /** @var \Roolith\Template\Engine\Interfaces\TemplateContextInterface $this */ ?>
<?php $this->inject('partials/header') ?>

<form method="POST" action="/form">
<?= csrf_field() ?>
<button type="submit">Submit</button>
</form>

<?php $this->inject('partials/footer') ?>
