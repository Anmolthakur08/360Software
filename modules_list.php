<!DOCTYPE html>
<html lang="en">

</html>
<?php $title = 'Contact Form Page';
include_once('../head.php'); ?>

<body>
    <?php include_once('../navbar.php'); ?>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <p class="text-center"><?php if (isset($_SESSION['success']))
                    echo $_SESSION['success']; ?></p>
                <p class="text-center"><?php if (isset($_SESSION['error']))
                    echo $_SESSION['error']; ?></p>
                <h2 class="text-center">Manage Modules</h2>
                <a href="?module-form" class="btn btn-primary">Add Module</a>
                <!-- <a href="?modules-matching-list" class="btn btn-secondary">Module Matching</a> -->
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <svg class="svg-inline--fa fa-table fa-w-16 me-1" aria-hidden="true" focusable="false"
                            data-prefix="fas" data-icon="table" role="img" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 512 512" data-fa-i2svg="">
                            <path fill="currentColor"
                                d="M464 32H48C21.49 32 0 53.49 0 80v352c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V80c0-26.51-21.49-48-48-48zM224 416H64v-96h160v96zm0-160H64v-96h160v96zm224 160H288v-96h160v96zm0-160H288v-96h160v96z">
                            </path>
                        </svg>
                        <font style="vertical-align: inherit;">Modules</font>
                    </div>
                    <table class="table mt-3 text-center" id="modulelistdt">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                            <?php
                            foreach ($modules as $module) {
                                ?>
                                <tr>
                                    <td><?php echo $module['name']; ?></td>
                                    <td><?php echo $module['description']; ?></td>
                                    <td><?php echo $module['date']; ?></td>
                                    <td><a type='button' class="btn btn-primary"
                                            href="?findModule=<?php echo $module['id']; ?>">Edit</a>
                                        <a href="?deleteModule=<?php echo $module['id']; ?>" class="btn btn-danger"
                                            onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
    include_once('../footer.php');
    ?>
    <script>
        $(document).ready(function () {
            $('#modulelistdt').DataTable({
                "paging": true,
                "searching": true,
                "ordering": true,
                "language": {
                    "emptyTable": "No module found."
                }
            });
        });
    </script>
</body>

</html>