<?php
$moved         = false;                                        // Initialize
$message       = '';                                           // Initialize
$error         = '';                                           // Initialize
$upload_path   = 'uploads/';                                   // Upload path
$max_size      = 5242880;                                      // Max file size
$allowed_types = ['image/jpeg', 'image/png', 'image/gif',];    // Allowed file types
$allowed_exts  = ['jpeg', 'jpg', 'png', 'gif',];               // Allowed file extensions

function create_filename($filename, $upload_path)              // Function to make filename
{
    $basename   = pathinfo($filename, PATHINFO_FILENAME);      // Get basename
    $extension  = pathinfo($filename, PATHINFO_EXTENSION);     // Get extension
    $basename   = preg_replace('/[^A-z0-9]/', '-', $basename); // Clean basename
    $i          = 0;                                           // Counter
    while (file_exists($upload_path . $filename)) {            // If file exists
        $i        = $i + 1;                                    // Update counter 
        $filename = $basename . $i . '.' . $extension;         // New filepath
    }
    return $filename;                                          // Return filename
}

function crop_and_resize_image_gd(
    $orig_path, $new_path, $new_width, $new_height
) {
    $image_data  = getimagesize($orig_path);
    $orig_path   = $image_data[0];
    $orig_height = $image_data[1];
    $media_type  = $image_data['mime'];
    $orig_ratio  = $orig_width / $orig_height;
    $new_ratio   = $new_width / $new_height;

    // 새로운 크기 계산하기
    if ($new_ratio < $orig_ratio) {
        $select_width = $orig_height * $new_ratio;     // 너비 계산
        $select_height = $orig_height;
        $x_offset = ($orig_width - $select_width) / 2; // 중간 위치 계산
        $y_offset = 0;  // (top)
    } else {
        $select_width = $orig_width;
        $select_height = $orig_width * $new_ratio;       // 높이 계산
        $x_offset = 0;  // (left)
        $y_offset = ($orig_height - $select_height) / 2; // 중간 위치 계산
    }

    // 미디어 타입 확인
    switch($media_type) {
        case 'image/gif';
            $orig = imagecreatefromgif($orig_path);
            break;
        case 'image/jpeg';
            $orig = imagecreatefromjpeg($orig_path);
            break;
        case 'image/png';
            $orig = imagecreatefrompng($orig_path);
            break;    
    }

    // 새로운 빈 이미지 생성
    $new = imagecreatertruecolor($new_width, $new_height);
    // 이미지를 자르고 크기 조정
    imagecopyresampled($new, $orig, 0, 0, $x_offset, $y_offset,
        $new_width, $new_height, $select_widthm, $select_height);

    // 새로운 조정한 이미지를 ... 드디어... 저장하기
    switch($media_type) {
        case 'image/gif'  : $result = imagegif($new, $new_path); break;
        case 'image/jpeg' : $result = imagejpeg($new, $new_path); break;
        case 'image/png'  : $result = imagepng($new, $new_path); break;    
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {                    // If form submitted
    $error = ($_FILES['image']['error'] === 1) ? 'too big ' : '';  // Check size error

    if ($_FILES['image']['error'] == 0) {                          // If no upload errors
        $error  .= ($_FILES['image']['size'] <= $max_size) ? '' : 'too big '; // Check size
        // Check the media type is in the $allowed_types array
        $type   = mime_content_type($_FILES['image']['tmp_name']);        
        $error .= in_array($type, $allowed_types) ? '' : 'wrong type ';
        // Check the file extension is in the $allowed_exts array
        $ext    = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $error .= in_array($ext, $allowed_exts) ? '' : 'wrong file extension ';

        // If there are no errors create the new filepath and try to move the file
        if (!$error) {
          $filename    = create_filename($_FILES['image']['name'], $upload_path);
          $destination = $upload_path . $filename;
          $thumbpath   = $upload_path . 'thumb_' . $filename;
          $moved       = move_uploaded_file($_FILES['image']['tmp_name'], $destination);
          $resized     = crop_and_resize_image_gd($destination, $thumbpath, 200, 200);
        }
    }
    if ($moved === true and $resized === true) {                        // If it moved
        $message = '<img src="' . $thumbpath . '">';                  // Show image
    } else {                                                            // Otherwise
        $message = '<b>Could not upload file</b> ' . $error;            // Show errors
    }
}
?>
<?php include 'includes/header.php' ?>
<?= $message ?>
  <form method="POST" action="crop-and-resize-gd.php" enctype="multipart/form-data">
    <label for="image"><b>Upload file:</b></label>
    <input type="file" name="image" accept="image/*" id="image"><br>
    <input type="submit" value="upload">
  </form>
<?php include 'includes/footer.php' ?>