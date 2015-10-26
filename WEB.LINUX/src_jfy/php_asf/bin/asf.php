#!/usr/bin/env php
<?php
error_reporting(E_ALL);

if(empty($argv[1]))
{
    echo "Usage: voipd {start|stop|restart|reload|status}".PHP_EOL;
    exit;
}

$cmd = $argv[1];
$server_file = '/usr1/app/php/voip/voip_server.php';
$pid_file = '/var/local/swoole_' . substr(basename($server_file), 0, -4) . ".pid";

switch($cmd)
{
    case 'start':
        if (file_exists($pid_file)){
            $pid = file_get_contents($pid_file);
            $pid = intval($pid);
            if ($pid > 0 && posix_kill($pid, 0)){
                exit("the server is already started!\n");
            }
        }
        start_and_wait(15);
        exit;
        break;
    case 'stop':
        stop_and_wait(5);
        exit;
        break;
    case 'restart':
        stop_and_wait(5);
        start_and_wait(15);
        exit;
        break;
    case 'reload':
        $pid = @file_get_contents($pid_file);
        if(empty($pid))
        {
            exit("Server is not running!\n");
        }
        if (!posix_kill($pid, 0)){
            exit("Server is not running!\n");
        }
        posix_kill($pid, SIGUSR1);
        echo "Server reload ok!\n";
        break;
    case 'status':
        $pid = @file_get_contents($pid_file);
        if(empty($pid))
        {
            exit("Server is not running!\n");
        }
        if (!posix_kill($pid, 0)){
            exit("Server is not running!\n");
        }
        exec("ps -ef | grep 'voip_server' | grep -v grep", $ret);
        foreach($ret as $line) echo $line."\n";
        break;
    default:
        echo "Usage: workermand {start|stop|restart|reload}\n";
        exit;

}

function start_and_wait($wait_time = 5)
{
    global $server_file;
    global $pid_file;

    echo exec("/usr/bin/php $server_file");

    $start_time = time();
    $succ=false;
    while(true)
    {
        if (file_exists($pid_file)){
            $pid = file_get_contents($pid_file);
            $pid = intval($pid);
            if ($pid > 0 && posix_kill($pid, 0)){
                exec("ps -ef | grep 'voip_server' | grep -v grep", $ret);
                if ( count($ret) > 2 ) {
                    $succ=true;
                    break;
                }
            }
        }
        clearstatcache();
        usleep(1000);
        if(time()-$start_time >= $wait_time)
        {
            usleep(500000);
            break;
        }
    }
    $succ = true;
    if ( $succ )
        echo "Server start ok!\n";
    else
        echo "Server start error, please view logfile!\n";

    return;
}

function stop_and_wait($wait_time = 5)
{
    global $pid_file;

    $pid = @file_get_contents($pid_file);
    if(empty($pid))
    {
        exit("Server is not running!\n");
    }
    if (!posix_kill($pid, 0)){
        exit("Server is not running!\n");
    }
    posix_kill($pid, SIGTERM);

    $start_time = time();
    while(is_file($pid_file))
    {
        clearstatcache();
        usleep(1000);
        if(time()-$start_time >= $wait_time)
        {
            posix_kill($pid, SIGTERM);
            posix_kill($pid, SIGTERM);
            unlink($pid_file);
            usleep(500000);
            break;
        }
    }

    echo "Server stop ok!\n";

    return;
}
