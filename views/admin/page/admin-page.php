<?php $this->inject('admin/partials/admin-header') ?>

<main class="layout">
    <aside class="layout__sidebar">
        <?php $this->inject('admin/partials/admin-sidebar') ?>
    </aside>

    <section class="layout__body">
        <h3>Pages</h3>

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
    </section>
</main>

<?php $this->inject('admin/partials/admin-footer') ?>
