<?php

class job
{
    public $funcname;
    public $data;

    public function __construct($funcname,$data)
    {
        $this->funcname=$funcname;
        $this->data=$data;
    }

    function workload()
    {
        return $this->data;
    }

    function functionName()
    {
        return $this->funcname;
    }
}

/**
 * ���������뷵�ز���json_encode
 * @param string $errcode M01/M02/MMM
 * @param string/array $msg
 * @return string json�ַ���,{'error_code'=>'000', 'error_msg'=>'XXX', ...}
 */
function assign_result($errcode, $msg)
{
    if ( is_array($msg) ) {
        $result=array('error_code'=>$errcode)+$msg;
    } else {
        $result['error_code']=$errcode;
        $result['error_msg']=$msg;
    }

    return encode_json($result);
}

/**
 * ���ص�������
 * @param boolean $return
 * @param string $msg
 * @return array('return'=>true/false, 'result'=>XXXX)
 */
function assign_return($return, $msg)
{
    $ret['return']=$return;
    $ret['result']=$msg;

    return $ret;
}

/**
 * ����������false
 * @param string $errcode
 * @param string $msg
 * @return array('return'=>false, 'result'=>XXXX)
 */
function assign_error($errcode, $msg)
{
    return assign_return(false, assign_result($errcode, $msg));
}

/**
 * ����������true
 * @param string $errcode
 * @param string $msg
 * @return array('return'=>true, 'result'=>XXXX)
 */
function assign_ok($errcode, $msg)
{
    return assign_return(true, assign_result($errcode, $msg));
}

/**
 * �Ϸ��Լ�飨��δ��Ч��
 * @param type $id
 * @param type $token
 * @return boolean
 */
function check_valid($id, $token)
{
    return true;
}

/**
 * ��mysqlд������־
 * @global type $db
 * @param string $funcname ���ú�����
 * @param string $keykey �����ؼ���Ϣ���磺���룬userid
 * @param string $jsonreq json�������
 * @param string $jsonrep jsonӦ�����
 * @return boolean true/false
 */
function write_oplog($funcname, $keykey, $jsonreq, $jsonrep)
{
    global $db;
    return $db->insert_one("insert into oplog values(0, now(),
                           '$funcname', '$keykey', '$jsonreq', '$jsonrep')",false);
}

/**
 * ��ͨ��������Ԥ�������Ƚ�$job����������array
 * @param job $job
 * @return array=('return'=>true/false,'result'=>'XXX','keykey'=>'XXX',...}
 */
function apply_with_common($job)
{
    $request=$job->workload();
    $funcname=$job->functionName();
    Log::prn_log(NOTICE, 'request:'.$funcname.':'.$request);

    $req=json_decode($request, true);
    if ( $req == NULL ) {
        Log::prn_log(ERROR, 'param error, json_decode is NULL!');
        return assign_result('MMM', 'param error!');
    }
    $ret=$funcname($req);
    Log::prn_log(NOTICE, 'result:'.$ret['result']);

    return $ret['result'];
}

/**
 * д��־�ຯ������Ԥ�������Ƚ�$job����������array�����ý������дoplog
 * @param job $job
 * @return array=('return'=>true/false,'result'=>'XXX','keykey'=>'XXX',...}
 */
function apply_with_log($job)
{
    $request=$job->workload();
    $funcname=$job->functionName();
    Log::prn_log(NOTICE, 'request:'.$funcname.':'.$request);

    $req=json_decode($request, true);
    if ( $req == NULL ) {
        Log::prn_log(ERROR, 'param error, json_decode is NULL!');
        return assign_result('MMM', 'param error!');
    }
    $ret=$funcname($req);
    Log::prn_log(NOTICE, 'result:'.$ret['result']);

    if ( !isset($ret['keykey'] ) ) $ret['keykey']='';
    write_oplog($funcname, $ret['keykey'], $request, $ret['result']);

    return $ret['result'];
}

/**
 * �����ຯ������Ԥ�������Ƚ�$job����������array���������񣬵��ý������дoplog���ɹ��ύ����ʧ�ܻع�����
 * @param job $job
 * @return array=('return'=>true/false,'result'=>'XXX','keykey'=>'XXX',...}
 */
function apply_with_tran($job)
{
    global $db;

    $request=$job->workload();
    $funcname=$job->functionName();
    Log::prn_log(NOTICE, 'request:'.$funcname.':'.$request);

    $req=json_decode($request, true);
    if ( $req == NULL ) {
        Log::prn_log(ERROR, 'param error, json_decode is NULL!');
        return assign_result('MMM', 'param error!');
    }

    //$db->autocommit(FALSE);
    $db->query('SET AUTOCOMMIT=0'); //֧��mysql�Զ�����
    $ret=$funcname($req);
    Log::prn_log(NOTICE, "result:".$ret['result']);
    if ($ret['return']) {
      $db->commit();
    } else {
      $db->rollback();
    }
    $db->autocommit(TRUE);
    if ( !isset($ret['keykey'] ) ) $ret['keykey']='';
    write_oplog($funcname, $ret['keykey'], $request, $ret['result']); 
              
    return $ret['result'];
}

/**
 * ����msisdn/userid����û��Ϸ���
 * @global mysqldb $db
 * @param array $req �������{'userid'=>'XXX', 'msisdn'=>'XXX'} ����������������һ
 * @param array/string $user �û���������/������Ϣ����
 * @return boolean
 */
function chk_user_valid($req, &$user)
{
    global $db;

    if ( isset($req['userid']) ) {
        $user=$db->select_one("select * from user where userid='{$req['userid']}'");
        if ( $user === false ) {
          $user='accounter is not exists!';
          return false;
        }
    } else
    if ( isset($req['msisdn']) ) {
        $user=$db->select_one("select * from user where msisdn='{$req['msisdn']}'");
        if ( $user === false ) {
          $user='accounter is not exists!';
          return false;
        }
    } else {
        Log::prn_log(ERROR, 'query param is error,<userid> or <msisdn> is not exists!');
        $user='query param is error!';
        return -1;
    }

    return true;
}

/**
 * �����ʽ��
 * @param string $msisdn ��ۺ��루�����ֿ���ǰ׺��
 * @return string ��ʽ����ĺ��루852XXXXXXXX��
 */
function format_msisdn($msisdn)
{
    if ((substr($msisdn,0,2) == '86')&&(strlen($msisdn)==13)) return $msisdn;
    else if ((substr($msisdn,0,3) == '+86')&&(strlen($msisdn)==14)) return substr($msisdn,1);
    else if ((substr($msisdn,0,3) == '852')&&(strlen($msisdn)==11)) return $msisdn;
    else if ((substr($msisdn,0,4) == '+852')&&(strlen($msisdn)==12)) return substr($msisdn,1);
    else if ((substr($msisdn,0,4) == '4920')&&(strlen($msisdn)==12)) return '852'.substr($msisdn,4);
    else if ((substr($msisdn,0,5) == '00852')&&(strlen($msisdn)==13)) return substr($msisdn,2);
    else if (strlen($msisdn)==8) return '852'.$msisdn;
    else return $msisdn;
}
