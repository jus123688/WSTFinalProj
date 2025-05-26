<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$xmlFile = "users.xml";

if (!file_exists($xmlFile) || filesize($xmlFile) == 0) {
    $initXml = new SimpleXMLElement("<users></users>");
    $initXml->asXML($xmlFile);
}

$usersXml = simplexml_load_file($xmlFile);

function saveXML($xml) {
    global $xmlFile;
    $xml->asXML($xmlFile);
}

function handleImageUpload($fileInputName) {
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES[$fileInputName]['tmp_name'];
        $fileName = basename($_FILES[$fileInputName]['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {
            $newFileName = uniqid() . '.' . $ext;
            $targetFile = $uploadDir . $newFileName;

            if (move_uploaded_file($tmpName, $targetFile)) {
                return $targetFile;
            }
        }
    }
    return "https://via.placeholder.com/80?text=No+Image";
}

// Handle registration
if (isset($_POST['register'])) {
    $xml = simplexml_load_file($xmlFile);

    // Check if email already exists
    foreach ($xml->user as $existingUser) {
        if ($existingUser->email == $_POST['email']) {
            $error = "Email already registered.";
            break;
        }
    }

    if (!isset($error)) {
        $newUser = $xml->addChild("user");
        $newUser->addChild("id", uniqid());
        $newUser->addChild("name", htmlspecialchars($_POST['name']));
        $newUser->addChild("email", htmlspecialchars($_POST['email']));
        $newUser->addChild("password", $_POST['password']);
        $picturePath = handleImageUpload('picture');
        $newUser->addChild("picture", $picturePath);
        saveXML($xml);
        $_SESSION['email'] = $_POST['email'];
        header("Location: index.php");
        exit;
    }
}

// Handle login
if (isset($_POST['login'])) {
    foreach ($usersXml->user as $user) {
        if ($user->email == $_POST['email'] && $user->password == $_POST['password']) {
            $_SESSION['email'] = $_POST['email'];
            header("Location: index.php");
            exit;
        }
    }
    $error = "Invalid email or password";
}

// Handle edit user
if (isset($_POST['edit_user'])) {
    foreach ($usersXml->user as $user) {
        if ($user->id == $_POST['id']) {
            $user->name = htmlspecialchars($_POST['name']);
            $user->email = htmlspecialchars($_POST['email']);
            if (isset($_FILES['picture']) && $_FILES['picture']['error'] == UPLOAD_ERR_OK) {
                $newPic = handleImageUpload('picture');
                if ($newPic != "https://via.placeholder.com/80?text=No+Image") {
                    $user->picture = $newPic;
                }
            }
            saveXML($usersXml);
            break;
        }
    }
    header("Location: index.php");
    exit;
}

// Handle delete user
if (isset($_POST['delete_user'])) {
    $index = 0;
    foreach ($usersXml->user as $user) {
        if ($user->id == $_POST['id']) {
            $pic = (string)$user->picture;
            if (strpos($pic, 'uploads/') === 0 && file_exists($pic)) {
                unlink($pic);
            }
            unset($usersXml->user[$index]);
            saveXML($usersXml);
            break;
        }
        $index++;
    }
    header("Location: index.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Library User Management - Modern UI</title>
<style>
  /* (same CSS you already have, omitted here for brevity) */
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap');
  * {
    box-sizing: border-box;
  }
  body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #333;
    min-height: 100vh;
  }
  a {
    text-decoration: none;
    color: #764ba2;
    font-weight: 600;
    transition: color 0.3s ease;
  }
  a:hover {
    color: #f8a5c2;
  }
  .container {
    max-width: 900px;
    margin: 50px auto;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    padding: 30px 40px;
  }
  h1, h2, h3 {
    margin-bottom: 20px;
    color: #4a148c;
  }
  input[type=text], input[type=email], input[type=password], input[type=file] {
    width: 100%;
    padding: 12px 15px;
    margin: 10px 0 20px 0;
    border: 2px solid #ddd;
    border-radius: 10px;
    font-size: 16px;
    transition: border-color 0.3s ease;
  }
  input[type=text]:focus, input[type=email]:focus, input[type=password]:focus, input[type=file]:focus {
    border-color: #764ba2;
    outline: none;
  }
  button {
    background: #764ba2;
    color: white;
    font-weight: 700;
    padding: 12px 30px;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    font-size: 16px;
    box-shadow: 0 8px 15px rgba(118,75,162,0.3);
    transition: all 0.3s ease;
    margin-right: 10px;
  }
  button:hover {
    background: #4a148c;
    box-shadow: 0 15px 20px rgba(74,20,140,0.5);
    transform: translateY(-3px);
  }
  .error {
    color: #e74c3c;
    font-weight: 600;
    margin-bottom: 15px;
  }
  .top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
  }
  .top-bar a {
    padding: 10px 20px;
    background: #f8a5c2;
    border-radius: 50px;
    color: #fff;
    font-weight: 700;
    box-shadow: 0 5px 10px rgba(248,165,194,0.6);
    transition: background 0.3s ease;
  }
  .top-bar a:hover {
    background: #e14b8b;
  }
  .stats {
    font-weight: 700;
    font-size: 18px;
    margin-bottom: 25px;
    color: #764ba2;
  }
  .users-container {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    justify-content: center;
  }
  .user-card {
    background: #fafafa;
    width: 220px;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(118, 75, 162, 0.15);
    text-align: center;
    position: relative;
    transition: transform 0.3s ease;
    cursor: default;
  }
  .user-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 30px rgba(118, 75, 162, 0.35);
  }
  .user-card img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin-bottom: 15px;
    object-fit: cover;
    border: 4px solid #764ba2;
    box-shadow: 0 0 10px rgba(118, 75, 162, 0.5);
    cursor: pointer;
    transition: box-shadow 0.3s ease;
  }
  .user-card img:hover {
    box-shadow: 0 0 18px #f8a5c2;
  }
  .user-card p {
    font-size: 18px;
    font-weight: 600;
    margin: 10px 0 0 0;
  }
  .user-card small {
    color: #999;
  }
  .modal {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }
  .modal-content {
    background: #fff;
    padding: 30px 40px;
    border-radius: 20px;
    max-width: 400px;
    width: 100%;
    position: relative;
    box-shadow: 0 20px 40px rgba(118, 75, 162, 0.3);
    text-align: center;
  }
  .modal-content img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid #764ba2;
    margin-bottom: 20px;
  }
  .close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 30px;
    color: #764ba2;
    cursor: pointer;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
  }
  th, td {
    padding: 12px 15px;
    border-bottom: 1px solid #ddd;
    text-align: left;
  }
  th {
    background: #764ba2;
    color: white;
  }
  td img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #764ba2;
  }
  .action-buttons form {
    display: inline;
  }
