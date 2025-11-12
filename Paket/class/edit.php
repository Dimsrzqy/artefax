<?php include "db_connect.php";

$id = $_GET['id'];
$result = $mysqli->query("SELECT * FROM products WHERE id=$id");
$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4">

    <h2>Edit Product</h2>
    <form action="" method="POST" enctype="multipart/form-data">

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" value="<?= $data['name'] ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label>Category</label>
            <input type="text" name="category" value="<?= $data['category'] ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label>Price</label>
            <input type="number" step="0.01" name="price" value="<?= $data['price'] ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control"><?= $data['description'] ?></textarea>
        </div>

        <div class="mb-3">
            <label>Current Image</label><br>
            <img src="<?= $data['image'] ?>" width="100"><br><br>
            <input type="file" name="image" class="form-control">
        </div>

        <button class="btn btn-success" name="update">Update</button>
    </form>

    <?php
    if (isset($_POST['update'])) {

        $image = $data['image'];

        if ($_FILES['image']['name'] != "") {
            $newFile = "uploads/" . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], $newFile);
            $image = $newFile;
        }

        $mysqli->query("
            UPDATE products 
            SET name='$_POST[name]', category='$_POST[category]', price='$_POST[price]',
                description='$_POST[description]', image='$image'
            WHERE id=$id
        ");

        header("Location: index.php");
    }
    ?>
</body>
</html>
