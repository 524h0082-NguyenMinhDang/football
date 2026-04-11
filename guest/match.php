<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) {
    header('Location: index.php');
    exit;
}

$pdo = getPdo();
$st = $pdo->prepare(<<<SQL
SELECT m.*, hc.Name AS HomeName, ac.Name AS AwayName
FROM `Match` m
JOIN `Club` hc ON m.HomeClubId = hc.ClubId
JOIN `Club` ac ON m.AwayClubId = ac.ClubId
WHERE m.MatchId = ?
SQL);
$st->execute([$id]);
$match = $st->fetch();
if (!$match) {
    header('Location: index.php');
    exit;
}

$homeClubId = (int) $match['HomeClubId'];
$awayClubId = (int) $match['AwayClubId'];

$ln = $pdo->prepare(<<<SQL
SELECT l.LineupId, l.PlayerId, l.IsHomeTeam, l.IsStarter, l.FieldPosition,
       p.FullName, p.ShirtNumber, p.Position, p.Nationality, p.DateOfBirth
FROM `MatchLineup` l
JOIN `Player` p ON l.PlayerId = p.PlayerId
WHERE l.MatchId = ?
ORDER BY l.IsHomeTeam DESC, l.IsStarter DESC, p.ShirtNumber, p.FullName
SQL);
$ln->execute([$id]);
$allLineup = $ln->fetchAll();

$formatDob = static function (?string $d): string {
    if ($d === null || $d === '') {
        return '—';
    }
    $t = strtotime($d);
    return $t !== false ? date('d/m/Y', $t) : '—';
};

$lineupHome = [];
$lineupAway = [];
foreach ($allLineup as $row) {
    $entry = [
        'lineupId' => (int) $row['LineupId'],
        'playerId' => (int) $row['PlayerId'],
        'isStarter' => (bool) (int) $row['IsStarter'],
        'fieldPosition' => $row['FieldPosition'] !== null && $row['FieldPosition'] !== '' ? (string) $row['FieldPosition'] : null,
        'fullName' => (string) $row['FullName'],
        'shirtNumber' => $row['ShirtNumber'] !== null ? (int) $row['ShirtNumber'] : null,
        'position' => $row['Position'] !== null && $row['Position'] !== '' ? (string) $row['Position'] : null,
        'nationality' => $row['Nationality'] !== null && $row['Nationality'] !== '' ? (string) $row['Nationality'] : null,
        'dob' => $formatDob($row['DateOfBirth'] !== null ? (string) $row['DateOfBirth'] : null),
    ];
    if ((int) $row['IsHomeTeam'] === 1) {
        $lineupHome[] = $entry;
    } else {
        $lineupAway[] = $entry;
    }
}

$playerStmt = $pdo->prepare(<<<SQL
SELECT PlayerId, FullName, ShirtNumber, Position, Nationality, DateOfBirth
FROM `Player`
WHERE ClubId = ?
ORDER BY ShirtNumber, FullName
SQL);

$playerStmt->execute([$homeClubId]);
$squadHome = [];
foreach ($playerStmt->fetchAll() as $p) {
    $squadHome[] = [
        'playerId' => (int) $p['PlayerId'],
        'fullName' => (string) $p['FullName'],
        'shirtNumber' => $p['ShirtNumber'] !== null ? (int) $p['ShirtNumber'] : null,
        'position' => $p['Position'] !== null && $p['Position'] !== '' ? (string) $p['Position'] : null,
        'nationality' => $p['Nationality'] !== null && $p['Nationality'] !== '' ? (string) $p['Nationality'] : null,
        'dob' => $formatDob($p['DateOfBirth'] !== null ? (string) $p['DateOfBirth'] : null),
    ];
}

$playerStmt->execute([$awayClubId]);
$squadAway = [];
foreach ($playerStmt->fetchAll() as $p) {
    $squadAway[] = [
        'playerId' => (int) $p['PlayerId'],
        'fullName' => (string) $p['FullName'],
        'shirtNumber' => $p['ShirtNumber'] !== null ? (int) $p['ShirtNumber'] : null,
        'position' => $p['Position'] !== null && $p['Position'] !== '' ? (string) $p['Position'] : null,
        'nationality' => $p['Nationality'] !== null && $p['Nationality'] !== '' ? (string) $p['Nationality'] : null,
        'dob' => $formatDob($p['DateOfBirth'] !== null ? (string) $p['DateOfBirth'] : null),
    ];
}

