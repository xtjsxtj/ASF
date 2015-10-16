<?php

/**
 * MySQLi���ݿ��װ��
 * @author jiaofuyou@qq.com
 * @date   2014-11-25
    config = array (
        'host'       => 'localhost',  //mysql����
        'port'       => 3306,         //mysql�˿�
        'user'       => 'user',       //mysql�û����������
        'passwd'     => 'password',   //mysql���룬�������
        'name'       => 'dbname',     //���ݿ����ƣ��������
        'persistent' => false,        //MySQL������
        'charset'    => 'utf8',       //�������ݿ��ַ���
        'sqls'       => 'set wait_timeout=24*60*60*31;set wait_timeout=24*60*60*31'  
                                      //�������ݿ����Ҫִ�е�SQL���,��';'�ָ��Ķ������
    )
 */
class mysqldb extends mysqli
{
    public $conn = null;
    public $config;

    public function __construct($db_config)
    {
        if ( !isset($db_config['host']) ) $db_config['host'] = 'localhost';
        if ( !isset($db_config['port']) ) $db_config['port'] = 3306;
        $this->config = $db_config;
        if ( isset($db_config['persistent'])?$db_config['persistent']:false )
        {
            $this->config['host'] = 'p:'.$this->config['host'];
            //$host��Prepending host by p: opens a persistent connection,'p:172.16.18.114'
            //must is mysqli->close()��־����ӲŻᱻ����
        }
    }

    public function connect($host = NULL, $user = NULL, $password = NULL, $database = NULL, $port = NULL, $socket = NULL)
    {
        $db_config = $this->config;
        @parent::connect($db_config['host'], $db_config['user'], $db_config['passwd'], $db_config['name'], $db_config['port']);
        if( $this->connect_errno ){
            Log::prn_log(ERROR, "database connect failed: ".$this->connect_error."!");
            return false;
        }
        Log::prn_log(INFO, "database connect ok ({$db_config['host']},{$db_config['port']})!");
        if ( isset($db_config['charset']) ) {
            Log::prn_log(INFO, "set charset names {$db_config['charset']}");
            $this->query("set names {$db_config['charset']}");
        }       
        if ( isset($db_config['sqls']) ) {
            Log::prn_log(INFO, "set charset names {$db_config['charset']}");
            $this->query("set names {$db_config['charset']}");
        }    
        if ( isset($db_config['sqls']) ) {
            $sqls = explode(";", $db_config['sqls']);
            foreach($sqls as $sql)
            {
                Log::prn_log(INFO, "$sql");
                $this->query($sql);
            }
        } 
        
        return true;
    }

    /**
     * ִ��һ��SQL���
     * @param string $sql ִ�е�SQL���
     * @return result(object) | false
     */
    public function query($sql)
    {
        $result = false;
        for ($i = 0; $i < 2; $i++)
        {
            $result = @parent::query($sql);
            if ($result === false)
            {
                if ($this->errno == 2013 or $this->errno == 2006)
                {
                    Log::prn_log(ERROR, "[{$this->errno}]{$this->error}, reconnect ...");
                    $r = $this->checkConnection();
                    if ($r === true) continue;
                }
                else
                {
                    return false;
                }
            }
            break;
        }
        if ($result === false)
        {
            Log::prn_log(ERROR, "mysql connect lost, again still failed, {$this->errno}, {$this->error}");
            return false;
        }

        return $result;
    }

    /**
     * ������ݿ�����,�Ƿ���Ч����Ч�����½���
     */
    protected function checkConnection()
    {
        if (!@$this->ping())
        {
            $this->close();
            return $this->connect();
        }
        return true;
    }

    /**
     * ��ѯΨһ��¼
     * @param string $sql ִ�е�SQL���
     * @return row(array) | false
     */
    public function select_one($sqlstr,$flag=true){
        if ( !($result = $this->query($sqlstr)) ) {
            Log::prn_log(ERROR, "select_one,($sqlstr) error,$this->errno,$this->error!");
            return false;
        }
        if ( $result->num_rows == 0 ) {
            if ($flag) Log::prn_log(ERROR, "select_one,($sqlstr) not found!");
            return false;
        } else if ( $result->num_rows > 1 ) {
            if ($flag) Log::prn_log(ERROR, "select_one ($sqlstr) mulit found!");
            return false;
        }
        $row = $result->fetch_assoc();
        Log::prn_log(INFO, 'select_one ok:'.json_encode($row));
        return $row;
    }

    /**
     * ��ѯ������¼
     * @param string $sql ִ�е�SQL���
     * @return result(array) | false
     */
    public function select_more($sqlstr){
        if ( !($result = $this->query($sqlstr)) ) {
            Log::prn_log(ERROR, "select_more,($sqlstr) error,$this->errno,$this->error!");
            return false;
        }
        for ($res = array(); $tmp = $result->fetch_assoc();) $res[] = $tmp;

        return $res;
    }

    /**
     * ���뵥����¼
     * @param string $sql ִ�е�SQL���
     * @return true | false
     * $this->insert_id Ϊ�����ֶ�ID
     */
    public function insert_one($sqlstr,$flag=true){
        if ( !($result = $this->query($sqlstr)) ) {
            Log::prn_log(ERROR, "insert_one,($sqlstr) error,{$this->errno},{$this->error}!");
            return false;
        }
        if ($flag) Log::prn_log(INFO, 'insert_one ok: '.$sqlstr);

        return true;
    }

    /**
     * ���µ�����¼
     * @param string $sql ִ�е�SQL���
     * @return true | false
     * @$this->affected_rows Ϊ���¼�¼��
     */
    public function update_one($sqlstr){
        if ( !($result = $this->query($sqlstr)) ) {
            Log::prn_log(ERROR, "update_one,($sqlstr) error,{$this->errno},{$this->error}!");
            return false;
        }
        $rows = $this->affected_rows;
        if ( $rows != 1 ) {
          Log::prn_log(ERROR, "update_one ,($sqlstr) affected_rows is $rows!");
          return false;
        }
        Log::prn_log(INFO, 'update_one ok: '.$sqlstr);

        return true;
    }

    /**
     * ���¶�����¼
     * @param string $sql ִ�е�SQL���
     * @return true | false
     */
    public function update_more($sqlstr){
        if ( !($result = $this->query($sqlstr)) ) {
            Log::prn_log(ERROR, "update_more,($sqlstr) error,{$this->errno},{$this->error}!");
            return false;
        }
        Log::prn_log(INFO, 'update_more ok: '.$sqlstr);
        Log::prn_log(INFO, "updated $this->affected_rows row");

        return true;
    }
}

$db=null;
