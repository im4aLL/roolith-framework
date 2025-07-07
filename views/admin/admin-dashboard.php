<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3><?= $this->escape('content') ?></h3>

        <p>You have last logged in at <time datetime="2021-01-01T00:00:00+00:00"><?= $lastLoggedIn ?></time></p>

        <ul class="card">
            <li class="card__item"><span class="card__info"><?= $pageCount->published_count ?></span> Published Pages</li>
            <li class="card__item"><span class="card__info"><?= $pageCount->draft_count ?></span> Unpublished Pages</li>
            <li class="card__item"><span class="card__info">20</span> Published Modules</li>
            <li class="card__item"><span class="card__info">2</span> Unpublished Modules</li>
        </ul>

        <p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Mollitia veniam accusamus deserunt ratione odit rerum quaerat a molestiae corporis, tempore molestias officia ut corrupti dolore vero? Beatae quas <a href="">laboriosam</a> fuga.</p>

        <table class="table">
            <caption>
                Front-end web developer course 2021
            </caption>
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Most interest in</th>
                    <th>Age</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Chris</td>
                    <td>HTML tables</td>
                    <td>22</td>
                </tr>
                <tr>
                    <td>Dennis</td>
                    <td>Web accessibility</td>
                    <td>45</td>
                </tr>
                <tr>
                    <td>Sarah</td>
                    <td>JavaScript frameworks</td>
                    <td>29</td>
                </tr>
                <tr>
                    <td>Karen</td>
                    <td>Web performance</td>
                    <td>36</td>
                </tr>
            </tbody>
        </table>

        <form action="" class="form">
            <div class="form__field form__field--error">
                <label for="" class="form__label">Label</label>
                <input type="text" name="" id="" class="form__input">
                <span class="form__hint">Error Message</span>
            </div>

            <div class="form__field">
                <label for="" class="form__label">Label</label>
                <input type="number" name="" id="" class="form__input">
            </div>

            <div class="form__field">
                <label for="" class="form__label">Label</label>
                <input type="email" name="" id="" class="form__input">
            </div>

            <div class="form__field">
                <label for="" class="form__label">Label</label>
                <select name="" id="" class="form__input form--select">
                    <option></option>
                    <option>Option</option>
                    <option>Option</option>
                    <option>Option</option>
                </select>
            </div>

            <div class="form__field">
                <label for="" class="form__label">Label</label>
                <select name="" id="" class="form__input form--select" multiple>
                    <option></option>
                    <option>Option</option>
                    <option>Option</option>
                    <option>Option</option>
                </select>
            </div>

            <div class="form__field">
                <label for="" class="form__label">Label</label>
                <input type="radio" name="aa" id="" class="form__input form__input--radio">
                <input type="radio" name="aa" id="" class="form__input form__input--radio">
            </div>

            <div class="form__field">
                <label><input type="checkbox" id="" class="form__input form__input--checkbox"> Male</label>
                <label><input type="checkbox" id="" class="form__input form__input--checkbox"> Female</label>
            </div>

            <div class="form__field">
                <label for="" class="form__label">Label</label>
                <textarea name="" id="" class="form__input form--textarea"></textarea>
            </div>

            <div class="form__field">
                <label for="" class="form__label">Label</label>
                <input type="date" name="" id="" class="form__input form--date">
            </div>

            <button class="button">Submit</button>
            <a href="" class="button">Submit</a>
        </form>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
