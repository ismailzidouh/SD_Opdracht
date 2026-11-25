<?php


class Pokemon
{
    public function __construct(
        public string $name,
        public int $hp,
        public int $attack,
        public int $defense,
        public array $moves   
    ) {}

    public function isFainted(): bool
    {
        return $this->hp <= 0;
    }
}

class BattleLog
{
    private array $messages = [];

    public function add(string $text): void
    {
        $this->messages[] = $text;
    }

    public function all(): array
    {
        return $this->messages;
    }
}

function attack(Pokemon $attacker, Pokemon $defender, BattleLog $log, string $prefix = ''): void
{
    $move = $attacker->moves[array_rand($attacker->moves)];
    $log->add("{$prefix}{$attacker->name} used {$move['name']}!");

    // 1 in 10 chance to miss
    if (mt_rand(1, 10) === 1) {
        $log->add("But it missed!");
        return;
    }

    $damage = (int) round(($move['power'] * $attacker->attack / $defender->defense) / 5 + mt_rand(1, 4));

    // 1 in 8 chance of a critical hit
    if (mt_rand(1, 8) === 1) {
        $damage *= 2;
        $log->add("A critical hit!");
    }

    $damage = max(1, $damage);
    $defender->hp = max(0, $defender->hp - $damage);
    $log->add("{$defender->name} lost {$damage} HP.");
}



$log = new BattleLog();

$player = new Pokemon('PIKACHU', 60, 55, 40, [
    ['name' => 'Thunder Shock', 'power' => 40],
    ['name' => 'Quick Attack',  'power' => 40],
]);

$enemy = new Pokemon('RATTATA', 45, 56, 35, [
    ['name' => 'Tackle',      'power' => 40],
    ['name' => 'Quick Attack', 'power' => 40],
]);

$log->add("A wild {$enemy->name} appeared!");
$log->add("Go! {$player->name}!");

while (!$player->isFainted() && !$enemy->isFainted()) {
    attack($player, $enemy, $log);
    if ($enemy->isFainted()) break;

    attack($enemy, $player, $log, 'Wild ');
}

if ($enemy->isFainted()) {
    $log->add("Wild {$enemy->name} fainted!");
    $log->add("{$player->name} gained 24 EXP. Points!");
} else {
    $log->add("{$player->name} fainted!");
    $log->add("You blacked out!");
}

// Safe way to hand the messages to JavaScript
$messagesJson = json_encode($log->all(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Battle Log</title>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<style>
    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        background: #6a9a5b;
        font-family: 'Press Start 2P', 'Courier New', monospace;
    }
    #battle-box {
        position: relative;
        box-sizing: border-box;
        width: min(640px, 100%);
        height: 140px;
        margin: 16px;
        padding: 22px 28px;
        background: #f8f8f8;
        color: #383838;
        border: 6px solid #383838;
        border-radius: 8px;
        outline: 4px solid #e8b030;
        outline-offset: -12px;
        font-size: 16px;
        line-height: 1.8;
        cursor: pointer;
        user-select: none;
    }
    #arrow {
        position: absolute;
        right: 26px;
        bottom: 18px;
        font-size: 14px;
        visibility: hidden;
        animation: blink 0.8s steps(1) infinite;
    }
    @keyframes blink { 50% { opacity: 0; } }
</style>
</head>
<body>

<div id="battle-box">
    <span id="text"></span>
    <span id="arrow">▼</span>
</div>

<script>

const messages = <?= $messagesJson ?>;   // PHP puts its array here

const textEl  = document.getElementById('text');
const arrowEl = document.getElementById('arrow');
const box     = document.getElementById('battle-box');

const CHAR_DELAY = 30;   
let index = 0;           
let typing = false;      
let timer = null;
let current = '';

function showMessage(msg) {
    current = msg;
    textEl.textContent = '';
    arrowEl.style.visibility = 'hidden';
    typing = true;

    let i = 0;
    timer = setInterval(() => {
        textEl.textContent += msg[i++];
        if (i >= msg.length) finishTyping();
    }, CHAR_DELAY);
}

function finishTyping() {
    clearInterval(timer);
    textEl.textContent = current;
    typing = false;
    arrowEl.style.visibility = 'visible';
}

function advance() {
    if (typing) {                         
        finishTyping();
    } else if (index < messages.length) { 
        showMessage(messages[index++]);
    } else {                              
        arrowEl.style.visibility = 'hidden';
        textEl.textContent = 'Battle over! Refresh to fight again.';
    }
}

box.addEventListener('click', advance);
document.addEventListener('keydown', (e) => {
    if (e.code === 'Space' || e.code === 'Enter' || e.code === 'KeyZ') {
        e.preventDefault();
        advance();
    }
});

advance(); 
</script>
</body>
</html>