</style>
</head>
<body>

<div class="container">
  <div class="top-bar">
    <h1>User Management System</h1>
    <?php if (isset($_SESSION['email'])): ?>
      <a href="?logout=1">Logout</a>
    <?php endif; ?>
  </div>

  <?php if (isset($error)): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <?php if (!isset($_SESSION['email'])): ?>
  <!-- Show login form if NOT logged in -->
  <form method="POST" style="max-width: 400px; margin-bottom: 40px;">
    <h2>Login</h2>
    <input type="email" name="email" placeholder="Email" required />
    <input type="password" name="password" placeholder="Password" required />
    <button type="submit" name="login">Login</button>
  </form>
  <?php endif; ?>

  <!-- Always show add/register user form, whether logged in or not -->
  <form id="registerForm" method="POST" enctype="multipart/form-data" style="max-width: 400px; margin-bottom: 40px;">
    <h2>Add New User</h2>
    <input type="text" name="name" placeholder="Full Name" required />
    <input type="email" name="email" placeholder="Email" required />
    <input type="password" name="password" placeholder="Password" required />
    <input type="file" name="picture" accept="image/*" />
    <button type="submit" name="register">Add User</button>
  </form>

  <?php if (isset($_SESSION['email'])): ?>
    <?php
      $totalUsers = count($usersXml->user);
      $currentUser = null;
      foreach ($usersXml->user as $user) {
          if ($user->email == $_SESSION['email']) {
              $currentUser = $user;
              break;
          }
      }
    ?>
    <p class="stats">Welcome, <?= htmlspecialchars($currentUser ? $currentUser->name : $_SESSION['email']) ?>! Total Users: <?= $totalUsers ?></p>

    <table>
      <thead>
        <tr>
          <th>Picture</th>
          <th>Name</th>
          <th>Email</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usersXml->user as $user): ?>
          <tr>
            <td><img src="<?= htmlspecialchars($user->picture) ?>" alt="User Picture" /></td>
            <td><?= htmlspecialchars($user->name) ?></td>
            <td><?= htmlspecialchars($user->email) ?></td>
            <td class="action-buttons">
              <!-- Edit button triggers modal -->
              <button onclick="openEditModal('<?= htmlspecialchars($user->id) ?>', '<?= htmlspecialchars($user->name) ?>', '<?= htmlspecialchars($user->email) ?>')">Edit</button>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                <input type="hidden" name="id" value="<?= htmlspecialchars($user->id) ?>" />
                <button type="submit" name="delete_user" style="background: #e74c3c;">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
      <div class="modal-content">
        <span class="close-btn" onclick="closeEditModal()">&times;</span>
        <h2>Edit User</h2>
        <form method="POST" enctype="multipart/form-data" id="editUserForm">
          <input type="hidden" name="id" id="editUserId" />
          <input type="text" name="name" id="editUserName" placeholder="Full Name" required />
          <input type="email" name="email" id="editUserEmail" placeholder="Email" required />
          <input type="file" name="picture" accept="image/*" />
          <button type="submit" name="edit_user">Save Changes</button>
        </form>
      </div>
    </div>

  <?php else: ?>
    <p>Please login to see users and edit them.</p>
  <?php endif; ?>
</div>

<script>
function openEditModal(id, name, email) {
  document.getElementById('editUserId').value = id;
  document.getElementById('editUserName').value = name;
  document.getElementById('editUserEmail').value = email;
  document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
  let modal = document.getElementById('editModal');
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>

</body>
</html>
