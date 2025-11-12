<?php include "db_connect.php"; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4">

    <h2>Add New Product</h2>
    <form action="" method="POST" enctype="multipart/form-data">

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Category</label>
            <input type="text" name="category" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Price</label>
            <input type="number" step="0.01" name="price" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label>Image</label>
            <input type="file" name="image" class="form-control">
        </div>

        <button class="btn btn-success" name="submit">Save</button>
    </form>

    <?php
    if (isset($_POST['submit'])) {

        $file_name = $_FILES['image']['name'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $path = "uploads/" . $file_name;

        move_uploaded_file($file_tmp, $path);

        $mysqli->query("
            INSERT INTO products (name, category, price, description, image)
            VALUES ('$_POST[name]', '$_POST[category]', '$_POST[price]', '$_POST[description]', '$path')
        ");

        header("Location: index.php");
    }
    ?>
</body>

</html>
