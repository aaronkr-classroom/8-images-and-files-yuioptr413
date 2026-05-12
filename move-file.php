<?php
// upload
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if ($_FILES['image']['error'] === 0) {
    // 임시 경고와 새로운 목적지를 저장한다.
    $temp = $_FILES['image']['tmp_name'];
    $path = 'uploads/' . $_FILES['image']['name']; // candy.phg

    // 파일을 이동하고 그 결과를 $moved에 저장한다.
    $moved = move_uploaded_file($temp, $path);
  } 
  if ($moved) {
    $message = '<img src="' . $path . '">'; // 웹사이트에서 표시
  } else {
    $message = 'File could not be uploaded.';
  }
}

// Move file code

?>
<?php include 'includes/header.php' ?>
<?= $message ?>
<form method="POST" action="move-file.php" enctype="multipart/form-data">
  <label for="image"><b>Upload file:</b></label>
  <input type="file" name="image" accept="image/*" id="image"><br>
  <input type="submit" value="upload">
</form>
<?php include 'includes/footer.php' ?>