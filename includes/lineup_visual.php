<?php
declare(strict_types=1);

/**
 * Chuẩn hóa mã vị trí sân (FieldPosition) để tra bản đồ tọa độ.
 */
function lineupNormalizeFieldKey(?string $fieldPosition): string
{
    if ($fieldPosition === null) {
        return '';
    }
    $s = strtoupper(trim($fieldPosition));
    $s = preg_replace('/\s+/', '', $s) ?? '';
    return $s;
}

/**
 * Tọa độ % trên sân (top, left): top nhỏ = gần khung đối phương, GK ở cuối sân (top lớn).
 *
 * @return array{t: float, l: float}|null
 */
function lineupSlotForPosition(?string $fieldPosition): ?array
{
    $k = lineupNormalizeFieldKey($fieldPosition);
    if ($k === '') {
        return null;
    }

    static $map = [
        'ST' => ['t' => 10.0, 'l' => 50.0],
        'CF' => ['t' => 10.0, 'l' => 50.0],
        'LW' => ['t' => 16.0, 'l' => 18.0],
        'RW' => ['t' => 16.0, 'l' => 82.0],
        'LM' => ['t' => 28.0, 'l' => 12.0],
        'RM' => ['t' => 28.0, 'l' => 88.0],
        'LAM' => ['t' => 24.0, 'l' => 28.0],
        'RAM' => ['t' => 24.0, 'l' => 72.0],
        'CAM' => ['t' => 32.0, 'l' => 50.0],
        'LCM' => ['t' => 40.0, 'l' => 32.0],
        'RCM' => ['t' => 40.0, 'l' => 68.0],
        'CM' => ['t' => 40.0, 'l' => 50.0],
        'LCDM' => ['t' => 48.0, 'l' => 35.0],
        'RCDM' => ['t' => 48.0, 'l' => 65.0],
        'CDM' => ['t' => 50.0, 'l' => 50.0],
        'DM' => ['t' => 50.0, 'l' => 50.0],
        'LWB' => ['t' => 58.0, 'l' => 10.0],
        'RWB' => ['t' => 58.0, 'l' => 90.0],
        'LB' => ['t' => 68.0, 'l' => 12.0],
        'RB' => ['t' => 68.0, 'l' => 88.0],
        'LCB' => ['t' => 72.0, 'l' => 35.0],
        'RCB' => ['t' => 72.0, 'l' => 65.0],
        'CB' => ['t' => 72.0, 'l' => 50.0],
        'GK' => ['t' => 90.0, 'l' => 50.0],
    ];

    return $map[$k] ?? null;
}

/**
 * Gán tọa độ cho từng cầu thủ đá chính (đá chính mới lên sơ đồ).
 *
 * @param list<array<string, mixed>> $starters Mỗi phần tử có FieldPosition (mixed|null)
 * @return list<array<string, mixed>>
 */
function lineupAssignPitchCoordinates(array $starters): array
{
    $placed = [];
    $unplaced = [];
    $used = [];

    foreach ($starters as $s) {
        $fp = isset($s['FieldPosition']) && is_string($s['FieldPosition']) ? $s['FieldPosition'] : null;
        $slot = lineupSlotForPosition($fp);
        if ($slot === null) {
            $unplaced[] = $s;
            continue;
        }
        $key = (string) round($slot['t']) . '_' . (string) round($slot['l']);
        $dup = $used[$key] ?? 0;
        $used[$key] = $dup + 1;
        $t = $slot['t'];
        $l = min(94.0, $slot['l'] + $dup * 6.0);
        $placed[] = array_merge($s, ['pitchT' => $t, 'pitchL' => $l]);
    }

    $n = count($unplaced);
    foreach ($unplaced as $i => $s) {
        $l = $n <= 1 ? 50.0 : 18.0 + ($i / (float) max(1, $n - 1)) * 64.0;
        $placed[] = array_merge($s, ['pitchT' => 56.0, 'pitchL' => $l]);
    }

    return $placed;
}

/**
 * Vòng tròn hiển thị số áo (không dùng ảnh).
 *
 * @param mixed $shirtNumber
 * @param string $teamSide 'home' | 'away' | ''
 */
