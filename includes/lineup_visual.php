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
        'GK' => ['t' => 92.0, 'l' => 50.0],

        'SW' => ['t' => 83.0, 'l' => 50.0],

        'LB' => ['t' => 76.0, 'l' => 14.0],
        'LCB' => ['t' => 76.0, 'l' => 36.0],
        'CB' => ['t' => 76.0, 'l' => 50.0],
        'RCB' => ['t' => 76.0, 'l' => 64.0],
        'RB' => ['t' => 76.0, 'l' => 86.0],

        'LWB' => ['t' => 66.0, 'l' => 12.0],
        'RWB' => ['t' => 66.0, 'l' => 88.0],

        'LDM' => ['t' => 58.0, 'l' => 36.0],
        'LCDM' => ['t' => 58.0, 'l' => 36.0],
        'CDM' => ['t' => 58.0, 'l' => 50.0],
        'DM' => ['t' => 58.0, 'l' => 50.0],
        'RDM' => ['t' => 58.0, 'l' => 64.0],
        'RCDM' => ['t' => 58.0, 'l' => 64.0],

        'LM' => ['t' => 50.0, 'l' => 18.0],
        'LCM' => ['t' => 50.0, 'l' => 36.0],
        'CM' => ['t' => 50.0, 'l' => 50.0],
        'RCM' => ['t' => 50.0, 'l' => 64.0],
        'RM' => ['t' => 50.0, 'l' => 82.0],

        'LAM' => ['t' => 40.0, 'l' => 36.0],
        'CAM' => ['t' => 40.0, 'l' => 50.0],
        'RAM' => ['t' => 40.0, 'l' => 64.0],

        'LW' => ['t' => 28.0, 'l' => 14.0],
        'LF' => ['t' => 28.0, 'l' => 36.0],
        'CF' => ['t' => 28.0, 'l' => 50.0],
        'RF' => ['t' => 28.0, 'l' => 64.0],
        'RW' => ['t' => 28.0, 'l' => 86.0],

        'LS' => ['t' => 18.0, 'l' => 38.0],
        'ST' => ['t' => 16.0, 'l' => 50.0],
        'RS' => ['t' => 18.0, 'l' => 62.0],
    ];

    return $map[$k] ?? null;
}

/**
 * Quy nhóm các vị trí trung tâm để khi bị trùng sẽ tách trái/phải theo sơ đồ.
 */
function lineupPositionFamily(?string $fieldPosition): ?string
{
    $k = lineupNormalizeFieldKey($fieldPosition);
    if ($k === '') {
        return null;
    }

    static $families = [
        'CB' => 'CB', 'LCB' => 'CB', 'RCB' => 'CB', 'SW' => 'CB',
        'CDM' => 'CDM', 'LCDM' => 'CDM', 'RCDM' => 'CDM', 'LDM' => 'CDM', 'RDM' => 'CDM', 'DM' => 'CDM',
        'CM' => 'CM', 'LCM' => 'CM', 'RCM' => 'CM', 'LM' => 'CM', 'RM' => 'CM',
        'CAM' => 'CAM', 'LAM' => 'CAM', 'RAM' => 'CAM',
        'CF' => 'CF', 'LF' => 'CF', 'RF' => 'CF',
        'ST' => 'ST', 'LS' => 'ST', 'RS' => 'ST',
    ];

    return $families[$k] ?? null;
}

/**
 * Chuyển vị trí trung tâm sang biến thể trái/phải nếu có nhiều người trùng vị trí.
 */
