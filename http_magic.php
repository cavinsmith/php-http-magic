<?
#
# HTTP_MAGIC.PHP v.30/09/10   v3
# функции для работы с протоколом HTTP/SSL.
#
#
#    http($request,$port=80,$ssl=false,$read=true,$method='plain',$sleep=false) # общая функция для запросов.
#
#         $addr - web address (www.mail.com) or CONNCETION RESOURCE
#         $request - full http request
#         $port - server port
#         $ssl - true/false (ssl or plain http?)
#         $read - true/false/header (read answer after request/no/read header)
#
#    cookies($header,$cookies=array()) # Экстракт и синхронизация кукис
#
#         $header - свежеполученный хидер
#         $cookies - массив с куками извлеченными ранее при помощи cookies()
#         для использования кукисов в запросах используйте $cookie=implode("\r\n",$cookies);
#
#
#    header_parser($header) # парсит и красиво возвращает хидер
#
#

function http($request,$port=80,$ssl=false,$read=true) # общая функция.
{

        $addr=explode('host: ',strtolower($request),2);
        $addr=explode("\n",$addr[1],2);
        $addr=trim($addr[0]);

        $f=http_open($addr,$port,$ssl); # новый
        if($f==false)return $f;

        @stream_set_blocking($f,1);

        fwrite($f, $request); # отправляем запрос

        if($read==false)return;
        echo '<pre>';
        $data='';
        while(true)
        {
                $line=fgets($f);
                $data.=$line;
                if($line==="\r\n")
                {
                        $method=http_method($data);
                        break;
                }
        }

        if($read==='header')return $data;

        if($method==='chunked') # читаем методом CHUNKED
        {
                $data.=http_read_chunked($f);
        }
        else
        {
                $data.= http_read_all($f); # читаем обычным методом
        }
        fclose($f);
        return $data;
}


function http_method($header)
{
        $header=header_parser($header);
        $method=strtolower($header['transfer-encoding']);
        if($method!='chunked')return 'plain'; else return 'chunked';
}

function http_read_all($f) # читает документ целиком в обычном режиме. для ф-ии HTTP
{
        $data='';
        while (!feof($f))$data.=fgets($f, 16384);
        return $data;
}

function http_open($addr,$port=80,$ssl=false) # функция открытия соединения. для ф-ии HTTP
{
        if($ssl)$addr='ssl://' . $addr;
        $f=@fsockopen($addr, $port, $errno, $errstr, 5);
        return $f;
}

function http_read_chunked($f)  # адекватное чтение в случае режима CHUNKED (нужно обязательно при чтении картинок)
{
        $length=fgets($f);
        $length=hexdec($length);

        while(true)
        {
                if ($length < 1)break;
                $x='';
                while(strlen($x)<$length)
                {
                        $b=fread($f, $length-strlen($x));
                        if($b=='')return $data;
                        $x.=$b;
                }
                $data.= $x;
                fgets($f);
                $length = rtrim(fgets($f));
                $length = hexdec($length);
        }
        return $data;
}


function header_parser($header) # Парсит хидеры и достаёт из них всё ценное.
{
        $headers=explode("\r\n\r\n",$header,2);
        $headers=explode("\r\n",trim($headers[0]));
        $http=trim(array_shift($headers));
        $harray=array(); #all headers
        $cookies=array(); #cookies
        foreach($headers as $i=>$header)
        {
                $header=explode(':',trim($header),2);
                $type=strtolower($header[0]);
                $value=trim($header[1]);
                if($type=='set-cookie')
                {
                        $value=explode(';',$value,2);
                        $harray['set-cookie'][]=$value[0];
                }
                else
                {
                        $harray[$type]=$value;
                }
        }
        return $harray;
}

function extract_charset($header) # из хидера вытаскивает название кодировки
{
        $charset=explode('charset=',$header,2);
        $charset=explode("\r\n",$charset[1],2);
        $charset=explode(';',$charset[0],2);
        $charset=$charset[0];
        return $charset;
}

function cookies($header,$cookies=array()) # Возвращает массив кук. элементы вида cookeis[0]="name=value"; Может совмещать куки с массивом cookies, такого же вида.
{
        $headers=explode("\r\n\r\n",$header,2);
        $headers=explode("\r\n",trim($headers[0]));
        $http=trim(array_pop($headers));
        foreach($headers as $i=>$header)
        {
                $header=explode(':',trim($header),2);
                $type=strtolower($header[0]);
                $value=trim($header[1]);
                if($type=='set-cookie')
                {
                        $value=explode(';',$value,2);
                        $cookies[]=$value[0];
                }
        }
        return cl_cookies($cookies);
}

function cl_cookies($cookies) # "чистит" массив кук. Вспомогательная для cookies
{
        $cl=array();
        foreach($cookies as $c)
        {
                $c=explode('=',$c,2);
                $cl[$c[0]]=$c[1];
        }
        $cookies=array();
        foreach($cl as $name=>$c)
        {
                $cookies[]=$name . '=' . $c;
        }
        return $cookies;

}






?>
