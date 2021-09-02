<?PHP
header('Content-Type: text/html; charset=utf-8');
error_reporting(0);

function getip(){
    $file = fopen("ips.txt",'r');
    $content = array();
    if(!$file){
        echo 'file open fail';
    }else{
        $i = 0;
        while (!feof($file)){
            $content[$i] = mb_convert_encoding(fgets($file),"UTF-8","GBK,ASCII,ANSI,UTF-8");
            $i++ ;
        }
        fclose($file);
        $content = array_filter($content);
        return $content;
    }
}

function ping($host, $timeout = 1) {
    $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
    $socket = socket_create(AF_INET, SOCK_RAW, 1);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
    socket_connect($socket, $host, null);
    $ts = microtime(true);
    socket_send($socket, $package, strLen($package), 0);
    if (socket_read($socket, 255))
        $result = microtime(true) - $ts;
    else
        $result = false;
    socket_close($socket);
    return $result;
}

function get_ip_pre($ip){
    $num = explode('.',$ip);
    return $num[0].".".$num[1].".".$num[2].".";
}



$ips = getip();

foreach($ips as $ip){
    $res = ping($ip,1);
    if($res){
        echo "$ip is online\n";file_put_contents("result.txt","$ip is online\n",FILE_APPEND);
    }
    else{
        echo "$ip sense shutdown\n";file_put_contents("result.txt","$ip sense shutdown\n",FILE_APPEND);
    }
    $ip = $ippre;
    ob_flush();
    flush();
}
?>