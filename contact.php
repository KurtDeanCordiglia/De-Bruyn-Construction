<?php
// Emails quote requests from the website form to De Bruyn Developments.
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Africa/Johannesburg');

$TO   = 'debruyndevelopments@gmail.com';
$FROM = 'website@debruyndevelopments.co.za';

function reply($ok, $msg, $code = 200) {
  http_response_code($code);
  echo json_encode(['ok' => $ok, 'message' => $msg]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(false, 'Method not allowed', 405);

// Spam traps: a hidden field people never fill in, and a minimum time on the page
if (!empty($_POST['website'])) reply(true, 'Thanks');
$started = isset($_POST['started']) ? (int)$_POST['started'] : 0;
if ($started > 0 && (time() * 1000 - $started) < 3000) reply(true, 'Thanks');

function field($key, $max) {
  $v = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
  return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max);
}
function oneline($v) { return preg_replace('/[\r\n]+/', ' ', $v); }

$type   = oneline(field('type', 40));
$name   = oneline(field('name', 100));
$phone  = oneline(field('phone', 40));
$suburb = oneline(field('suburb', 100));
$budget = oneline(field('budget', 40));
$msg    = field('msg', 3000);

if ($name === '' || $phone === '') reply(false, 'Please add your name and phone number.', 422);

$subject = 'Website quote request: ' . ($type ?: 'Project') . ' - ' . $name;
$body = "New quote request from debruyndevelopments.co.za\n\n"
      . "Project type: " . ($type ?: '-') . "\n"
      . "Name:         " . $name . "\n"
      . "Phone:        " . $phone . "\n"
      . "Site suburb:  " . ($suburb ?: '-') . "\n"
      . "Budget:       " . ($budget ?: '-') . "\n\n"
      . "What they want built:\n" . ($msg ?: '-') . "\n\n"
      . "Sent " . date('j M Y, H:i') . "\n";

$headers = [
  'From: De Bruyn Website <' . $FROM . '>',
  'Reply-To: ' . $TO,
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
  'Content-Transfer-Encoding: 8bit',
];

$sent = mail($TO, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers), '-f' . $FROM);

if ($sent) reply(true, 'Thanks');
reply(false, 'The email could not be sent.', 500);
