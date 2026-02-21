<?php
require "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

echo "<h2>Update Users: username -> email, keep display_name</h2>";

$users = [
  // old_username => [new_email, display_name, plain_password]
  "ram"   => ["ram12345@gmail.com",   "Ram",   "Ram@12345"],
  "shyam" => ["shyam12345@gmail.com", "Shyam", "Shyam@12345"],
  "hari"  => ["hari12345@gmail.com",  "Hari",  "Hari@12345"],
  "gita"  => ["gita12345@gmail.com",  "Gita",  "Gita@12345"],
  "rita"  => ["rita12345@gmail.com",  "Rita",  "Rita@12345"],
];

$findByUsername = $conn->prepare("
  SELECT id FROM users
  WHERE username = ?
  LIMIT 1
");

$checkEmailExists = $conn->prepare("
  SELECT id FROM users
  WHERE username = ?
  LIMIT 1
");

$update = $conn->prepare("
  UPDATE users
  SET username = ?, display_name = ?, password_hash = ?
  WHERE id = ?
");

foreach ($users as $oldUsername => [$newEmail, $displayName, $plainPw]) {

  // find old user
  $findByUsername->bind_param("s", $oldUsername);
  $findByUsername->execute();
  $findByUsername->bind_result($id);
  $found = $findByUsername->fetch();
  $findByUsername->free_result();

  if (!$found) {
    echo "❌ Missing user: <b>" . htmlspecialchars($oldUsername) . "</b><br>";
    continue;
  }

  // avoid duplicate email username
  $checkEmailExists->bind_param("s", $newEmail);
  $checkEmailExists->execute();
  $checkEmailExists->bind_result($existingId);
  $emailTaken = $checkEmailExists->fetch();
  $checkEmailExists->free_result();

  if ($emailTaken && (int)$existingId !== (int)$id) {
    echo "⚠️ Email already used: <b>" . htmlspecialchars($newEmail) . "</b> (skipped)<br>";
    continue;
  }

  $hash = password_hash($plainPw, PASSWORD_DEFAULT);

  $update->bind_param("sssi", $newEmail, $displayName, $hash, $id);
  $update->execute();

  echo "✅ Updated <b>" . htmlspecialchars($oldUsername) . "</b> → "
     . "username: <b>" . htmlspecialchars($newEmail) . "</b>, "
     . "display_name: <b>" . htmlspecialchars($displayName) . "</b>, "
     . "pw: <b>" . htmlspecialchars($plainPw) . "</b><br>";
}

$findByUsername->close();
$checkEmailExists->close();
$update->close();

echo "<hr>DONE. Now delete <code>update_user_emails.php</code> for security.";