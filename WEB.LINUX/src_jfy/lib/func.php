<?php

/**
 * ��һ��������Ԫ�ػ�һ���ַ�������urlencodeת�����
 * @param string/array $str
 * @return string/array ת�����ַ���������
 */
function url_encode($str) {
    if (is_array($str)) {
        foreach ($str as $key => $value) {
            $str[urlencode($key)] = url_encode($value);
        }
    } else {
        //if ( !is_bool($str) ) $str = urlencode($str);
        if ( is_string($str) ) $str = urlencode($str);
    }

    return $str;
}

/**
 * ��һ�������jsonencode����(֧������)
 * @param array $array ��ֵ����
 * @return string
 */
//function encode_json($arrval, $keyval=true)
//{
//    $str = url_encode($arrval);
//    return urldecode(json_encode($str));
//}

function encode_json($str) {
    return json_encode(url_encode($str));
}

/**
 * ��һjson�������jsondecode����(֧������)
 * @param array $array ��ֵ����
 * @return array
 */
function decode_json($jsonstr)
{
    return(gbk_iconv(json_decode(iconv("GBK", "UTF-8", $jsonstr), true)));
}

/**
 * ��һ������ַ���ת����UTF-8��bgkת��
 * @param array/string $str
 * @return string ת�����
 */
function gbk_iconv($str)
{
    if (is_array($str)) {
        foreach ($str as $key => $value) {
            $str[$key] = gbk_iconv($value);
        }
    } else {
        $str = iconv("UTF-8", "gbk", $str);
    }

    return $str;
}

/**
 * ���һ��������У��Ƿ���������ֵkey
 * @param array $param �������
 * @param array $varnames ��������ļ�ֵkey�б�
 * @return boolean
 */
function check_array($param, $varnames)
{
    $ret=true;
    foreach ($varnames as $varname) {
        if ( !isset($param[$varname]) ) {
            Log::prn_log(ERROR, "param is error, <$varname> is not exists!");
            $ret=false;
        }
    }

    return $ret;
}
