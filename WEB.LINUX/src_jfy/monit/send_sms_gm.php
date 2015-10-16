<?php

/**
 * ���Ͷ���gearman���ýӿ�
 * gearman������: sndsms
 * gearman���ݰ�: json�����ʽ,��: ["13602447301", "��֤��Ϊ123456"],��һ��Ϊ�ֻ�����,�ڶ���Ϊ��������
 */

# Create our worker object.
$gmworker= new GearmanWorker();

echo $argc;
if ($argc==4){
    $gmworker->addServer($argv[2], $argv[3]);
} else 
if ($argc==2){
    # Add default server (localhost).
    $gmworker->addServer();
} else {
    die("argv param error,exit!\n");
}

# Register function "reverse" with the server. Change the worker function to
# "reverse_fn_fast" for a faster worker with no output.
$gmworker->addFunction("sndsms", "sndsms");

echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] ' . "Waiting for job ...\n";
while($gmworker->work())
{
  if ($gmworker->returnCode() != GEARMAN_SUCCESS)
  {
    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] ' . "return_code: " . $gmworker->returnCode() . "\n";
    break;
  }
}

function sndsms($job)
{
  $workload= $job->workload();
  $workload_size= $job->workloadSize();

  echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").']'." < " . $workload . "\n";

  $json_req = json_decode(iconv("gbk", "UTF-8", $workload), true); //ֱ��json_decode gbk���ݻ��ɿգ�������ת��utf-8
  $mobileno = $json_req[0];
  if ( $mobileno[0] != '1' ) {
    $content = iconv("UTF-8", "gbk", $json_req[1]);
    sndsms_cws($mobileno, $content);
    return "";
  }

  $content = urlencode($json_req[1]);
  $url = "http://59.42.210.216/wollar/app_sendsms/Sendsms.php?mobile=" . $mobileno . "&content=" . $content;

  //��ʼ��
  $ch = curl_init();

  echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] '."> " . $url . "\n";

  //����ѡ�����URL
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);

  //ִ�в���ȡHTML�ĵ�����
  $output = curl_exec($ch);
  if ( $output == '0' ) {
    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] '."< " . "sndsms ok!\n";
  } else {
    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] '."< " . "sndsms err," . $output . "!\n";
  }

  //�ͷ�curl���
  curl_close($ch);

  $result= "";

  # Return what we want to send back to the client.
  return $result;
}

//======================================================================================================
$seqno=0;
function get_seqno()
{
  global $seqno;
  if ($seqno>999999999) $seqno=0; else $seqno++;
  return $seqno;
}

function _send_cmd($socket, $cmd, $reply, $param)
{
  $in = sprintf("%c%-10d%s",ord($cmd),get_seqno(),$param); //CMD
  //echo '$in'."=$in\n";

  //echo "write socket ...\n";
  $len=socket_write($socket, $in, strlen($in));
  if ( $len === false ) {
      echo 'socket_write failed: ' . socket_strerror($socket) . "\n";
      return false;
  }
  //echo "socket_write ok!\n";

  //echo "read socket ...\n";
  $out = socket_read($socket, 12);
  if ( $out === false ) {
      echo 'socket_read failed: ' . socket_strerror($socket) . "\n";
      return false;
  }
  //echo '$out'."=$out\n";

  if ($out[0] != $reply) {
    echo "_send_cmd($cmd).read out[0]=$out[0] <> $reply!\n";
    return false;
  }
  if ($out[11] != '0') {
    $sms_error_msg = array(
      '0' => 'SUCCESS_�ɹ�',
      '1' => 'INVALIDUSER_�û�������',
      '2' => 'INVALIDPASS_�û��������',
      '3' => 'INVALIDADDR_IP��ַ����',
      '4' => 'INVALIDBIND_δ�ɹ�BIND',
      '5' => 'INVALIDMOC_Դ�������',
      '6' => 'INVALIDMTC_Ŀ��������',
      '7' => 'INVALIDTEXT_���������쳣�����糤�Ȳ���',
      '8' => 'INVALIDSYS_����ϵͳ�����ã���ʱ�����˶���',
      '9' => 'UNKNOWN_����δ֪����',
    );
    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] '."_send_cmd($cmd).read out[11]={$out[11]} <> 0, ({$sms_error_msg[$out[11]]})!\n";
    return false;
  }
  //echo "_send_cmd($cmd) ok\n";

  return true;
}

function _sms_bind($socket)
{
  $buf = sprintf("%-10s%-10s","SmsAgent","elitel"); //BIND

  return _send_cmd($socket,'1','2',$buf);
}

function _sms_send($socket,$mobileno, $content)
{
  $buf = sprintf("%-20s%-20s%c%-254s"," ",$mobileno,ord('2'),$content); //SEND_SM
  if ( !_send_cmd($socket,'3','4',$buf) ) return false;
  return true;
}

function _sms_unbind($socket)
{
  return _send_cmd($socket,'5','6','');
}

//------------------------------------------------------------------------------------------------------
function sndsms_cws($mobileno, $content)
{
    $service_port = 5556;
    $address = '172.16.18.12';

    //���� TCP/IP socket
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket == false) {
      echo 'socket_create failed: ' . socket_strerror(socket_last_error()) . "\n";
      return false;
    } else {
      //echo "socket create success!\n";
    }
    socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>5, "usec"=>0 ));
    socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,array("sec"=>5, "usec"=>0 ));

    $result = @socket_connect($socket, $address, $service_port);
    if ($result == false) {
      echo 'socket_connect failed: ('.socket_last_error().') ' . socket_strerror(socket_last_error()) . "\n";
      return false;
    } else {
      //echo "socket connect success!\n";
    }

    if ( !_sms_bind($socket) ) return false;
    if ( !_sms_send($socket,$mobileno, $content) ) return false;
    _sms_unbind($socket);
    socket_close($socket);

    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] '."> sndsms ok($mobileno,$content)!\n";

    return true;
}