$matchViewerData = [
    'homeName' => (string) $match['HomeName'],
    'awayName' => (string) $match['AwayName'],
    'lineup' => [
        'home' => $lineupHome,
        'away' => $lineupAway,
    ],
    'squad' => [
        'home' => $squadHome,
        'away' => $squadAway,
    ],
];
$matchViewerJson = json_encode($matchViewerData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

$pageTitle = 'Trận đấu chi tiết';
$isGuestArea = true;
$assetsPrefix = '../';
$guestNavActive = 'matches';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<h1 class="h3 mb-4">Chi tiết trận đấu</h1>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row text-center align-items-center mb-2 g-2">
            <div class="col">
                <button type="button" class="btn btn-light w-100 py-3 match-team-btn active" data-side="home" id="pick-home" aria-pressed="true">
                    <span class="fs-5 fw-semibold"><?= htmlspecialchars((string) $match['HomeName'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="d-block small text-muted mt-1 team-sub home-sub">Đội nhà</span>
                </button>
            </div>
            <div class="col-auto fs-3 px-2">
                <?php if ($match['HomeScore'] !== null && $match['AwayScore'] !== null): ?>
                    <?= (int) $match['HomeScore'] ?> - <?= (int) $match['AwayScore'] ?>
                <?php else: ?>
                    vs
                <?php endif; ?>
            </div>
            <div class="col">
                <button type="button" class="btn btn-light w-100 py-3 match-team-btn" data-side="away" id="pick-away" aria-pressed="false">
                    <span class="fs-5 fw-semibold"><?= htmlspecialchars((string) $match['AwayName'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="d-block small text-muted mt-1 team-sub away-sub">Đội khách</span>
                </button>
            </div>
        </div>
        <p class="text-center small text-muted mb-3 mb-md-2">Nhấn vào tên câu lạc bộ để xem đội hình trận (trái) và chi tiết cầu thủ (phải).</p>
        <ul class="list-unstyled mb-0 small">
            <li><strong>Ngày giờ:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $match['MatchDateTime'])), ENT_QUOTES, 'UTF-8') ?></li>
            <li><strong>Trọng tài:</strong> <?= htmlspecialchars((string) ($match['RefereeName'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
            <li><strong>Sân:</strong> <?= htmlspecialchars((string) ($match['Venue'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></li>
            <li><strong>Trạng thái:</strong> <?= htmlspecialchars((string) $match['Status'], ENT_QUOTES, 'UTF-8') ?></li>
        </ul>
    </div>
</div>

<div class="row g-3 match-detail-panels mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold" id="lineup-panel-title">Đội hình</div>
            <div class="card-body p-0">
                <div class="table-responsive" id="lineup-table-wrap">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Chính / Dự</th>
                                <th>Số</th>
                                <th>Cầu thủ</th>
                                <th>Vị trí trận</th>
                            </tr>
                        </thead>
                        <tbody id="lineup-panel-body"></tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0 p-3 d-none" id="lineup-panel-empty">Chưa có đội hình đăng ký cho đội này trong trận.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold" id="squad-panel-title">Chi tiết cầu thủ</div>
            <div class="card-body p-0">
                <div class="table-responsive" id="squad-table-wrap">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Số</th>
                                <th>Họ tên</th>
                                <th>Vị trí</th>
                                <th>Quốc tịch</th>
                                <th>Ngày sinh</th>
                            </tr>
                        </thead>
                        <tbody id="squad-panel-body"></tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0 p-3 d-none" id="squad-panel-empty">Chưa có dữ liệu cầu thủ cho CLB này.</p>
            </div>
        </div>
    </div>
</div>

<a href="index.php" class="btn btn-outline-secondary">← Quay lại</a>

<script>
(function () {
    const data = <?= $matchViewerJson ?>;

    const pickHome = document.getElementById('pick-home');
    const pickAway = document.getElementById('pick-away');
    const lineupTitle = document.getElementById('lineup-panel-title');
    const squadTitle = document.getElementById('squad-panel-title');
    const lineupBody = document.getElementById('lineup-panel-body');
    const squadBody = document.getElementById('squad-panel-body');
    const lineupEmpty = document.getElementById('lineup-panel-empty');
    const squadEmpty = document.getElementById('squad-panel-empty');
    const lineupTableWrap = document.getElementById('lineup-table-wrap');
    const squadTableWrap = document.getElementById('squad-table-wrap');

    function esc(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setActive(side) {
        const isHome = side === 'home';
        pickHome.classList.toggle('active', isHome);
        pickAway.classList.toggle('active', !isHome);
        pickHome.setAttribute('aria-pressed', isHome ? 'true' : 'false');
        pickAway.setAttribute('aria-pressed', isHome ? 'false' : 'true');
    }

    function render(side) {
        const name = side === 'home' ? data.homeName : data.awayName;
        const lineup = data.lineup[side] || [];
        const squad = data.squad[side] || [];

        lineupTitle.textContent = 'Đội hình — ' + name;
        squadTitle.textContent = 'Chi tiết cầu thủ — ' + name;

        lineupBody.innerHTML = '';
        if (lineup.length === 0) {
            lineupTableWrap.classList.add('d-none');
            lineupEmpty.classList.remove('d-none');
        } else {
            lineupTableWrap.classList.remove('d-none');
            lineupEmpty.classList.add('d-none');
            for (const r of lineup) {
                const tr = document.createElement('tr');
                const role = r.isStarter ? 'Đá chính' : 'Dự bị';
                const shirt = r.shirtNumber != null ? String(r.shirtNumber) : '—';
                const field = r.fieldPosition != null ? r.fieldPosition : '—';
                tr.innerHTML =
                    '<td>' + esc(role) + '</td>' +
                    '<td>' + esc(shirt) + '</td>' +
                    '<td>' + esc(r.fullName) + '</td>' +
                    '<td>' + esc(field) + '</td>';
                lineupBody.appendChild(tr);
            }
        }

        squadBody.innerHTML = '';
        if (squad.length === 0) {
            squadTableWrap.classList.add('d-none');
            squadEmpty.classList.remove('d-none');
        } else {
            squadTableWrap.classList.remove('d-none');
            squadEmpty.classList.add('d-none');
            for (const p of squad) {
                const tr = document.createElement('tr');
                const shirt = p.shirtNumber != null ? String(p.shirtNumber) : '—';
                const pos = p.position != null ? p.position : '—';
                const nat = p.nationality != null ? p.nationality : '—';
                tr.innerHTML =
                    '<td>' + esc(shirt) + '</td>' +
                    '<td>' + esc(p.fullName) + '</td>' +
                    '<td>' + esc(pos) + '</td>' +
                    '<td>' + esc(nat) + '</td>' +
                    '<td>' + esc(p.dob) + '</td>';
                squadBody.appendChild(tr);
            }
        }
    }

    function select(side) {
        setActive(side);
        render(side);
    }

    pickHome.addEventListener('click', function () { select('home'); });
    pickAway.addEventListener('click', function () { select('away'); });

    select('home');
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
