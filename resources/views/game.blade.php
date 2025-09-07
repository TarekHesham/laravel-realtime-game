<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>لعبة X-O - غرفة {{ $roomCode }}</title>
    
    <style>
        .game-container {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        .game-title {
            font-size: 2.5em;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .room-code {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .invite-code {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px;
            border-radius: 6px;
            font-family: monospace;
            word-break: break-all;
        }

        .copy-btn {
            background: #28a745;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }

        .copy-btn:hover {
            background: #218838;
        }

        .spectator-badge {
            background: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }

        .symbol-selector {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .symbol-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 15px 25px;
            margin: 10px;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .symbol-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .game-board {
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .turn-info {
            font-size: 1.2em;
            margin: 20px 0;
            padding: 10px;
            border-radius: 8px;
        }

        .status-success {
            background: rgba(40, 167, 69, 0.8);
        }

        .status-info {
            background: rgba(23, 162, 184, 0.8);
        }

        .status-waiting {
            background: rgba(255, 193, 7, 0.8);
            color: #000;
        }

        .winner-announcement {
            font-size: 1.5em;
            padding: 20px;
            background: rgba(40, 167, 69, 0.9);
            border-radius: 10px;
            margin: 20px 0;
            animation: celebration 0.5s ease-in-out;
        }

        @keyframes celebration {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .reset-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .reset-btn:hover {
            background: #c82333;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        .status-message {
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            display: none;
        }

        .status-message.status-error {
            background: rgba(220, 53, 69, 0.8);
        }

        .status-message.status-success {
            background: rgba(40, 167, 69, 0.8);
        }

        @media (max-width: 600px) {

            
            .game-title {
                font-size: 2em;
            }
        }
    </style>
    
    @vite(['resources/js/app.js'])

</head>
<body>
    <div class="room-info absolute top-[14px] right-[14px]">
        <div class="room-code">كود الغرفة: <span id="inviteCode" class="invite-code">{{ $roomCode }}</span></div>
        <button class="copy-btn" onclick="copyInviteCode()">نسخ الكود</button>
        <a href="/" class="back-btn">العودة للصفحة الرئيسية</a>
    </div>

    <div class="game-container">
        <h1 class="game-title">لعبة X-O</h1>
        
        <div id="symbolSelector" class="symbol-selector" style="display: none;">
            <h3 style="margin-bottom: 15px;">اختر رمزك:</h3>
            <button class="symbol-btn" onclick="selectSymbol('X')">X</button>
            <button class="symbol-btn" onclick="selectSymbol('O')">O</button>
        </div>
        
        <div id="turnInfo" class="turn-info status-info">جاري التحميل...</div>
        
        <div id="statusMessage" class="status-message"></div>
        
        <div id="gameBoard" class="game-board"></div>
    </div>

    <div class="players-info absolute bottom-[-25%] w-[93vw] lg:bottom-auto lg:w-auto lg:top-[14px] lg:left-[14px]">
        <h3 style="margin-bottom: 15px;font-size: 1.5em;font-weight: bold;margin-bottom: 10px;">اللاعبين</h3>
        <div id="playersList">جاري التحميل...</div>
    </div>

    <div id="toast-container" class="fixed bottom-6 right-6 z-50 flex flex-col space-y-2"></div>

    <script>
        const roomCode = '{{ $roomCode }}';
        let gameState = {
            board: Array(3).fill().map(() => Array(3).fill(null)),
            status: 'waiting',
            currentTurn: null,
            player: null,
            players: [],
            canChooseSymbol: false,
            availableSymbols: [],
            spectators: []
        };

        async function initGame() {
            try {
                const response = await fetch(`/api/game/room/${roomCode}`);
                
                if (!response.ok) {
                    const errorData = await response.json();
                    showMessage(errorData.error || 'الغرفة غير موجودة', 'error');
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 2000);
                    return;
                }
                
                const data = await response.json();
                updateGameState(data);
                createBoard();
                updateUI();
                
            } catch (error) {
                showMessage('خطأ في تحميل اللعبة', 'error');
                setTimeout(() => {
                    window.location.href = '/';
                }, 2000);
            }
        }

        function updateGameState(data) {
            gameState = {
                board: data.board || Array(3).fill().map(() => Array(3).fill(null)),
                status: data.status || 'waiting',
                currentTurn: data.current_turn,
                player: data.player,
                players: data.players || [],
                canChooseSymbol: data.can_choose_symbol || false,
                availableSymbols: data.available_symbols || [],
                winner: data.winner,
                isDraw: data.is_draw,
                spectators: data.spectators
            };
        }

        function updateUI() {
            updatePlayersList();
            updateTurnInfo();
            updateBoard();
            updateSymbolSelector();

            const boardElement = document.getElementById('gameBoard');

            if (gameState.status === 'waiting') {
                boardElement.style.display = 'none';
            } else {
                boardElement.style.display = 'grid';
            }

            if (gameState.status === 'finished') {
                showWinner(gameState.winner, gameState.isDraw);
            }
        }

        function updatePlayersList() {
            const playersContainer = document.getElementById('playersList');
            
            if (gameState.players.length === 0) {
                playersContainer.innerHTML = '<div class="player-item">لا يوجد لاعبين نشطين</div>';
                return;
            }
            
            let html = '';
            let sortedPlayers = [...gameState.players].sort((a, b) => b.score - a.score);

            sortedPlayers.forEach(player => {
                let rank = sortedPlayers.findIndex(p => p.name === player.name) + 1;

                let medal = '';
                if (rank === 1) medal = '🥇';
                else if (rank === 2) medal = '🥈';

                html += `
                    <div class="player-item">
                        <span class="player-name">${player.name}</span>
                        <span class="player-symbol">${player.symbol || 'لم يختر بعد'}</span>
                        <span class="player-score">${medal} ${player.score}</span>
                    </div>
                `;
            });

            if (gameState.player && gameState.player.is_spectator) {
                html += `
                    <div class="player-item">
                        <span class="player-name">${gameState.player.name} (أنت)</span>
                        <span class="spectator-badge">متفرج</span>
                    </div>
                `;
            } else {
                gameState.spectators?.forEach(spectator => {
                    html += `
                        <div class="player-item">
                            <span class="player-name">${spectator.name}</span>
                            <span class="spectator-badge">متفرج</span>
                        </div>
                    `;
                });
            }
            
            playersContainer.innerHTML = html;
        }

        function updateTurnInfo() {
            const turnElement = document.getElementById('turnInfo');
            
            if (gameState.status === 'waiting') {
                if (gameState.players.length < 2) {
                    turnElement.textContent = 'انتظار المزيد من اللاعبين...';
                    turnElement.className = 'turn-info status-waiting';
                } else {
                    turnElement.textContent = 'انتظار اختيار الرموز...';
                    turnElement.className = 'turn-info status-info';
                }
            } else if (gameState.status === 'playing') {
                if (gameState.player && gameState.player.is_spectator) {
                    turnElement.textContent = `دور: ${gameState.currentTurn}`;
                    turnElement.className = 'turn-info status-info';
                } else if (gameState.player && gameState.player.symbol === gameState.currentTurn) {
                    turnElement.textContent = '🎯 دورك الآن!';
                    turnElement.className = 'turn-info status-success';
                } else {
                    turnElement.textContent = '⏳ انتظار اللاعب الآخر...';
                    turnElement.className = 'turn-info status-info';
                }
            } else if (gameState.status === 'finished') {
                turnElement.textContent = 'انتهت اللعبة';
                turnElement.className = 'turn-info status-info';
            }
        }

        function updateSymbolSelector() {
            const selector = document.getElementById('symbolSelector');
            if (gameState.canChooseSymbol && gameState.availableSymbols) {
                selector.style.display = 'block';

                // Update available buttons
                const buttons = selector.querySelectorAll('.symbol-btn');

                buttons.forEach(btn => {
                    const symbol = btn.textContent;
                    btn.disabled = !gameState.availableSymbols.includes(symbol);
                    btn.style.opacity = gameState.availableSymbols.includes(symbol) ? '1' : '0.5';
                });
            } else {
                selector.style.display = 'none';
            }
        }

        function createBoard() {
            const boardElement = document.getElementById('gameBoard');
            boardElement.innerHTML = '';
            
            for (let i = 0; i < 3; i++) {
                for (let j = 0; j < 3; j++) {
                    const cell = document.createElement('div');
                    cell.className = 'cell';
                    cell.dataset.x = i;
                    cell.dataset.y = j;
                    cell.onclick = () => makeMove(i, j);
                    boardElement.appendChild(cell);
                }
            }
        }

        function updateBoard() {
            const cells = document.querySelectorAll('.cell');
            
            gameState.board.forEach((row, i) => {
                row.forEach((cell, j) => {
                    const cellElement = cells[i * 3 + j];
                    cellElement.textContent = cell || '';
                    cellElement.className = `cell ${cell || ''}`;
                    
                    // Check if cell should be disabled
                    const shouldDisable = 
                        cell || // Cell is occupied
                        !gameState.player || // No player data
                        gameState.player.is_spectator || // Is spectator
                        gameState.status !== 'playing' || // Game not playing
                        !gameState.player.symbol || // No symbol chosen
                        gameState.player.symbol !== gameState.currentTurn; // Not player's turn
                    
                    if (shouldDisable) {
                        cellElement.classList.add('disabled');
                    } else {
                        cellElement.classList.remove('disabled');
                    }
                });
            });
        }

        async function selectSymbol(symbol) {
            try {
                const response = await fetch(`/api/game/room/${roomCode}/symbol`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ symbol: symbol })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    showMessage('تم اختيار الرمز بنجاح!', 'success');
                    // Refresh game state
                    setTimeout(initGame, 500);
                } else {
                    showMessage(data.error, 'error');
                }
            } catch (error) {
                showMessage('خطأ في اختيار الرمز', 'error');
            }
        }

        async function makeMove(x, y) {
            // Check if move is allowed
            if (!gameState.player || 
                gameState.player.is_spectator || 
                gameState.status !== 'playing' ||
                !gameState.player.symbol ||
                gameState.player.symbol !== gameState.currentTurn ||
                gameState.board[x][y] !== null) {
                return;
            }

            try {
                const response = await fetch(`/api/game/room/${roomCode}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ x: x, y: y })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    showMessage(data.error, 'error');
                }
            } catch (error) {
                showMessage('خطأ في إرسال الحركة', 'error');
            }
        }

        async function resetGame() {
            try {
                const response = await fetch(`/api/game/room/${roomCode}/reset`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    showMessage('خطأ في إعادة تشغيل اللعبة', 'error');
                }
            } catch (error) {
                showMessage('خطأ في إعادة تشغيل اللعبة', 'error');
            }
        }

        function copyInviteCode() {
            const code = document.getElementById('inviteCode').innerText.trim();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(() => {
                    showMessage('تم نسخ الكود!', 'success');
                }).catch(() => {
                    showMessage('فشل في نسخ الكود', 'error');
                });
            } else {
                const temp = document.createElement("textarea");
                temp.value = code;
                document.body.appendChild(temp);
                temp.select();
                try {
                    document.execCommand("copy");
                    showMessage('تم نسخ الكود!', 'success');
                } catch (err) {
                    showMessage('فشل في نسخ الكود', 'error');
                }
                document.body.removeChild(temp);
            }
        }

        function showWinner(winner, isDraw) {
            // Remove existing winner announcement
            const existingAnnouncement = document.querySelector('.winner-announcement');
            if (existingAnnouncement) {
                existingAnnouncement.remove();
            }
            
            let message;
            if (isDraw) {
                message = '🤝 تعادل!';
            } else if (gameState.player && winner === gameState.player.symbol) {
                message = '🎉 مبروك! أنت الفائز!';
            } else if (gameState.player && gameState.player.is_spectator) {
                message = `🏆 الفائز: ${winner}`;
            } else {
                message = `🏆 الفائز: ${winner}`;
            }
            
            const winnerDiv = document.createElement('div');
            winnerDiv.className = 'winner-announcement';
            winnerDiv.textContent = message;
            
            const boardElement = document.getElementById('gameBoard');
            boardElement.parentNode.insertBefore(winnerDiv, boardElement.nextSibling);
        }

        function showMessage(message, type = 'info') {
            const container = document.getElementById('toast-container');

            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500 text-black'
            };

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;

            container.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 50);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        // Initialize when page loads
        window.onload = () => {
            initGame();

            // Set up WebSocket listeners
            const channel = window.Echo.channel(`game.${roomCode}`);

            channel.listen('.game.move', function(data) {
                gameState.board = data.board;
                gameState.currentTurn = data.nextTurn;
                updateBoard();
                updateTurnInfo();
            });

            channel.listen('.game.win', function(data) {
                gameState.board = data.board;
                gameState.status = 'finished';
                gameState.winner = data.winner;
                gameState.isDraw = data.isDraw;
                updateBoard();
                updateTurnInfo();
                showWinner(data.winner, data.isDraw);

                setTimeout(resetGame, 5000);
            });

            channel.listen('.game.reset', function(data) {
                // Refresh the entire game state
                initGame();
                // Remove winner announcement
                const winnerAnnouncement = document.querySelector('.winner-announcement');
                if (winnerAnnouncement) {
                    winnerAnnouncement.remove();
                }
            });

            channel.listen('.game.state', function(data) {
                if (data.status === 'closed') {
                    showMessage('تم إغلاق الغرفة لأن أحد اللاعبين خرج.');
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 1000);
                    return;
                }

                // Game state changed (like when second player joins)
                initGame();
            });
        };
    </script>
</body>
</html>