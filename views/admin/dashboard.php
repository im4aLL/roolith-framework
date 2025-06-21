<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h2><?= $this->escape('content') ?></h2>

        <p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Mollitia veniam accusamus deserunt ratione odit rerum quaerat a molestiae corporis, tempore molestias officia ut corrupti dolore vero? Beatae quas <a href="">laboriosam</a> fuga.</p>

        <table class="table">
            <caption>
                Front-end web developer course 2021
            </caption>
            <thead>
                <tr>
                    <th scope="col">Person</th>
                    <th scope="col">Most interest in</th>
                    <th scope="col">Age</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th scope="row">Chris</th>
                    <td>HTML tables</td>
                    <td>22</td>
                </tr>
                <tr>
                    <th scope="row">Dennis</th>
                    <td>Web accessibility</td>
                    <td>45</td>
                </tr>
                <tr>
                    <th scope="row">Sarah</th>
                    <td>JavaScript frameworks</td>
                    <td>29</td>
                </tr>
                <tr>
                    <th scope="row">Karen</th>
                    <td>Web performance</td>
                    <td>36</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row" colspan="2">Average age</th>
                    <td>33</td>
                </tr>
            </tfoot>
        </table>

    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>