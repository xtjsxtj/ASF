CREATE DATABASE `voip` DEFAULT CHARACTER SET latin1 default COLLATE  latin1_bin;
use voip;

-- �û���
drop table IF EXISTS user;
create table user (
    userid        char(8)     binary PRIMARY key,
    username      varchar(32) comment '�û��ǳ�',
    feature       char(16)    comment '����',
    secure        TINYINT(1)  default 0 comment '���ܱ�־',
    siminfo       char(15)    comment 'SIM��ϢIMSI',
    msisdn        char(13)    comment '�û��ֻ�����',
    countyrcode   char(3)     comment '������',
    zgtflag       char(1)     comment '�и�ͨ��־YN',
    usertype      char(16)    comment '�û�����',
    operatorid    char(32)    comment '��������',
    createtime    TIMESTAMP   comment '����ʱ��',
    PRIMARY KEY (msisdn),
    unique index useridx(userid)
) comment '�û����ϱ�';

-- �û��ʻ���
drop table IF EXISTS useracnt;
create table useracnt (
    userid        char(8)       binary PRIMARY key ,
    activetime    TIMESTAMP     comment '����ʱ��',
    amount        INT UNSIGNED  comment '�ʻ����(��)',
    validdate     date          comment '��Ч��',
    status        char(1)       comment '�ʻ�״̬',
    nextkfdate    date          comment '�´ο۷�ʱ��'
) comment '���и�ͨ�û��ʻ���';

-- ��ֵ��¼��
drop table IF EXISTS rechargelog;
create table rechargelog (
    vid        int           auto_increment PRIMARY key,
    userid     char(8)       binary,
    chargetype char(10)      comment '��ֵ����',
    cardno     char(32)      comment '��ֵ����',
    charge     decimal(10,2) comment '��ֵ���',
    chargetime TIMESTAMP     comment '��ֵʱ��',
    validdays  integer       comment '��ֵ��Ч������',
    status     char(1)       comment '��ֵ״̬',
    memo       varchar(100)  comment '��ע',
    KEY `logi1` (userid)
) comment '��ֵ��¼��';

-- �۷���־��
drop table IF EXISTS charginglog;
create table charginglog (
    vid        integer       auto_increment PRIMARY key,
    userid     char(8)       binary,
    msisdn     char(13)      comment 'MSISDN',
    callnoa    char(64)      comment 'MOC',
    callnob    char(64)      comment 'MTC',
    calltype   char(10)      comment 'ͨ������',
    amount     decimal(10,2) comment '�۷ѽ��',
    duration   integer       comment 'ʱ��',
    callid     varchar(64)   comment '���б�ʶ',
    chargetime TIMESTAMP     comment '�۷�ʱ��',
    status     char(3)       comment '�۷�״̬',
    memo       varchar(100)  comment '��ע',
    KEY `logi1` (userid,msisdn)
) comment '�۷���־��';

-- oplog
drop table if exists oplog;
CREATE TABLE `oplog` (
  `recid`    int(11)   NOT NULL AUTO_INCREMENT,
  `optime`   TIMESTAMP COLLATE latin1_bin NOT NULL,
  `funcname` char(64)  COLLATE latin1_bin NOT NULL,
  `keykey`   char(64)  COLLATE latin1_bin NOT NULL,
  `jsonreq`  varchar(1024) COLLATE latin1_bin NOT NULL,
  `jsonrep`  varchar(1024) COLLATE latin1_bin NOT NULL,
  PRIMARY KEY (`recid`),
  KEY `oplogi1` (`funcname`,`keykey`,`optime`)
) comment '������־��';