function lineupResolveDuplicateCentralPosition(string $normalizedPosition, int $index, int $count): string
{
    $family = lineupPositionFamily($normalizedPosition);
    if ($family === null || $count <= 1) {
        return $normalizedPosition;
    }

    $patterns = [
        'CB' => ['LCB', 'CB', 'RCB'],
        'CDM' => ['LDM', 'CDM', 'RDM'],
        'CM' => ['LCM', 'CM', 'RCM'],
        'CAM' => ['LAM', 'CAM', 'RAM'],
        'CF' => ['LF', 'CF', 'RF'],
        'ST' => ['LS', 'ST', 'RS'],
    ];
    $slots = $patterns[$family] ?? [$normalizedPosition];
    $slotCount = count($slots);

    if ($count === 2 && $slotCount >= 2) {
        return $family === 'ST'
            ? ($index === 0 ? 'LS' : 'RS')
            : ($index === 0 ? $slots[0] : $slots[$slotCount - 1]);
    }

    if ($count >= 3 && $slotCount >= 3) {
        if ($index === 0) {
            return $slots[0];
        }
        if ($index === $count - 1) {
            return $slots[$slotCount - 1];
        }
        return $slots[1];
    }

    return $slots[min($index, $slotCount - 1)];
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
    $grouped = [];

    foreach ($starters as $s) {
        $fp = isset($s['FieldPosition']) && is_string($s['FieldPosition']) ? $s['FieldPosition'] : null;
        $norm = lineupNormalizeFieldKey($fp);
        if ($norm === '' || lineupSlotForPosition($norm) === null) {
            $unplaced[] = $s;
            continue;
        }
        $family = lineupPositionFamily($norm) ?? $norm;
        if (!isset($grouped[$family])) {
            $grouped[$family] = [];
        }
        $grouped[$family][] = ['row' => $s, 'norm' => $norm];
    }

    foreach ($grouped as $familyRows) {
        $count = count($familyRows);
        foreach ($familyRows as $i => $entry) {
            $resolved = lineupResolveDuplicateCentralPosition($entry['norm'], $i, $count);
            $slot = lineupSlotForPosition($resolved);
            if ($slot === null) {
                $unplaced[] = $entry['row'];
                continue;
            }
            $placed[] = array_merge($entry['row'], [
                'pitchT' => $slot['t'],
                'pitchL' => $slot['l'],
                'ResolvedFieldPosition' => $resolved,
            ]);
        }
    }

    $n = count($unplaced);
    foreach ($unplaced as $i => $s) {
        $l = $n <= 1 ? 50.0 : 18.0 + ($i / (float) max(1, $n - 1)) * 64.0;
        $placed[] = array_merge($s, ['pitchT' => 56.0, 'pitchL' => $l, 'ResolvedFieldPosition' => null]);
    }

    return $placed;
}

/**
 * Vòng tròn hiển thị số áo (không dùng ảnh).
 *
 * @param mixed $shirtNumber
 * @param string $teamSide 'home' | 'away' | ''
 */
function lineupPlayerShirtBadgeHtml($shirtNumber, string $fullName, string $teamSide = '', bool $isOut = false): string
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
    if ($isOut) {
        $classes .= ' admin-lineup-shirt--out';
        $label = 'OUT';
    }
    $title = htmlspecialchars($fullName . ($shirtNumber !== null && $shirtNumber !== '' ? ' · #' . (string) (int) $shirtNumber : '') . ($isOut ? ' · OUT' : ''), ENT_QUOTES, 'UTF-8');
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
                $fp = '—';
                if (isset($pl['ResolvedFieldPosition']) && is_string($pl['ResolvedFieldPosition']) && $pl['ResolvedFieldPosition'] !== '') {
                    $fp = $pl['ResolvedFieldPosition'];
                } elseif ($pl['FieldPosition'] !== null && $pl['FieldPosition'] !== '') {
                    $fp = (string) $pl['FieldPosition'];
                }
                $t = (float) $pl['pitchT'];
                $l = (float) $pl['pitchL'];
                $nodeClass = 'admin-lineup-node--' . htmlspecialchars($side, ENT_QUOTES, 'UTF-8');
                if (!empty($pl['is_out'])) {
                    $nodeClass .= ' admin-lineup-node--out';
                }
                ?>
                <div class="admin-lineup-node <?= $nodeClass ?>" style="top: <?= $t ?>%;left: <?= $l ?>%;">
                    <span class="admin-lineup-node-pos"><?= htmlspecialchars($fp, ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="admin-lineup-node-figure">
                        <?= lineupPlayerShirtBadgeHtml($pl['ShirtNumber'] ?? null, (string) $pl['FullName'], $side, !empty($pl['is_out'])) ?>
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
                <div class="admin-lineup-bench-item admin-lineup-bench-item--<?= htmlspecialchars($side, ENT_QUOTES, 'UTF-8') ?> <?= !empty($pl['is_out']) ? 'admin-lineup-node--out' : '' ?> text-center">
                    <?= lineupPlayerShirtBadgeHtml($pl['ShirtNumber'] ?? null, (string) $pl['FullName'], $side, !empty($pl['is_out'])) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php

    return (string) ob_get_clean();
}
