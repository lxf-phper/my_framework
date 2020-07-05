<?php
namespace core;

class Websocket
{
    protected $config = []; //配置
    protected $sockets = [];
    protected $master;

    public function __construct($options = [])
    {
        $this->config = Config::get('websocket');
        if (!empty($options)) {
            $this->config = array_merge($this->config, $options);
        }

        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        // 设置IP和端口重用,在重启服务器后能重新使用此端口;
        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->master, $this->config['address'], $this->config['port']);
        socket_listen($this->master, $this->config['listen_socket_num']);
        $this->sockets[intval($this->master)] = ['resource' => $this->master];
    }

    public function run()
    {
        while (true) {
            $this->doServer();
            /*$buffer = socket_read($msg, 8192, PHP_BINARY_READ);
            if (!$this->hand) {
                $this->handshake($msg, $buffer);
            } else {
                $msg2 = $this->undecode("success成功\n");
                socket_write($msg, $msg2, strlen($msg2));
                //接收数据并打印
                //halt($this->decode($buffer));
                //socket_write($msg, 'test\n');
            }*/
        }
    }

    public function doServer()
    {
        $this->writeLog(json_encode($this->sockets));
        $sockets = array_column($this->sockets, 'resource');
        // 阻塞进程，直到有socket接入
        $read_num = socket_select($sockets, $write, $except, null);
        if ($read_num == false) {
            $errorMsg = socket_last_error();
            $this->writeLog($errorMsg);
            return null;
        }
        foreach ($sockets as $key=>$socket) {
            if ($socket == $this->master) {
                $msg = socket_accept($socket) or die("socket_accept() failed: reason: " . socket_strerror(socket_last_error()) . "/n");
                if ($msg != false) {
                    $this->connect($msg);
                    $this->writeLog('accept socket: '.$msg);
                    continue;
                } else {
                    $errorMsg = socket_strerror(socket_last_error());
                    $this->writeLog($errorMsg);
                    continue;
                }
            } else {
                $buffer = socket_read($socket, 8192, PHP_BINARY_READ);
                if ($this->sockets[intval($socket)]['handshake'] == false) {
                    $this->handshake($socket, $buffer);
                    continue;
                } else {
                    //$broadcastMsg = $this->decode($socket, $buffer);
                    $broadcastMsg = $this->handleMsg($socket, $buffer);
                    //halt($broadcastMsg);
                    //$broadcastMsg = json_encode($broadcastMsg,true);
                    //$this->writeLog('receive data:'.$broadcastMsg);
                    //if (!empty($broadcastMsg)) {
                        $this->broadcast($broadcastMsg);
                    //}
                    continue;
                    //socket_write($socket, $broadcastMsg, strlen($broadcastMsg));
                    //接收数据并打印
                    //halt($this->decode($buffer));
                    //socket_write($msg, 'test\n');
                }
            }
        }
    }

    /**
     * 广播信息
     * @param $msg
     */
    public function broadcast($msg)
    {
        foreach ($this->sockets as $key=>$socket) {
            if (isset($socket['handshake'])) {
                if (!empty($socket['handshake'])) {
                    socket_write($socket['resource'], $msg, strlen($msg));
                }
            }
        }
    }

    //处理客户端的消息
    public function handleMsg($socket, $buffer)
    {
        $msg = $this->decode($socket, $buffer);
//        $broadcastMsg = json_encode($broadcastMsg,true);
//        $this->writeLog('receive data:'.$broadcastMsg);
        $broadcastMsg = '';
        $type = $msg['type'];
        $content = isset($msg['content']) ? $msg['content'] : '';
        unset($msg['type']);
        switch ($type) {
            case 'login' :
                $this->sockets[intval($socket)]['username'] = $content['user_name'];
                $writeMsg = '';
                foreach ($this->sockets[intval($socket)] as $key=>$val) {
                    $writeMsg .= $key.': '.$val.' | ';
                }
                $this->writeLog($writeMsg);
                $msg = [
                    'type' => $type,
                    'content' => [
                        'user_name' => $content['user_name'],
                        'user_list' => array_column($this->sockets,'username'),
                    ]
                ];
                $broadcastMsg = json_encode($msg);
                break;
            case 'draw' :
                $msg = [
                    'type' => $type,
                    'content' => $content,
                ];
                $broadcastMsg = json_encode($msg);
                $this->writeLog('receive data:'.$broadcastMsg);
                break;
            case 'clear' :
                $msg = [
                    'type' => $type
                ];
                $broadcastMsg = json_encode($msg);
                $this->writeLog('clear sketchpad');
                break;
            case 'dialog' :
                $msg = [
                    'type' => $type,
                    'content' => $content
                ];
                $broadcastMsg = json_encode($msg);
                $this->writeLog('receive data:'.$broadcastMsg);
                break;
        }
        return $this->undecode($broadcastMsg);
    }

    /**
     * 公共握手方法握手
     * @param $socket
     * @param $buffer
     * @return bool
     */
    public function handshake($socket,$buffer)
    {
        $key = substr($buffer,strpos($buffer, 'Sec-WebSocket-Key:')+18);
        $key = trim(substr($key, 0, strpos($key,"\r\n")));
        $key .= '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
        $upgrade_key = base64_encode(sha1($key,true));
        $response = "HTTP/1.1 101 Switching Protocols\r\n"
                    . "Upgrade: websocket\r\n"
                    . "Connection: Upgrade\r\n"
                    . "Sec-WebSocket-Accept: " . $upgrade_key . "\r\n\r\n";
        socket_write($socket, $response, strlen($response)); // 向socket里写入升级信息
        $this->sockets[intval($socket)]['handshake'] = true; //修改当前socket的握手状态
        // 向客户端发送握手成功信息，触发客户端发送用户信息
        $msg = [
            'type' => 'handshake',
        ];
        $msg = json_encode($msg);
        $msg = $this->undecode($msg);
        socket_write($socket, $msg, strlen($msg));
        $this->writeLog('handshake success: '.$socket);
        return true;
    }

    // 解析数据帧(1Byte=8bit)
    // 一个英文字母（不分大小写）占一个字节的空间，一个中文汉字占两个字节的空间。一个二进制数字序列，在计算机中作为一个数字单元，一般为8位二进制数，换算为十进制。最小值0，最大值255。如一个ASCII码就是一个字节。
    public function decode($socket, $buffer)  {
        $opcode = ord(substr($buffer, 0, 1)) & 0x0F; //opcode标识数据类型,如果收到一个未知的操作码，接收端点必须_失败WebSocket连接
        $payloadlen = ord(substr($buffer, 1, 1)) & 0x7F; //PayloadLen表示数据部分的长度
        $ismask = (ord(substr($buffer, 1, 1)) & 0x80) >> 7; //MASK标识这个数据帧的数据是否使用掩码，定义payload数据是否进行了掩码处理，如果是1表示进行了掩码处理。Masking-key域的数据即是掩码密钥，用于解码PayloadData。客户端发出的数据帧需要进行掩码处理，所以此位是1。
        $maskkey = null;
        $oridata = null;
        $decodedata = null;

        // 关闭socket连接
        if ($ismask != 1 || $opcode == 0x8) {
            $this->disconnect($socket);
            return null;
        }

        if ($payloadlen <= 125 && $payloadlen >= 0) {
            $maskkey = substr($buffer, 2, 4);
            $oridata = substr($buffer, 6);
        } else if ($payloadlen == 126) {
            $maskkey = substr($buffer, 4, 4);
            $oridata = substr($buffer, 8);
        } else if ($payloadlen == 127) {
            $maskkey = substr($buffer, 10, 4);
            $oridata = substr($buffer, 14);
        }

        $len = strlen($oridata);
        for ($i = 0; $i < $len; $i++) {
            $decodedata .= $oridata[$i] ^ $maskkey[$i % 4];
        }
        return json_decode($decodedata, true);
    }

    // 把发送信息组成websocket数据帧
    public function undecode($msg="", $opcode = 0x1)
    {
        //control bit, default is 0x1(text data)
        $firstByte = 0x80 | $opcode;
        $encodedata = null;
        $len = strlen($msg);

        if (0 <= $len && $len <= 125)
            $encodedata = chr(0x81) . chr($len) . $msg;
        else if (126 <= $len && $len <= 0xFFFF)
        {
            $low = $len & 0x00FF;
            $high = ($len & 0xFF00) >> 8;
            $encodedata = chr($firstByte) . chr(0x7E) . chr($high) . chr($low) . $msg;
        }

        return $encodedata;
    }

    // 记录socket连接
    public function connect($msg)
    {
        $socket = [
            'resource' => $msg,
            'username' => '',
            'handshake' => false
        ];
        $this->sockets[intval($socket['resource'])] = $socket;
        $writeMsg = '';
        foreach ($socket as $key=>$val) {
            $writeMsg .= $key.': '.$val.' | ';
        }
        //array_push($this->sockets,$socket);
        $this->writeLog($writeMsg);
    }

    // 关闭socket连接
    public function disconnect($socket)
    {
        unset($this->sockets[intval($socket['resource'])]);
        $this->writeLog('close socket:'.$socket);
        socket_close($socket);
    }

    // 记录日志
    public function writeLog($msg)
    {
        if (is_array($msg)) {
            return ;
        }
        $message = '[ '.date('Y-m-d H:i:s')." ]  ".$msg."\n";
        // 路径
        $path = str_replace('\\', '/', '../runtime/drawLog/'.date('Ym').'/');
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }
        $filePath = $path.date('Y-m-d').'-log.txt';
        file_put_contents($filePath, $message, FILE_APPEND);
    }
}