<?php

/**
 * �����ʼ�gearman���ýӿ�
 * gearman������: sndmail
 * gearman���ݰ�: json�����ʽ,��: ["jiaofuyou@qq.com", "�ʼ�����", "<h1>�ʼ�����(֧��HTML��ʽ)</h1>"]
 */

require_once('email.class.php');

# Create our worker object.
$gmworker= new GearmanWorker();

# Add default server (localhost).
$gmworker->addServer("127.0.0.1", 4730);

# Register function "reverse" with the server. Change the worker function to
# "reverse_fn_fast" for a faster worker with no output.
$gmworker->addFunction("sndmail", "sndmail");

echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] ' . "Waiting for job ...\n";
while($gmworker->work())
{
  if ($gmworker->returnCode() != GEARMAN_SUCCESS)
  {
    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] ' . "return_code: " . $gmworker->returnCode() . "\n";
    break;
  }
}

function sndmail($job)
{
    $workload= $job->workload();
    $workload_size= $job->workloadSize();

    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").']'." < " . $workload . "\n";
    $json_req = json_decode($workload, true);
    $json_req[1] = urldecode($json_req[1]);
    $json_req[2] = urldecode($json_req[2]);

    //##########################################
    $smtpserver = "smtp.qq.com";//SMTP������
    $smtpserverport = 25;//SMTP�������˿�
    $smtpusermail = "elitelmonit@qq.com";//SMTP���������û�����
    $smtpuser = "elitelmonit";//SMTP���������û��ʺ�
    $smtppass = "xtjsxtj302111";//SMTP���������û�����
    $smtpemailto = $json_req[0];//���͸�˭
    $mailsubject = $json_req[1];//�ʼ�����
    $mailbody = $json_req[2];//�ʼ�����
    $mailtype = "HTML";//�ʼ���ʽ��HTML/TXT��,TXTΪ�ı��ʼ�
    ##########################################

    $smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//�������һ��true�Ǳ�ʾʹ�������֤,����ʹ�������֤.
    $smtp->debug = false;//�Ƿ���ʾ���͵ĵ�����Ϣ

    $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);
    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").']'." > " . 'sendmail ok' . "\n";

    $result= "";

    # Return what we want to send back to the client.
    return $result;
}
