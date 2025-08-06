<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="assets/website images/logo.png">
    <link rel="stylesheet" href="assets/styles/styles.css">
    <title>CFBPicks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.3/echarts.min.js"></script>
    <script src="assets/scripts/animation.js"></script>
</head>
<body>
    <div id="waves" class="chart-container"></div>
    <div id="title" class="chart-container"></div>
    <form id="login-form-container" method="POST">
        <div class="error-message" id="error-message"></div>
        <input type="text" name="username" class="form-input" placeholder="Username" required>
        <input type="password" name="password" class="form-input" placeholder="Password" required>
        <button type="submit" class="login-button" id="submit-button">Login</button>
    </form>
    <script>
    window.onload = () => {
        launchAnimation();

        const titleDom = document.getElementById('title');
        if (!titleDom) { console.error("Fatal: Title container not found!"); return; }
        const titleChart = echarts.init(titleDom);
        const titleOption = {
            graphic: {
                elements: [{
                    id: 'main-title',
                    type: 'text',
                    left: 'center',
                    top: 'center',
                    style: {
                        text: 'CFBPicks 3.0',
                        fontSize: window.innerWidth >= 768 ? 120 : 50,
                        fontWeight: 'bold',
                        lineDash: [0, 200],
                        lineDashOffset: 0,
                        fill: 'transparent',
                        stroke: '#fff',
                        lineWidth: 2
                    },
                    keyframeAnimation: {
                        duration: 5000,
                        loop: false,
                        delay: 500,
                        keyframes: [{
                            percent: 0.7,
                            style: {
                                fill: 'transparent',
                                lineDashOffset: -200,
                                lineDash: [200, 0]
                            }
                        }, {
                            percent: 1,
                            style: {
                                fill: '#fff'
                            }
                        }]
                    }
                }]
            }
        };
        titleChart.setOption(titleOption);
        
        setTimeout(() => {
            titleDom.classList.add("slide-up");
            
            const form = document.getElementById('login-form-container');
            form.style.display = 'flex';
            setTimeout(() => {
                form.style.opacity = '1';
            }, 500);
        }, 5500);

        const loginForm = document.getElementById('login-form-container');
        const submitButton = document.getElementById('submit-button');
        const errorMessageDiv = document.getElementById('error-message');
        const usernameInput = loginForm.querySelector('input[name="username"]');
        const passwordInput = loginForm.querySelector('input[name="password"]');

        function resetToLoginState() {
            if (submitButton.textContent === 'Create Account') {
                submitButton.textContent = 'Login';
                errorMessageDiv.style.display = 'none';
            }
        }

        usernameInput.addEventListener('input', resetToLoginState);
        passwordInput.addEventListener('input', resetToLoginState);

        loginForm.addEventListener('submit', function(event) {
            event.preventDefault(); 
            errorMessageDiv.style.display = 'none';

            const formData = new FormData(this);
            const submissionType = submitButton.textContent === 'Login' ? 'login' : 'register';
            formData.append('type', submissionType);

            fetch('server/handle_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php';
                } else {
                    errorMessageDiv.textContent = data.message || 'An unknown error occurred.';
                    errorMessageDiv.style.display = 'block';

                    if (data.error === 'username_not_found') {
                        submitButton.textContent = 'Create Account';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessageDiv.textContent = 'Could not connect to the server.';
                errorMessageDiv.style.display = 'block';
            });
        });

        window.onresize = () => {
            wavesChart.resize();
            titleChart.resize();
            titleChart.setOption({
                graphic: {
                    elements: [{
                        id: 'main-title',
                        style: {
                           fontSize: window.innerWidth >= 768 ? 120 : 50,
                        }
                    }]
                }
            });
            wavesChart.setOption({
                graphic: {
                    elements: createElements(wavesChart, noise, config, mode)
                }
            });
        };
    };
    </script>
</body>
</html>