function lineupPlayerShirtBadgeHtml($shirtNumber, string $fullName, string $teamSide = ''): string
{
    $label = '—';
    if ($shirtNumber !== null && $shirtNumber !== '') {
        $label = (string) (int) $shirtNumber;
    }
    $classes = 'admin-lineup-shirt';
    if ($teamSide === 'home') {
        $classes .= ' admin-lineup-shirt--home';
    } elseif ($teamSide === 'away') {
        $classes .= ' admin-lineup-shirt--away';
    }
    $title = htmlspecialchars($fullName . ($label !== '—' ? ' · #' . $label : ''), ENT_QUOTES, 'UTF-8');
    $text = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    return '<span class="' . $classes . '" title="' . $title . '">' . $text . '</span>';
}

/**
 * Chia đội hình thành sân (đá chính) + dự bị theo đội nhà / khách.
 *
 * @param list<array<string, mixed>> $lineupRows Hàng từ MatchLineup JOIN Player
 * @return array{0: list, 1: list, 2: list, 3: list} pitchHome, benchHome, pitchAway, benchAway
 */
function lineupComputePitchSides(array $lineupRows): array
{
    $split = static function (array $rows, int $wantHome): array {
        $starters = [];
        $bench = [];
        foreach ($rows as $lr) {
            if ((int) ($lr['IsHomeTeam'] ?? 0) !== $wantHome) {
                continue;
            }
            if ((int) ($lr['IsStarter'] ?? 0) === 1) {
                $starters[] = $lr;
            } else {
                $bench[] = $lr;
            }
        }

        return [lineupAssignPitchCoordinates($starters), $bench];
    };

    [$pitchHome, $benchHome] = $split($lineupRows, 1);
    [$pitchAway, $benchAway] = $split($lineupRows, 0);

    return [$pitchHome, $benchHome, $pitchAway, $benchAway];
}

/**
 * HTML khối sân + dự bị (dùng admin & khách).
 *
 * @param list<array<string, mixed>> $pitchStarters
 * @param list<array<string, mixed>> $bench
 */
function lineupRenderPitchMarkup(array $pitchStarters, array $bench, string $teamSide, string $ariaLabel): string
{
    $side = $teamSide === 'away' ? 'away' : 'home';
    if ($pitchStarters === [] && $bench === []) {
        return '<p class="text-muted small mb-0 p-2">Chưa có đội hình đăng ký cho đội này trong trận.</p>';
    }

    ob_start();
    ?>
    <div class="admin-lineup-pitch-wrap mx-auto">
        <div class="admin-lineup-pitch" role="img" aria-label="<?= htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') ?>">
            <div class="admin-lineup-pitch-field" aria-hidden="true"></div>
            <div class="admin-lineup-pitch-lines" aria-hidden="true">
                <div class="admin-lineup-pen admin-lineup-pen--top"></div>
                <div class="admin-lineup-pen admin-lineup-pen--bot"></div>
            </div>
            <?php foreach ($pitchStarters as $pl): ?>
                <?php
                $fp = $pl['FieldPosition'] !== null && $pl['FieldPosition'] !== '' ? (string) $pl['FieldPosition'] : '—';
                $t = (float) $pl['pitchT'];
                $l = (float) $pl['pitchL'];
                ?>
                <div class="admin-lineup-node admin-lineup-node--<?= htmlspecialchars($side, ENT_QUOTES, 'UTF-8') ?>" style="top: <?= $t ?>%;left: <?= $l ?>%;">
                    <span class="admin-lineup-node-pos"><?= htmlspecialchars($fp, ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="admin-lineup-node-figure">
                        <?= lineupPlayerShirtBadgeHtml($pl['ShirtNumber'] ?? null, (string) $pl['FullName'], $side) ?>
                    </div>
                    <span class="admin-lineup-node-name"><?= htmlspecialchars((string) $pl['FullName'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if (count($bench) > 0): ?>
        <p class="small text-muted mb-1 mt-2 fw-semibold">Dự bị</p>
        <div class="d-flex flex-wrap gap-2 admin-lineup-bench">
            <?php foreach ($bench as $pl): ?>
                <div class="admin-lineup-bench-item admin-lineup-bench-item--<?= htmlspecialchars($side, ENT_QUOTES, 'UTF-8') ?> text-center">
                    <?= lineupPlayerShirtBadgeHtml($pl['ShirtNumber'] ?? null, (string) $pl['FullName'], $side) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php

    return (string) ob_get_clean();
}
