<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>لعبة X-O - الصفحة الرئيسية</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            max-width: 400px;
            width: 90%;
        }

        .title {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .subtitle {
            font-size: 1.2em;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .name-input {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            text-align: center;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(5px);
        }

        .name-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .name-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
        }

        .buttons-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(45deg, #54a0ff, #2e86de);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .join-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .room-code-input {
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            text-align: center;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(5px);
            text-transform: uppercase;
        }

        .room-code-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .message {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
        }

        .message.success {
            background: rgba(39, 174, 96, 0.8);
            color: white;
        }

        .message.error {
            background: rgba(231, 76, 60, 0.8);
            color: white;
        }

        .loading {
            display: none;
            margin: 10px 0;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">🎮 لعبة X-O</h1>
        <p class="subtitle">العب مع أصدقائك أونلاين</p>
        
        <div class="message" id="message"></div>
        
        <input type="text" 
               id="playerName" 
               class="name-input" 
               placeholder="ادخل اسمك هنا"
               maxlength="20">
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
        </div>
        
        <div class="buttons-container">
            <button class="btn btn-primary" onclick="createRoom()">
                🏠 إنشاء غرفة جديدة
            </button>
            
            <div class="join-section">
                <h3 style="margin-bottom: 15px;">أو انضم لغرفة موجودة</h3>
                <input type="text" 
                       id="roomCode" 
                       class="room-code-input" 
                       placeholder="كود الغرفة"
                       maxlength="8">
                <button class="btn btn-secondary" onclick="joinRoom()">
                    🚪 الانضمام للغرفة
                </button>
            </div>
        </div>
    </div>

    <script>
        // Get room code from URL if exists
        const urlParams = new URLSearchParams(window.location.search);
        const roomCodeFromUrl = urlParams.get('join');
        if (roomCodeFromUrl) {
            document.getElementById('roomCode').value = roomCodeFromUrl;
        }

        function showMessage(text, type) {
            const message = document.getElementById('message');
            message.textContent = text;
            message.className = `message ${type}`;
            message.style.display = 'block';
            
            setTimeout(() => {
                message.style.display = 'none';
            }, 4000);
        }

        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => btn.disabled = show);
        }

        function validateName() {
            const name = document.getElementById('playerName').value.trim();
            if (!name) {
                showMessage('يرجى إدخال اسمك أولاً', 'error');
                return false;
            }
            if (name.length > 20) {
                showMessage('الاسم طويل جداً', 'error');
                return false;
            }
            return true;
        }

        async function createRoom() {
            if (!validateName()) return;

            showLoading(true);
            
            try {
                const response = await fetch('/api/game/create-room', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        name: document.getElementById('playerName').value.trim()
                    })
                });

                const data = await response.json();
                
                if (response.ok) {
                    showMessage(`تم إنشاء الغرفة! كود الغرفة: ${data.room_code}`, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1500);
                } else {
                    showMessage(data.error || 'حدث خطأ في إنشاء الغرفة', 'error');
                }
            } catch (error) {
                showMessage('خطأ في الاتصال بالخادم', 'error');
            } finally {
                showLoading(false);
            }
        }

        async function joinRoom() {
            if (!validateName()) return;
            
            const roomCode = document.getElementById('roomCode').value.trim().toUpperCase();
            if (!roomCode) {
                showMessage('يرجى إدخال كود الغرفة', 'error');
                return;
            }

            showLoading(true);
            
            try {
                const response = await fetch(`/api/game/join-room/${roomCode}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        name: document.getElementById('playerName').value.trim()
                    })
                });

                const data = await response.json();
                
                if (response.ok) {
                    showMessage('تم الانضمام للغرفة بنجاح!', 'success');
                    setTimeout(() => {
                        window.location.href = `/room?join=${roomCode}`;
                    }, 1000);
                } else {
                    showMessage(data.error || 'خطأ في الانضمام للغرفة', 'error');
                }
            } catch (error) {
                showMessage('خطأ في الاتصال بالخادم', 'error');
            } finally {
                showLoading(false);
            }
        }

        // Allow Enter key to work
        document.getElementById('playerName').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                createRoom();
            }
        });

        document.getElementById('roomCode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                joinRoom();
            }
        });

        // Auto-uppercase room code
        document.getElementById('roomCode').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>