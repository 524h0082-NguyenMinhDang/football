<?php
declare(strict_types=1);

/**
 * Tính bảng xếp hạng từ các trận đã có tỷ số (HomeScore & AwayScore không null).
 * Điểm: thắng 3, hòa 1, thua 0. Sắp xếp: điểm → hiệu số → bàn thắng → tên CLB.
 *
 * @return list<array{clubId:int,name:string,shortName:?string,played:int,won:int,drawn:int,lost:int,gf:int,ga:int,gd:int,pts:int,rank:int}>
 */
function getLeagueStandings(PDO $pdo): array
{
    $clubs = $pdo->query('SELECT ClubId, Name, ShortName FROM `Club` ORDER BY Name')->fetchAll();
    $stats = [];
    foreach ($clubs as $c) {
        $id = (int) $c['ClubId'];
        $stats[$id] = [
            'clubId' => $id,
            'name' => (string) $c['Name'],
            'shortName' => $c['ShortName'] !== null && $c['ShortName'] !== '' ? (string) $c['ShortName'] : null,
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'gf' => 0,
            'ga' => 0,
        ];
    }

    $sql = <<<'SQL'
SELECT HomeClubId, AwayClubId, HomeScore, AwayScore
FROM `Match`
WHERE HomeScore IS NOT NULL AND AwayScore IS NOT NULL
SQL;
    $matches = $pdo->query($sql)->fetchAll();
    foreach ($matches as $m) {
        $hid = (int) $m['HomeClubId'];
        $aid = (int) $m['AwayClubId'];
        $hs = (int) $m['HomeScore'];
        $as = (int) $m['AwayScore'];
        if (!isset($stats[$hid]) || !isset($stats[$aid])) {
            continue;
        }
        $stats[$hid]['played']++;
        $stats[$aid]['played']++;
        $stats[$hid]['gf'] += $hs;
        $stats[$hid]['ga'] += $as;
        $stats[$aid]['gf'] += $as;
        $stats[$aid]['ga'] += $hs;
        if ($hs > $as) {
            $stats[$hid]['won']++;
            $stats[$aid]['lost']++;
        } elseif ($hs < $as) {
            $stats[$hid]['lost']++;
            $stats[$aid]['won']++;
        } else {
            $stats[$hid]['drawn']++;
            $stats[$aid]['drawn']++;
        }
    }

    $rows = [];
    foreach ($stats as $s) {
        $gd = $s['gf'] - $s['ga'];
        $pts = $s['won'] * 3 + $s['drawn'];
        $rows[] = array_merge($s, ['gd' => $gd, 'pts' => $pts]);
    }

    usort($rows, static function (array $a, array $b): int {
        if ($a['pts'] !== $b['pts']) {
            return $b['pts'] <=> $a['pts'];
        }
        if ($a['gd'] !== $b['gd']) {
            return $b['gd'] <=> $a['gd'];
        }
        if ($a['gf'] !== $b['gf']) {
            return $b['gf'] <=> $a['gf'];
        }
        return strcasecmp($a['name'], $b['name']);
    });

    $rank = 0;
    $lastKey = null;
    foreach ($rows as $i => &$r) {
        $key = $r['pts'] . '|' . $r['gd'] . '|' . $r['gf'];
        if ($key !== $lastKey) {
            $rank = $i + 1;
            $lastKey = $key;
        }
        $r['rank'] = $rank;
    }
    unset($r);

    return $rows;
}
