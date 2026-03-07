<?php
function handle_shifts_update(int $id): void {
  $d = get_json_body();

  $pdo = db();
  $cur = $pdo->prepare('SELECT * FROM shifts WHERE id = ? LIMIT 1');
  $cur->execute([$id]);
  $existing = $cur->fetch();
  if (!$existing) json_response(['error' => 'Shift not found'], 404);

  $isHms = fn($t) => is_string($t) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $t);

  $toMinutes = function(string $hms): int {
    [$hh, $mm, $ss] = array_map('intval', explode(':', $hms));
    return ($hh * 60) + $mm;
  };

  $toHms = function(int $mins): string {
    $mins = $mins % (24 * 60);
    if ($mins < 0) $mins += 24 * 60;
    $hh = intdiv($mins, 60);
    $mm = $mins % 60;
    return sprintf('%02d:%02d:00', $hh, $mm);
  };

  // Determine (possibly new) start/end for derived defaults
  $newStart = array_key_exists('start_time', $d) ? (string)$d['start_time'] : (string)$existing['start_time'];
  $newEnd   = array_key_exists('end_time', $d)   ? (string)$d['end_time']   : (string)$existing['end_time'];

  if ($newStart === '' || !$isHms($newStart)) json_response(['error' => 'start_time must be HH:MM:SS'], 422);
  if ($newEnd === ''   || !$isHms($newEnd))   json_response(['error' => 'end_time must be HH:MM:SS'], 422);

  $sMin = $toMinutes($newStart);
  $eMin = $toMinutes($newEnd);
  $eAdj = $eMin;
  if ($eAdj < $sMin) $eAdj += 24 * 60;
  $duration = max(0, $eAdj - $sMin);

  $set = [];
  $vals = [];

  // name
  if (array_key_exists('name', $d)) {
    $val = trim((string)$d['name']);
    if ($val === '') json_response(['error' => 'Name required'], 422);
    $set[] = 'name = ?';
    $vals[] = $val;
  }

  // start/end/grace
  if (array_key_exists('start_time', $d)) {
    $set[] = 'start_time = ?';
    $vals[] = $newStart;
  }
  if (array_key_exists('end_time', $d)) {
    $set[] = 'end_time = ?';
    $vals[] = $newEnd;
  }
  if (array_key_exists('grace_minutes', $d)) {
    $val = (int)$d['grace_minutes'];
    if ($val < 0) $val = 0;
    $set[] = 'grace_minutes = ?';
    $vals[] = $val;
  }

  // Advanced fields: allow explicit update, otherwise auto-adjust when start_time changes.
  $startChanged = array_key_exists('start_time', $d);
  $endChanged = array_key_exists('end_time', $d);

  // checkin_window_start
  if (array_key_exists('checkin_window_start', $d)) {
    $val = (string)$d['checkin_window_start'];
    if ($val === '') {
      // cannot be NULL in schema; so compute default
      $val = $toHms($sMin - 60);
    }
    if (!$isHms($val)) json_response(['error' => 'checkin_window_start must be HH:MM:SS'], 422);
    $set[] = 'checkin_window_start = ?';
    $vals[] = $val;
  } elseif ($startChanged) {
    $set[] = 'checkin_window_start = ?';
    $vals[] = $toHms($sMin - 60);
  }

  // checkin_window_end
  if (array_key_exists('checkin_window_end', $d)) {
    $val = (string)$d['checkin_window_end'];
    if ($val === '') {
      $val = $toHms($sMin + 90);
    }
    if (!$isHms($val)) json_response(['error' => 'checkin_window_end must be HH:MM:SS'], 422);
    $set[] = 'checkin_window_end = ?';
    $vals[] = $val;
  } elseif ($startChanged) {
    $set[] = 'checkin_window_end = ?';
    $vals[] = $toHms($sMin + 90);
  }

  // checkout_earliest_time (nullable)
  if (array_key_exists('checkout_earliest_time', $d)) {
    $val = trim((string)$d['checkout_earliest_time']);
    if ($val === '') {
      $set[] = 'checkout_earliest_time = NULL';
    } else {
      if (!$isHms($val)) json_response(['error' => 'checkout_earliest_time must be HH:MM:SS'], 422);
      $set[] = 'checkout_earliest_time = ?';
      $vals[] = $val;
    }
  }

  // min_daily_minutes
  if (array_key_exists('min_daily_minutes', $d)) {
    $val = (int)$d['min_daily_minutes'];
    if ($val < 0) $val = 0;
    $set[] = 'min_daily_minutes = ?';
    $vals[] = $val;
  } elseif ($startChanged || $endChanged) {
    // Keep it aligned with the new shift duration
    $set[] = 'min_daily_minutes = ?';
    $vals[] = $duration;
  }

  if (!$set) json_response(['error' => 'No fields to update'], 422);

  $vals[] = $id;

  $stmt = $pdo->prepare('UPDATE shifts SET '.implode(', ', $set).' WHERE id = ?');
  $stmt->execute($vals);

  $actor = $_SESSION['user']['id'] ?? null;
  audit_log($actor ? (int)$actor : null, 'UPDATE_SHIFT', ['shift_id' => $id, 'fields' => array_keys($d)]);

  json_response(['ok' => true]);
}
