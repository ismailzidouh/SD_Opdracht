<?php
/**
 * Monster Battle Academy - student starter
 * Sprint 1: ALLEEN procedurele PHP (arrays + functies).
 * Sprint 2: jullie zetten deze bestaande game om naar OOP.
 * Sprint 3: voeg inheritance en verschillende monstertypes toe.
 */

function startGame(): void
{
    $playerTeam = [
        ['name' => 'Charmander', 'hp' => 100, 'max_hp' => 100, 'attack' => 15, 'defense' => 5, 'pokemon_id' => 4],
        ['name' => 'Squirtle', 'hp' => 92, 'max_hp' => 92, 'attack' => 13, 'defense' => 8, 'pokemon_id' => 7],
        ['name' => 'Bulbasaur', 'hp' => 105, 'max_hp' => 105, 'attack' => 12, 'defense' => 7, 'pokemon_id' => 1]
    ];

    $_SESSION['game'] = [
        'player_team' => $playerTeam,
        'active_player' => 0,
        'player' => $playerTeam[0],
        'opponent' => ['name' => 'Pikachu', 'hp' => 100, 'max_hp' => 100, 'attack' => 12, 'defense' => 4, 'pokemon_id' => 25],
        'message' => 'Een wild Pikachu verschijnt!',
        'winner' => null
    ];
}

function handleAction(array $formData): void
{
    $action = isset($formData['action']) ? (string)$formData['action'] : '';

    if ($action === 'reset') {
        resetGame();
        return;
    }

    if (!isset($_SESSION['game'])) {
        startGame();
    }

    $game = &$_SESSION['game'];
    normalizeGameState($game);

    if ($game['winner'] !== null) {
        $game['message'] = 'Het gevecht is voorbij. Kies "Opnieuw" om opnieuw te spelen.';
        return;
    }

    if ($action === 'switch') {
        $team = $game['player_team'];
        $current = (int)$game['active_player'];
        $next = null;

        for ($step = 1; $step <= count($team); $step++) {
            $candidate = ($current + $step) % count($team);
            if (!isDefeated($team[$candidate])) {
                $next = $candidate;
                break;
            }
        }

        if ($next !== null && $next !== $current) {
            $game['active_player'] = $next;
            $game['player'] = $team[$next];
            $game['message'] = $team[$next]['name'] . ' komt het gevecht in.';
        } else {
            $game['message'] = 'Er is geen ander levensvatbaar monster om te wisselen.';
        }
        return;
    }

    if ($action !== 'attack') {
        $game['message'] = 'Kies een geldige actie.';
        return;
    }

    $damageToOpponent = calculateDamage($game['player'], $game['opponent']);
    $game['opponent']['hp'] = max(0, $game['opponent']['hp'] - $damageToOpponent);
    $game['message'] = $game['player']['name'] . ' valt aan en doet ' . $damageToOpponent . ' schade.';

    if (isDefeated($game['opponent'])) {
        $game['winner'] = 'player';
        $game['message'] .= ' ' . $game['opponent']['name'] . ' is verslagen!';
        return;
    }

    $damageToPlayer = calculateDamage($game['opponent'], $game['player']);
    $game['player']['hp'] = max(0, $game['player']['hp'] - $damageToPlayer);

    $game['player_team'][$game['active_player']] = $game['player'];
    $game['message'] .= ' ' . $game['opponent']['name'] . ' slaat terug en doet ' . $damageToPlayer . ' schade.';

    if (isDefeated($game['player'])) {
        $next = null;
        foreach ($game['player_team'] as $index => $monster) {
            if (!isDefeated($monster)) {
                $next = $index;
                break;
            }
        }

        if ($next !== null) {
            $game['active_player'] = $next;
            $game['player'] = $game['player_team'][$next];
            $game['message'] .= ' ' . $game['player']['name'] . ' neemt het over.';
        } else {
            $game['winner'] = 'opponent';
            $game['message'] .= ' Al jouw monsters zijn verslagen!';
        }
    }
}

function calculateDamage(array $attacker, array $defender): int
{
    $attack = isset($attacker['attack']) ? (int)$attacker['attack'] : 10;
    $defense = isset($defender['defense']) ? (int)$defender['defense'] : 0;
    $variation = random_int(80, 120) / 100;

    return max(1, (int)round(($attack * $variation) - ($defense / 2)));
}

function isDefeated(array $monster): bool
{
    return (int)($monster['hp'] ?? 0) <= 0;
}

function normalizeGameState(array &$game): void
{
    if (!isset($game['player'], $game['opponent']) || !is_array($game['player']) || !is_array($game['opponent'])) {
        startGame();
        $game = $_SESSION['game'];
        return;
    }

    $game['player'] += [
        'name' => 'Jouw monster',
        'max_hp' => (int)($game['player']['hp'] ?? 100),
        'attack' => 15,
        'defense' => 0
    ];
    $game['opponent'] += [
        'name' => 'Wild monster',
        'max_hp' => (int)($game['opponent']['hp'] ?? 100),
        'attack' => 12,
        'defense' => 0
    ];

    if (!isset($game['player_team']) || !is_array($game['player_team']) || $game['player_team'] === []) {
        $game['player_team'] = [$game['player']];
        $game['active_player'] = 0;
    }

    if (!isset($game['active_player']) || !isset($game['player_team'][$game['active_player']])) {
        $game['active_player'] = 0;
    }

    $game['player_team'][$game['active_player']] = $game['player'];
    $game['winner'] = $game['winner'] ?? null;
    $game['message'] = $game['message'] ?? 'Een wild monster verschijnt!';
}

function resetGame(): void
{
    startGame();
}
