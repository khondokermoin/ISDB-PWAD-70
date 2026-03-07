<?php
function handle_shifts_create(): void {
  $d = get_json_body();

  $name = trim((string)($d['name'] ?? ''));
  $start = (string)($d['start_time'] ?? '');
  $end   = (string)($d['end_time'] ?? '');
  $grace = (int)($d['grace_minutes'] ?? 0);

  // Optional advanced fields (schema requires some of them, so we compute safe defaults)
  $winStart = (string)($d['checkin_window_start'] ?? '');
  $winEnd   = (string)($d['checkin_window_end'] ?? '');
  $earliest = (string)($d['checkout_earliest_time'] ?? '');
  $minDaily = isset($d['min_daily_minutes']) ? (int)$d['min_daily_minutes'] : null;

  if ($name === '') json_response(['error' => 'Shift name required'], 422);
  if ($start === '' || $end === '') json_response(['error' => 'Start & End required'], 422);

  // basic format validation: HH:MM:SS
  $isHms = fn($t) => is_string($t) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $t);

  if (!$isHms($start)) json_response(['error' => 'start_time must be HH:MM:SS'], 422);
  if (!$isHms($end))   json_response(['error' => 'end_time must be HH:MM:SS'], 422);

  if ($grace < 0) $grace = 0;

  $toMinutes = function(string $hms): int {
    [$hh, $mm, $ss] = array_map('intval', explode(':', $hms));
    return ($hh * 60) + $mm; // seconds ignored
  };

  $toHms = function(int $mins): string {
    $mins = $mins % (24 * 60);
    if ($mins < 0) $mins += 24 * 60;
    $hh = intdiv($mins, 60);
    $mm = $mins % 60;
    return sprintf('%02d:%02d:00', $hh, $mm);
  };

  // Compute shift duration (supports overnight)
  $sMin = $toMinutes($start);
  $eMin = $toMinutes($end);
  $eAdj = $eMin;
  if ($eAdj < $sMin) $eAdj += 24 * 60; // overnight
  $duration = max(0, $eAdj - $sMin);

  // Defaults
  if ($minDaily === null || $minDaily < 0) {
    $minDaily = $duration;
  }

  if ($winStart === '') {
    $winStart = $toHms($sMin - 60); // 1 hour before
  } else {
    if (!$isHms($winStart)) json_response(['error' => 'checkin_window_start must be HH:MM:SS'], 422);
  }

  if ($winEnd === '') {
    $winEnd = $toHms($sMin + 90); // 90 minutes after start
  } else {
    if (!$isHms($winEnd)) json_response(['error' => 'checkin_window_end must be HH:MM:SS'], 422);
  }

  if ($earliest !== '') {
    if (!$isHms($earliest)) json_response(['error' => 'checkout_earliest_time must be HH:MM:SS'], 422);
  } else {
    $earliest = null;
  }

  $pdo = db();
  $stmt = $pdo->prepare(
    'INSERT INTO shifts (name, start_time, end_time, grace_minutes, checkin_window_start, checkin_window_end, checkout_earliest_time, min_daily_minutes, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
  );
  $stmt->execute([$name, $start, $end, $grace, $winStart, $winEnd, $earliest, $minDaily, now_dt()]);

  $actor = $_SESSION['user']['id'] ?? null;
  audit_log($actor ? (int)$actor : null, 'CREATE_SHIFT', ['shift_id' => (int)$pdo->lastInsertId(), 'name' => $name]);

  json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}
