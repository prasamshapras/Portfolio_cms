<?php
require "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

/*
  Priority:
  1) index.php?u=username
  2) logged-in user
  3) first admin
*/

$username = trim($_GET["u"] ?? "");
if ($username === "" && !empty($_SESSION["username"])) {
  $username = (string)$_SESSION["username"];
}

if ($username === "") {
  $stmt = $conn->prepare("SELECT username FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1");
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $username = $row["username"] ?? "";
}

/* ✅ fetch display_name too */
$stmt = $conn->prepare("SELECT id, username, display_name FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { http_response_code(404); exit("Portfolio not found."); }
$userId = (int)$user["id"];

/* ✅ display name fallback */
$displayName = trim((string)($user["display_name"] ?? ""));
if ($displayName === "") $displayName = (string)$user["username"];
$initial = strtoupper(mb_substr($displayName ?: "U", 0, 1, "UTF-8"));

function getContent(mysqli $conn, int $userId, string $section): string {
  $stmt = $conn->prepare("SELECT content FROM portfolio_content WHERE user_id=? AND section=? LIMIT 1");
  $stmt->bind_param("is", $userId, $section);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  return (string)($row["content"] ?? "");
}

function getContact(mysqli $conn, int $userId): array {
  /* phone may or may not exist in your table, so we safely select common fields */
  $stmt = $conn->prepare("
    SELECT email, phone, linkedin, location, footer_github, footer_linkedin
    FROM portfolio_contact
    WHERE user_id=? LIMIT 1
  ");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  return $row ?: [];
}

$hero   = getContent($conn, $userId, "hero");
$about  = getContent($conn, $userId, "about");
$skills = getContent($conn, $userId, "skills");
$contact = getContact($conn, $userId);

/* ✅ Projects */
$projects = [];
$stmt = $conn->prepare("
  SELECT title, description, tech, live_url, github_url, created_at
  FROM portfolio_projects
  WHERE user_id=?
  ORDER BY created_at DESC, id DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $projects[] = $row;
$stmt->close();

/* ✅ Education */
$education = [];
$stmt = $conn->prepare("
  SELECT institution, degree, field, start_year, end_year, grade, description
  FROM education
  WHERE user_id=?
  ORDER BY sort_order ASC, id DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $education[] = $row;
$stmt->close();

/* ✅ Experience */
$experience = [];
$stmt = $conn->prepare("
  SELECT company, title, location, employment_type, start_date, end_date, is_current, description
  FROM experience
  WHERE user_id=?
  ORDER BY sort_order ASC, id DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $experience[] = $row;
$stmt->close();

$selfUrl = "index.php?u=" . urlencode($username);

function techToArray(string $tech): array {
  $tech = trim($tech);
  if ($tech === "") return [];
  $tech = str_replace(["|", "/", ";"], ",", $tech);
  $parts = array_map("trim", explode(",", $tech));
  return array_values(array_filter($parts, fn($x) => $x !== ""));
}

/* ✅ profile image url with cache bust so it always shows latest */
$profileImgUrl = "profile_image.php?u=" . urlencode($username) . "&v=" . time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($displayName); ?> • Portfolio</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topbar">
  <div class="wrap topbar-inner">
    <a class="brand" href="<?php echo htmlspecialchars($selfUrl); ?>#home">
      <span class="brand-dot"></span>
      <span class="brand-text">Portfolio</span>
    </a>

    <nav class="nav">
      <a href="<?php echo htmlspecialchars($selfUrl); ?>#home">Home</a>
      <a href="<?php echo htmlspecialchars($selfUrl); ?>#skills">Skills</a>
      <a href="<?php echo htmlspecialchars($selfUrl); ?>#projects">Projects</a>
      <a href="<?php echo htmlspecialchars($selfUrl); ?>#experience">Experience</a>
      <a href="<?php echo htmlspecialchars($selfUrl); ?>#education">Education</a>
      <a href="<?php echo htmlspecialchars($selfUrl); ?>#contact">Contact</a>
    </nav>

    <div class="topbar-right">
      <?php if (!empty($_SESSION["user_id"])): ?>
        <a class="pill" href="dashboard.php">Dashboard</a>
      <?php else: ?>
        <a class="pill" href="login.php">Login</a>
      <?php endif; ?>

      <button class="pill pill-ghost" id="themeToggle" type="button">
        <span class="theme-label">Dark</span>
      </button>
    </div>
  </div>
</header>

<main>

  <!-- HERO -->
  <section id="home" class="hero">
    <div class="wrap hero-grid">

      <div class="hero-left">
        <div class="kicker">Web Developer</div>

        <h1 class="title">
          <?php echo nl2br(htmlspecialchars($hero ?: "Hey There,\nI’m " . $displayName)); ?>
        </h1>

        <p class="subtitle">
          <?php echo nl2br(htmlspecialchars($about ?: "I build clean and reliable web applications using PHP and MySQL.")); ?>
        </p>

        <div class="cta-row">
          <a class="btnx primary" href="<?php echo htmlspecialchars($selfUrl); ?>#projects">View Projects</a>
          <a class="btnx" href="<?php echo htmlspecialchars($selfUrl); ?>#contact">Contact</a>
        </div>

        <div class="stats">
          <div class="stat">
          </div>
    
        </div>
      </div>

      <div class="hero-right">
        <div class="profile-card">
          <div class="profile-media" id="avatarBox">

            <img
              class="profile-img"
              src="<?php echo htmlspecialchars($profileImgUrl); ?>"
              alt="Profile photo"
              loading="lazy"
              onerror="(function(img){
                var box = document.getElementById('avatarBox');
                if(box){ box.classList.add('no-photo'); }
                img.remove();
              })(this)"
            />

            <div class="profile-fallback"><?php echo htmlspecialchars($initial); ?></div>
          </div>

          <div class="profile-meta">
            <div class="profile-name"><?php echo htmlspecialchars($displayName); ?></div>
            <div class="profile-sub">Personal Portfolio</div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- SKILLS -->
  <section id="skills" class="section">
    <div class="wrap">
      <div class="section-head">
        <h2>Skills</h2>
      </div>
      <div class="panel">
        <?php echo nl2br(htmlspecialchars($skills ?: "Add your skills from dashboard → Manage Content/Skills.")); ?>
      </div>
    </div>
  </section>

  <!-- EXPERIENCE -->
  <section id="experience" class="section section-alt">
    <div class="wrap">
      <div class="section-head">
        <h2>Experience</h2>
      </div>

      <?php if (count($experience) === 0): ?>
        <div class="panel">
          <?php echo nl2br(htmlspecialchars("Add your experience from dashboard → Manage Experience.")); ?>
        </div>
      <?php else: ?>
        <div class="timeline-grid">
          <?php foreach ($experience as $e): ?>
            <?php
              $company = (string)($e["company"] ?? "");
              $title   = (string)($e["title"] ?? "");
              $loc     = trim((string)($e["location"] ?? ""));
              $type    = trim((string)($e["employment_type"] ?? ""));
              $start   = trim((string)($e["start_date"] ?? ""));
              $end     = trim((string)($e["end_date"] ?? ""));
              $cur     = (int)($e["is_current"] ?? 0) === 1;
              $desc    = (string)($e["description"] ?? "");
              $range   = trim($start . " - " . ($cur ? "Present" : $end));
              $metaParts = array_values(array_filter([$company, $type, $loc]));
            ?>
            <article class="timeline-card">
              <div class="timeline-head">
                <h3 class="timeline-title"><?php echo htmlspecialchars($title); ?></h3>
                <?php if ($range !== "-"): ?>
                  <div class="timeline-date"><?php echo htmlspecialchars($range); ?></div>
                <?php endif; ?>
              </div>

              <?php if (count($metaParts) > 0): ?>
                <div class="timeline-meta">
                  <?php echo htmlspecialchars(implode(" • ", $metaParts)); ?>
                </div>
              <?php endif; ?>

              <?php if (trim($desc) !== ""): ?>
                <div class="timeline-desc">
                  <?php echo nl2br(htmlspecialchars($desc)); ?>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- PROJECTS -->
  <section id="projects" class="section section-alt">
    <div class="wrap">
      <div class="section-head">
        <h2>Projects</h2>
      </div>

      <div class="project-grid">
        <?php if (count($projects) === 0): ?>
          <div class="project">
            <h3>No projects yet</h3>
            <p class="muted">Add projects from your dashboard.</p>
          </div>
        <?php endif; ?>

        <?php foreach ($projects as $p): ?>
          <?php
            $techArr = techToArray((string)($p["tech"] ?? ""));
            $live = trim((string)($p["live_url"] ?? ""));
            $git  = trim((string)($p["github_url"] ?? ""));
          ?>
          <article class="project">
            <div class="project-head">
              <h3 class="project-title"><?php echo htmlspecialchars($p["title"] ?? "Untitled"); ?></h3>
            </div>

            <p class="project-desc"><?php echo nl2br(htmlspecialchars($p["description"] ?? "")); ?></p>

            <?php if (count($techArr) > 0): ?>
              <div class="tags">
                <?php foreach ($techArr as $t): ?>
                  <span class="tag"><?php echo htmlspecialchars($t); ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if ($live !== "" || $git !== ""): ?>
              <div class="project-links">
                <?php if ($live !== ""): ?>
                  <a class="plink primary" target="_blank" rel="noopener" href="<?php echo htmlspecialchars($live); ?>">Live</a>
                <?php endif; ?>
                <?php if ($git !== ""): ?>
                  <a class="plink" target="_blank" rel="noopener" href="<?php echo htmlspecialchars($git); ?>">GitHub</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

  <!-- EDUCATION -->
  <section id="education" class="section">
    <div class="wrap">
      <div class="section-head">
        <h2>Education</h2>
      </div>

      <?php if (count($education) === 0): ?>
        <div class="panel">
          <?php echo nl2br(htmlspecialchars("Add your education from dashboard → Manage Education.")); ?>
        </div>
      <?php else: ?>
        <div class="timeline-grid">
          <?php foreach ($education as $ed): ?>
            <?php
              $institution = (string)($ed["institution"] ?? "");
              $degree      = (string)($ed["degree"] ?? "");
              $field       = trim((string)($ed["field"] ?? ""));
              $startY      = trim((string)($ed["start_year"] ?? ""));
              $endY        = trim((string)($ed["end_year"] ?? ""));
              $grade       = trim((string)($ed["grade"] ?? ""));
              $desc        = (string)($ed["description"] ?? "");
              $range       = trim($startY . " - " . $endY);
              $metaParts   = array_values(array_filter([$institution, $field, $grade]));
            ?>
            <article class="timeline-card">
              <div class="timeline-head">
                <h3 class="timeline-title"><?php echo htmlspecialchars($degree); ?></h3>
                <?php if ($range !== "-"): ?>
                  <div class="timeline-date"><?php echo htmlspecialchars($range); ?></div>
                <?php endif; ?>
              </div>

              <?php if (count($metaParts) > 0): ?>
                <div class="timeline-meta">
                  <?php echo htmlspecialchars(implode(" • ", $metaParts)); ?>
                </div>
              <?php endif; ?>

              <?php if (trim($desc) !== ""): ?>
                <div class="timeline-desc">
                  <?php echo nl2br(htmlspecialchars($desc)); ?>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- CONTACT -->
  <section id="contact" class="section">
    <div class="wrap">
      <div class="section-head">
        <h2>Contact</h2>
      </div>

      <div class="contact-block">
        <div class="contact-row">
          <div class="contact-label">Email</div>
          <div class="contact-value"><?php echo htmlspecialchars($contact["email"] ?? ""); ?></div>
        </div>

        <?php if (!empty($contact["phone"])): ?>
        <div class="contact-row">
          <div class="contact-label">Phone</div>
          <div class="contact-value"><?php echo htmlspecialchars($contact["phone"]); ?></div>
        </div>
        <?php endif; ?>

        <div class="contact-row">
          <div class="contact-label">LinkedIn</div>
          <?php $ln = trim((string)($contact["linkedin"] ?? "")); ?>
          <?php if ($ln !== ""): ?>
            <a class="contact-value link" target="_blank" rel="noopener" href="<?php echo htmlspecialchars($ln); ?>">
              <?php echo htmlspecialchars($ln); ?>
            </a>
          <?php else: ?>
            <div class="contact-value"></div>
          <?php endif; ?>
        </div>

        <div class="contact-row">
          <div class="contact-label">Location</div>
          <div class="contact-value"><?php echo htmlspecialchars($contact["location"] ?? ""); ?></div>
        </div>

        <div class="footer">
          © <?php echo date("Y"); ?> • <?php echo htmlspecialchars($displayName); ?>
          <?php if (!empty($contact["footer_github"])): ?>
            • <a class="link" target="_blank" rel="noopener" href="<?php echo htmlspecialchars($contact["footer_github"]); ?>">GitHub</a>
          <?php endif; ?>
          <?php if (!empty($contact["footer_linkedin"])): ?>
            • <a class="link" target="_blank" rel="noopener" href="<?php echo htmlspecialchars($contact["footer_linkedin"]); ?>">LinkedIn</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

</main>

<script src="theme.js"></script>
<script src="script.js"></script>
</body>
</html>
