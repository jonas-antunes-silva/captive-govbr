<?php
session_start();
date_default_timezone_set('America/Sao_Paulo'); // Defina o fuso horário correto


$redirectUri = 'https://captive.concordia.ifc.edu.br/auth';
$homeUri = 'https://captive.ifc-concordia.edu.br';

$clientId = getenv('CLIENT_ID');
$clientSecret = getenv('CLIENT_SECRET');


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$db = 'sqlite:/opt/captive-govbr/webapp/db/captive.db';

/**
 * Função para obter o MAC a partir do IP
 *
 * @param string $ip
 * @return string
 */
function getMacAddress($ip)
{
    $command = "arp -n " . escapeshellarg($ip);
    $output = shell_exec($command);

    if (preg_match('/([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}/', $output, $matches)) {
        return $matches[0];
    }

    return "MAC não encontrado";
}

switch ($path) {
    case '/':
        if (!isset($_SESSION['user'])) {
            header("Location: /login");
            exit;
        }else{
            ?>
            <!DOCTYPE html>
                <body>
                    <h1>Bem-vindo ao Captive Portal</h1>
                    <p>Você já está conectado.</p>
                    <p><a href="/logout">Sair</a></p>   
                </body>
            <html>
            <?php
        }
        exit;

    case '/login':

        /**
         * Gera o Code Verifier
         *
         * @param int $length
         * @return string
         */
        function generateCodeVerifier($length = 128)
        {
            $randomBytes = random_bytes($length);
            return rtrim(strtr(base64_encode($randomBytes), '+/', '-_'), '=');
        }

        /**
         * Gera o Code Challenge
         *
         * @param string $codeVerifier
         * @return string
         */
        function generateCodeChallenge($codeVerifier)
        {
            return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        }

        $codeVerifier = generateCodeVerifier();
        $_SESSION['code_verifier'] = $codeVerifier;
        $codeChallenge = generateCodeChallenge($codeVerifier);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Wifi visitantes - IFC Concórdia</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f1f1f1ff;
                    color: #333;
                }

                h1 {
                    color: #616161ff;
                    font-size: 24px;
                    margin-bottom: 30px;
                }
                button {
                    background-color: #f1f1f1;
                    color: white;
                    border: none;
                    border-radius: 25px;
                    padding: 10px 20px;
                    font-size: 16px;
                    cursor: pointer;
                    text-align: center;
                    display: inline-flex;
                    align-items: center;
                    border: 2px solid #f1f1f1;
                }


                button span {
                    
                    color: #2864ae;
                    font-weight: bold;
                }

                button:hover {
                    border: 2px solid #2864ae;
                }
                .logo-govbr {
                    height: 30px;
                    margin-left: 10px;
                }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const button = document.getElementById('login-button');
                    button.addEventListener('click', function(event) {
                        event.preventDefault();
                        // chamar url via json para liberar ip do cliente
                        fetch('/liberacao-temporaria', {
                            method: 'POST',
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Redireciona para a URL de autorização com os parâmetros necessários
                                console.log('IP liberado com sucesso:', data.output);
                                window.location.href = 'https://sso.acesso.gov.br/authorize?response_type=code&client_id=<?php echo $clientId; ?>&scope=openid%20profile%20email%20phone%20govbr_confiabilidades&redirect_uri=<?php echo $redirectUri; ?>&state=ABCDEF&nonce=123456&code_challenge=<?php echo $codeChallenge; ?>&code_challenge_method=S256';
                            } else {
                                console.error('Erro ao liberar IP:', data.output);
                            }
                        })
                        .catch(error => {
                            console.error('Erro na requisição:', error);
                        });
                        
                        
                    });
                });
            </script>

        </head>
        <body>
            <div style="width: 50%; margin: auto; margin-top:50px; padding-bottom: 50px; text-align: center; padding-top: 50px; border-radius: 10px; background-color: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1);">                  

                <img src="/img/logo_ifc_vertical_concordia.png" alt="IFC Concórdia" style="height: 300px;">

                <h1>Acesso a rede wifi para visitantes</h1>


                <button
                    id="login-button"
                >
                    <span>Entrar com</span>
                    <img class="logo-govbr" src="/img/govbr-colorido-b.png" alt="gov.br">
                </button>
            </div>
        </body>
        </html>
        <?php
        exit;

    case '/logout':
        $clientIp = $_SERVER['REMOTE_ADDR'];
        $clientMacaddress = getMacAddress($clientIp);

        $output = [];
        $return_var = 0;
        $cmd = escapeshellcmd("sudo /opt/captive-govbr/scripts/ipset_del.sh $clientIp $clientMacaddress");
        exec("$cmd 2>&1", $output, $return_var);

        header("Location: $captiveUrl");
        exit;

    case '/auth':
        if (isset($_SESSION['code_verifier'])) {
            $codeVerifier = $_SESSION['code_verifier'];
        } else {
            echo "<p>Erro: Code Verifier não encontrado na sessão.</p>";
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Wifi visitantes - IFC Concórdia</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f1f1f1ff;
                    color: #333;
                }

                h1 {
                    color: #616161ff;
                    font-size: 24px;
                    margin-bottom: 30px;
                }
                button {
                    background-color: #f1f1f1;
                    color: white;
                    border: none;
                    border-radius: 25px;
                    padding: 10px 20px;
                    font-size: 16px;
                    cursor: pointer;
                    text-align: center;
                    display: inline-flex;
                    align-items: center;
                    border: 2px solid #f1f1f1;
                }


                button span {
                    
                    color: #2864ae;
                    font-weight: bold;
                }

                button:hover {
                    border: 2px solid #2864ae;
                }
                .logo-govbr {
                    height: 30px;
                    margin-left: 10px;
                }
            </style>
        </head>
        <body>
            <div style="width: 50%; margin: auto; margin-top:50px; padding-bottom: 50px; text-align: center; padding-top: 50px; border-radius: 10px; background-color: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1);">                  

                <img src="/img/logo_ifc_vertical_concordia.png" alt="IFC Concórdia" style="height: 300px;">

        <?php
        if (isset($_GET['code'])) {
            $code = $_GET['code'];
            $tokenUrl = "https://sso.acesso.gov.br/token";
            $postData = [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => $clientId,
                'code_verifier' => $codeVerifier,
                'client_secret' => $clientSecret,
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo "<p>Erro na requisição: " . curl_error($ch) . "</p>";
            } else {
                
                $responseData = json_decode($response, true);
                //echo "<p>Resposta do servidor:</p>";
                //echo "<pre>" . htmlspecialchars(print_r($responseData, true)) . "</pre>";
                

                if (isset($responseData['id_token'])) {
                    $idToken = $responseData['id_token'];
                    $idTokenParts = explode('.', $idToken);
                    if (count($idTokenParts) === 3) {
                        $payload = base64_decode($idTokenParts[1]);
                        $payloadData = json_decode($payload, true);


                        if (isset($payloadData['name'])) {

                            //echo "<p>Resposta do servidor:</p>";
                            //echo "<pre>" . htmlspecialchars(print_r($payloadData, true)) . "</pre>";

                            $user = [
                                'name' => $payloadData['name'],
                                'email' => $payloadData['email'],
                                'cpf' => $payloadData['sub'],
                            ];
                            $_SESSION['user'] = $user;

                            $clientIp = $_SERVER['REMOTE_ADDR'];
                            $clientMacAddress = getMacAddress($clientIp);

                            $output = [];
                            $return_var = 0;
                            $cmd = escapeshellcmd("sudo /opt/captive-govbr/scripts/ipset_add.sh $clientIp $clientMacAddress");
                            
                            exec("$cmd 2>&1", $output, $return_var);

                            if(!$return_var){
                                // O comando ipset retornou sucesso, então vamos registar os dados do usuário no banco de dados
                                try {
                                    $pdo = new PDO($db);
                                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                    //echo "Conexão bem-sucedida com SQLite!";
                                } catch (PDOException $e) {
                                    echo "Erro na conexão: " . $e->getMessage();
                                }
                                
                                $timestamp = date('Y-m-d H:i:s'); // Gera o timestamp no formato ISO 8601

                                $stmt = $pdo->prepare("INSERT INTO conexoes (nome, email, cpf, ip, macaddress, data_conexao) VALUES (:nome, :email, :cpf, :ip, :macaddress, :timestamp)");
                                $stmt->execute([
                                    ':nome' => $user['name'],
                                    ':email' => $user['email'],
                                    ':cpf' => $user['cpf'],
                                    ':ip' => $clientIp,
                                    ':macaddress' => $clientMacAddress,
                                    ':timestamp' => $timestamp
                                ]);

                                // Consultar a tabela conexoes_ativas para verificar se já existe um registro para o IP
                                $stmt = $pdo->prepare("SELECT * FROM conexoes_ativas WHERE ip = :ip");
                                $stmt->execute([':ip' => $clientIp]);
                                $conexaoAtiva = $stmt->fetch(PDO::FETCH_ASSOC); 
                                if (!$conexaoAtiva) {
                                    // Se não existir, insere um novo registro

                                    $stmt = $pdo->prepare("INSERT INTO conexoes_ativas (ip, macaddress, data_conexao, data_ultima_atividade) VALUES (:ip, :macaddress, :timestamp, :ultima_atividade)");
                                    $stmt->execute([
                                        ':ip' => $clientIp,
                                        ':macaddress' => $clientMacAddress,
                                        ':timestamp' => $timestamp,
                                        ':ultima_atividade' => $timestamp
                                    ]);
                                } else {
                                    // Se já existir, atualiza o registro
                                    $stmt = $pdo->prepare("UPDATE conexoes_ativas SET macaddress = :macaddress, data_ultima_atividade = :timestamp WHERE ip = :ip");
                                    $stmt->execute([
                                        ':macaddress' => $clientMacAddress,
                                        ':ip' => $clientIp,
                                        ':timestamp' => $timestamp
                                    ]);
                                }
                                
                                //echo date_default_timezone_get();
                                //echo "<br>Data e hora atual: " . date('Y-m-d H:i:s');
                                $timestamp = date('Y-m-d H:i:s');
                                //echo "<br>variável timestamp: " . $timestamp;

                                echo "<h2>Bem-vindo <strong>{$user['name']}</strong></h2>";
                                echo "<p>Seu acesso a rede está liberado.</p>";
                                //echo "<a href='/logout'><button style='padding: 10px 20px; font-size: 16px;'>Sair</button></a>";
                                
                            }
                            /*
                            echo "<pre>";
                            echo "Comando: $cmd\n";
                            echo "Código de retorno: $return_var\n";
                            echo "Saída:\n" . implode("\n", $output);
                            echo "</pre>";
                            */

                        }
                    }
                }

                if (isset($responseData['access_token'])) {
                    $accessToken = $responseData['access_token'];
                    $tokenParts = explode('.', $accessToken);
                    if (count($tokenParts) === 3) {
                        $payload = base64_decode($tokenParts[1]);
                        $payloadData = json_decode($payload, true);
                        /*
                        if (isset($payloadData['sub'])) {
                            $apiUrl = "https://api.staging.acesso.gov.br/confiabilidades/v3/contas/" . $payloadData['sub'] . "/niveis?response-type=ids";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $apiUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                "Authorization: Bearer $accessToken"
                            ]);
                            $apiResponse = curl_exec($ch);

                            if (curl_errno($ch)) {
                                echo "<p>Erro na requisição à API: " . curl_error($ch) . "</p>";
                            } else {
                                $apiData = json_decode($apiResponse, true);
                                echo "<p>Níveis de confiabilidade: (3 - ouro, 2 - prata, 1 - bronze):</p>";
                                echo "<pre>" . htmlspecialchars(print_r($apiData, true)) . "</pre>";
                            }
                            curl_close($ch);
                        }*/
                    } else {
                        echo "<p>O Access Token não é um JWT válido.</p>";
                    }
                } else {
                    echo "<p>Access Token não foi recebido.</p>";
                }
            }
            curl_close($ch);
        } else {
            echo "<p>Nenhum código foi recebido.</p>";
        }
        ?>
            </div>
        </body>
        </html>
        <?php
        exit;

    case '/liberacao-temporaria':
        $clientIp = $_SERVER['REMOTE_ADDR'];
        $clientMacaddress = getMacAddress($clientIp);
        $output = [];
        $return_var = 0;
        $sucesso = false;
        $mensagem_retorno = "";
        $cmd = escapeshellcmd("sudo /opt/captive-govbr/scripts/ipset_add_temp.sh $clientIp $clientMacaddress");
        exec("$cmd 2>&1", $output, $return_var);

        if (!$return_var) {
            $sucesso = true;
            $mensagem_retorno = $output;
        } else {
            $sucesso = false;
            $mensagem_retorno = $output;
        }

        echo json_encode([
            'success' => $sucesso,
            'output' => $mensagem_retorno
        ]);
        exit;

    case '/.well-known/captive-portal':
    case '/captive-portal/api':
        header('Content-Type: application/json');
        if (!empty($_SESSION['user'])) {
            echo json_encode(['captive' => false, 'user-portal-url' => null]);
        } else {
            echo json_encode(['captive' => true, 'user-portal-url' => $captiveUrl]);
        }
        exit;

    case '/captiveportal/generate_204':

        // Verificar se o cliente consta na lista de clientes conectados, caso negativo faça o redirecionamento abaixo
        // Define o código de status HTTP 301
        header("HTTP/1.1 301 Moved Permanently");
        // Define a URL para onde o cliente será redirecionado
        header("Location: http://captive.concordia.ifc.edu.br/login");
        // Encerra a execução para garantir que o redirecionamento ocorra
        exit();
        exit;

    case '/redirect':
    case '/canonical.html':
    case '/generate_204':
    case '/ncsi.txt':
    case '/connecttest.txt':
    case '/hotspot-detect.html':
    case '/library/test/success.html':
    case '/captiveportal.html':
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: /login");
        exit;

    default:
        http_response_code(404);
        echo "404 - Página não encontrada.";
        exit;
}
