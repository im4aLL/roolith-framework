<?php $this->inject('partials/header') ?>

<form action="<?= route('welcome.form') ?>" method="post">
    <label for="name">Name</label>
    <input type="text" id="name" name="name">

    <label for="email">Email</label>
    <input type="email" id="email" name="email">

    <button type="submit">Submit</button>
</form>

<?php $this->inject('partials/footer') ?>
