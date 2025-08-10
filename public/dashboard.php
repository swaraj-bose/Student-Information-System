<?php
// public/dashboard.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/user.php';

// HTML-escape helper
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role     = $_SESSION['role'];

// Fetch all questions with author info and reaction counts
$sql = "
    SELECT
        q.id,
        q.content,
        q.user_id,
        q.created_at,
        u.username,
        COALESCE(SUM(r.type = 'like'    AND r.target_type = 'question' AND r.target_id = q.id), 0) AS likes,
        COALESCE(SUM(r.type = 'dislike' AND r.target_type = 'question' AND r.target_id = q.id), 0) AS dislikes
    FROM questions q
    JOIN users u
      ON q.user_id = u.id
    LEFT JOIN reactions r
      ON r.target_type = 'question'
     AND r.target_id = q.id
    GROUP BY q.id, q.content, q.user_id, q.created_at, u.username
    ORDER BY q.created_at DESC
";
$stmt     = $pdo->query($sql);
$questions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard – StudentIS</title>
  <link rel="stylesheet" href="css/styled.css">
</head>
<body>
  <header>
    <div>
      <span class="avatar"><?= h(strtoupper($username[0])) ?></span>
      &nbsp;Welcome, <strong><?= h($username) ?></strong> (<?= h($role) ?>)
    </div>
    <div>
      <a href="../src/logout.php?action=logout" class="logout-button">Log out</a>
    </div>
  </header>

  <hr>

  <?php if ($role === 'student'): ?>
    <section>
      <h2>Post a Question</h2>
      <form method="post" action="../src/post.php">
        <textarea
          name="content"
          rows="3"
          style="width:100%;"
          required
          placeholder="Type your question here…"></textarea>
        <br>
        <button type="submit" name="action" value="create_question">
          Submit Question
        </button>
      </form>
    </section>
    <hr>
  <?php endif; ?>

  <section>
    <h2>All Questions</h2>
    <?php foreach ($questions as $q): ?>
      <div class="question">
        <p><?= nl2br(h($q['content'])) ?></p>
        <div class="meta">
          Asked by <strong><?= h($q['username']) ?></strong>
          at <?= h($q['created_at']) ?>
        </div>
        <div class="actions">
          <!-- Like / Dislike Question -->
          <form class="inline" method="post" action="../src/reaction.php">
            <input type="hidden" name="target_type" value="question">
            <input type="hidden" name="target_id"   value="<?= h($q['id']) ?>">
            <button type="submit" name="type" value="like">
              👍 <?= h($q['likes']) ?>
            </button>
          </form>
          <form class="inline" method="post" action="../src/reaction.php">
            <input type="hidden" name="target_type" value="question">
            <input type="hidden" name="target_id"   value="<?= h($q['id']) ?>">
            <button type="submit" name="type" value="dislike">
              👎 <?= h($q['dislikes']) ?>
            </button>
          </form>

          <?php if ($q['user_id'] == $userId): ?>
            <a
              href="../src/post.php?action=delete_question&id=<?= h($q['id']) ?>"
              onclick="return confirm('Delete this question?')">
              🗑️ Delete
            </a>
          <?php endif; ?>
        </div>

        <?php
          // Fetch answers for this question
          $ansSql = "
            SELECT
              a.id,
              a.content,
              a.user_id,
              a.created_at,
              u.username,
              COALESCE(SUM(r.type = 'like'    AND r.target_type = 'answer' AND r.target_id = a.id), 0) AS likes,
              COALESCE(SUM(r.type = 'dislike' AND r.target_type = 'answer' AND r.target_id = a.id), 0) AS dislikes
            FROM answers a
            JOIN users u
              ON a.user_id = u.id
            LEFT JOIN reactions r
              ON r.target_type = 'answer'
             AND r.target_id = a.id
            WHERE a.question_id = ?
            GROUP BY a.id, a.content, a.user_id, a.created_at, u.username
            ORDER BY a.created_at ASC
          ";
          $ansStmt = $pdo->prepare($ansSql);
          $ansStmt->execute([$q['id']]);
          $answers = $ansStmt->fetchAll();
        ?>

        <?php foreach ($answers as $a): ?>
          <div class="answer" style="margin-left:2rem;">
            <p><?= nl2br(h($a['content'])) ?></p>
            <div class="meta">
              Answered by <strong><?= h($a['username']) ?></strong>
              at <?= h($a['created_at']) ?>
            </div>
            <div class="actions">
              <!-- Like / Dislike Answer -->
              <form class="inline" method="post" action="../src/reaction.php">
                <input type="hidden" name="target_type" value="answer">
                <input type="hidden" name="target_id"   value="<?= h($a['id']) ?>">
                <button type="submit" name="type" value="like">
                  👍 <?= h($a['likes']) ?>
                </button>
              </form>
              <form class="inline" method="post" action="../src/reaction.php">
                <input type="hidden" name="target_type" value="answer">
                <input type="hidden" name="target_id"   value="<?= h($a['id']) ?>">
                <button type="submit" name="type" value="dislike">
                  👎 <?= h($a['dislikes']) ?>
                </button>
              </form>

              <!-- Delete Answer (author only) -->
              <?php if ($a['user_id'] === $userId): ?>
                <form
                  class="inline"
                  method="post"
                  action="../src/answer.php"
                  onsubmit="return confirm('Delete this answer?');"
                >
                  <input type="hidden" name="action" value="delete_answer">
                  <input type="hidden" name="id" value="<?= h($a['id']) ?>">
                  <button type="submit">🗑️ Delete</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Add an answer -->
        <form
          method="post"
          action="../src/answer.php"
          style="margin-left:2rem;">
          <input type="hidden" name="question_id" value="<?= h($q['id']) ?>">
          <textarea
            name="content"
            rows="2"
            style="width:90%;"
            required
            placeholder="Your answer…"></textarea>
          <br>
          <button type="submit" name="action" value="create_answer">
            <?= $role === 'teacher' ? 'Answer as Teacher' : 'Submit Answer' ?>
          </button>
        </form>
      </div>
    <?php endforeach; ?>
  </section>

  <script src="js/scriptd.js" defer></script>
</body>
</html>

