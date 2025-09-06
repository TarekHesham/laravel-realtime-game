<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>لعبة X-O الاحترافية</title>
    <meta http-equiv="Access-Control-Allow-Origin" content="*">
    <meta http-equiv="Access-Control-Allow-Methods" content="GET, POST, PUT, DELETE, OPTIONS">
    <meta http-equiv="Access-Control-Allow-Headers" content="Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN">
    
    @vite(['resources/js/app.js'])
</head>
<body>
    <div class="game-container">
        <h1 class="game-title">لعبة X-O</h1>
        
        <div id="gameContent">
            <div class="game-info">
                <div id="playerInfo">جاري التحميل...</div>
                <div id="turnInfo"></div>
            </div>
            
            <div id="symbolSelector" class="symbol-selector" style="display: none;">
                <h3>اختر الرمز الخاص بك:</h3>
                <button class="symbol-btn" onclick="selectSymbol('X')">X</button>
                <button class="symbol-btn" onclick="selectSymbol('O')">O</button>
            </div>
            
            <div id="statusMessage" class="status-message" style="display: none;"></div>
            
            <div id="gameBoard" class="game-board"></div>
            
            <button class="reset-btn" onclick="resetGame()">إعادة تشغيل اللعبة</button>
        </div>
    </div>

    <script>
        let gameState = {
            playerSymbol: null,
            currentTurn: null,
            board: Array(3).fill().map(() => Array(3).fill(null)),
            gameStatus: 'waiting',
            isSpectator: false
        };

        async function initGame() {
            try {
                const response = await fetch('/api/game/symbol');
                const data = await response.json();
                
                gameState.playerSymbol = data.symbol;
                gameState.currentTurn = data.currentTurn;
                gameState.board = data.board || Array(3).fill().map(() => Array(3).fill(null));
                gameState.gameStatus = data.gameState || 'waiting';
                gameState.isSpectator = data.spectator || false;
                
                updateUI(data);
                createBoard();
                updateBoard(gameState.board);
                
                if (gameState.isSpectator) {
                    document.getElementById('playerInfo').innerHTML = 
                        '<div class="spectator-mode">👁️ انت متفرج - اللعبة ممتلئة</div>';
                }

                if (gameState.gameStatus === 'finished') {
                    showWinner(data.winner, data.isDraw);
                }
            } catch (error) {
                showMessage('خطأ في تحميل اللعبة', 'error');
            }
        }

        function updateUI(data) {
            if (data.choose) {
                document.getElementById('symbolSelector').style.display = 'block';
                document.getElementById('playerInfo').textContent = 'اختر الرمز الخاص بك للبدء';
            } else if (data.spectator) {
                document.getElementById('playerInfo').innerHTML = 
                    '<div class="spectator-mode">👁️ أنت متفرج - اللعبة ممتلئة</div>';
                gameState.isSpectator = true;
            } else if (data.symbol) {
                document.getElementById('playerInfo').textContent = `رمزك: ${data.symbol}`;
                document.getElementById('symbolSelector').style.display = 'none';
            } else {
                document.getElementById('playerInfo').textContent = 'انتظار لاعب آخر...';
            }
            
            updateTurnInfo();
        }

        function updateTurnInfo() {
            const turnElement = document.getElementById('turnInfo');
            if (gameState.gameStatus === 'playing') {
                if (gameState.isSpectator) {
                    turnElement.textContent = `دور: ${gameState.currentTurn}`;
                } else if (gameState.playerSymbol === gameState.currentTurn) {
                    turnElement.textContent = '🎯 دورك الآن!';
                    turnElement.className = 'status-success';
                } else {
                    turnElement.textContent = '⏳ انتظار اللاعب الآخر...';
                    turnElement.className = 'status-info';
                }
            } else if (gameState.gameStatus === 'waiting') {
                turnElement.textContent = 'انتظار بدء اللعبة...';
                turnElement.className = 'status-info';
            }
        }

        async function selectSymbol(symbol) {
            try {
                const response = await fetch('/api/game/symbol', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ symbol: symbol })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    gameState.playerSymbol = symbol;
                    document.getElementById('symbolSelector').style.display = 'none';
                    document.getElementById('playerInfo').textContent = `رمزك: ${symbol}`;
                    showMessage('تم اختيار الرمز بنجاح!', 'success');
                } else {
                    showMessage(data.error, 'error');
                }
            } catch (error) {
                showMessage('خطأ في اختيار الرمز', 'error');
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

        function updateBoard(board) {
            const cells = document.querySelectorAll('.cell');
            
            board.forEach((row, i) => {
                row.forEach((cell, j) => {
                    const cellElement = cells[i * 3 + j];
                    cellElement.textContent = cell || '';
                    cellElement.className = `cell ${cell || ''}`;
                    
                    if (cell || 
                        gameState.isSpectator || 
                        gameState.playerSymbol !== gameState.currentTurn ||
                        gameState.gameStatus !== 'playing') {
                        cellElement.classList.add('disabled');
                        cellElement.style.cursor = 'not-allowed';
                    } else {
                        cellElement.classList.remove('disabled');
                        cellElement.style.cursor = 'pointer';
                    }
                });
            });
        }

        async function makeMove(x, y) {
            if (gameState.isSpectator || 
                gameState.playerSymbol !== gameState.currentTurn ||
                gameState.board[x][y] !== null ||
                gameState.gameStatus !== 'playing') {
                return;
            }

            try {
                const response = await fetch('/api/game/move', {
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
                const response = await fetch('/api/game/reset', {
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

        function showWinner(winner, isDraw) {
            const boardElement = document.getElementById('gameBoard');
            let message;
            
            if (isDraw) {
                message = '🤝 تعادل!';
            } else if (winner === gameState.playerSymbol) {
                message = '🎉 مبروك! أنت الفائز!';
            } else if (gameState.isSpectator) {
                message = `🏆 الفائز: ${winner}`;
            } else {
                message = '😔 للأسف خسرت هذه المرة';
            }
            
            const winnerDiv = document.createElement('div');
            winnerDiv.className = 'winner-announcement';
            winnerDiv.textContent = message;
            
            boardElement.parentNode.insertBefore(winnerDiv, boardElement.nextSibling);
            
            document.querySelectorAll('.cell').forEach(cell => {
                cell.classList.add('disabled');
                cell.style.cursor = 'not-allowed';
            });
        }

        function showMessage(message, type) {
            const messageElement = document.getElementById('statusMessage');
            messageElement.textContent = message;
            messageElement.className = `status-message status-${type}`;
            messageElement.style.display = 'block';
            
            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 3000);
        }

        function updatePlayerCount(count) {
            const info = document.getElementById('playerInfo');
            if (count === 1) {
                info.textContent += ' - انتظار لاعب آخر...';
            }
        }

        window.onload = () => {
            initGame();

            const channel = window.Echo.channel('game');

            channel.listen('.game.move', function(data) {                
                gameState.board = data.board;
                gameState.currentTurn = data.nextTurn;
                updateBoard(gameState.board);
                updateTurnInfo();
            });

            channel.listen('.game.win', function(data) {
                updateBoard(data.board);
                showWinner(data.winner, data.isDraw);
            });

            channel.listen('.game.reset', function(data) {
                gameState = {
                    playerSymbol: null,
                    currentTurn: null,
                    board: Array(3).fill().map(() => Array(3).fill(null)),
                    gameStatus: 'waiting',
                    isSpectator: false
                };

                createBoard();
                updateBoard(gameState.board);
                document.getElementById('playerInfo').textContent = 'تمت إعادة اللعبة - اختر رمزك من جديد';
                document.getElementById('symbolSelector').style.display = 'block';
                document.querySelector('div.winner-announcement')?.remove();
                updateTurnInfo();
            });

            channel.listen('.game.state', function(data) {
                gameState.gameStatus = data.gameState;
                gameState.currentTurn = data.currentTurn;
                data.symbol = gameState.playerSymbol;

                updateUI(data);
                setTimeout(()=> {
                    updateTurnInfo();
                    updateBoard(gameState.board);
                }, 500);

                if (gameState.gameStatus === 'finished') {
                    showWinner(data.winner, data.isDraw);
                }
            });

            channel.listen('.player.joined', function(data) {
                updatePlayerCount(data.playersCount);
            });
        };
    </script>
</body>
</html>